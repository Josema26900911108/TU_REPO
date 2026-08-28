<?php

namespace App\Imports;

use App\Models\Expedientetecnico;
use App\Models\Tecnico;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class ExpedienteImport implements ToModel, WithHeadingRow
{
    // 🛠️ Propiedad pública para guardar y extraer los errores desde el controlador
    public array $erroresReportados = [];
    private int $filaActual = 1;

    public function model(array $row)
    {
        $this->filaActual++;

        $fkTienda = session('user_fkTienda') ? (int)session('user_fkTienda') : null;
        $nombreUsuario = session('nombreUsuario') ?? 'Sistema Masivo';

        // VALIDACIÓN 1: El usuario debe tener una tienda en su sesión activa
        if (empty($fkTienda)) {
            $this->erroresReportados[] = [
                'fila'   => $this->filaActual,
                'orden'  => $row['orden'] ?? 'N/A',
                'motivo' => "Error de seguridad: Tu sesión no tiene una tienda asignada."
            ];
            return null;
        }

        // VALIDACIÓN 2: Campos obligatorios vacíos (Como en importarMAMO)
        if (empty($row['orden']) || !isset($row['virtual']) || $row['virtual'] === '') {
            $this->erroresReportados[] = [
                'fila'   => $this->filaActual,
                'orden'  => $row['orden'] ?? 'N/A',
                'motivo' => "El campo 'Orden' o 'virtual' se encuentra vacío."
            ];
            return null;
        }

        $orden = trim($row['orden']);
        $virtual = trim($row['virtual']);
        $ahora = now();

        // 1. Buscar el ID del técnico usando la columna CENTRO/CODIGO del Excel
        $codigoTecnico = isset($row['centrocodigo']) ? trim($row['centrocodigo']) : null;
        $tecnicoId = null;

        if ($codigoTecnico) {
            $tecnico = Tecnico::where('codigo', $codigoTecnico)->first();
            if ($tecnico) {
                $tecnicoId = $tecnico->id;
            } else {
                // Opcional: Si quieres reportar como error que el código de técnico no exista
                $this->erroresReportados[] = [
                    'fila'   => $this->filaActual,
                    'orden'  => $orden,
                    'motivo' => "El código de técnico '{$codigoTecnico}' no existe en el sistema."
                ];
                return null;
            }
        } else {
            $this->erroresReportados[] = [
                'fila'   => $this->filaActual,
                'orden'  => $orden,
                'motivo' => "La columna 'CENTRO/CODIGO' viene vacía."
            ];
            return null;
        }

        // 2. Formatear la fecha de instalación
        $fechaInst = null;
        if (!empty($row['fechainstalacion'])) {
            try {
                $fechaInst = Carbon::createFromFormat('d/m/Y', trim($row['fechainstalacion']))->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                try {
                    $fechaInst = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(trim($row['fechainstalacion']))->format('Y-m-d H:i:s');
                } catch (\Exception $ex) {
                    $fechaInst = $ahora; 
                }
            }
        } else {
            $fechaInst = $ahora;
        }

        // 🛠️ RESTRICCIONES PREVENTIVAS DE TAMAÑO (Para evitar SQLSTATE 22001)
        $statusLimpio      = isset($row['status'])        ? strtoupper(substr(trim($row['status']), 0, 1))        : null;
        $estatusGralLimpio = isset($row['estatus'])       ? strtoupper(substr(trim($row['estatus']), 0, 2))       : null;
        $tipoServicio      = isset($row['tipo_servicio']) ? substr(trim($row['tipo_servicio']), 0, 2)            : null;
        $tipoOrden         = isset($row['tipo_orden'])    ? substr(trim($row['tipo_orden']), 0, 2)               : null;



        // 3. LOGICA DE VALIDACIÓN, REASIGNACIÓN Y ACTUALIZACIÓN
        // 🛠️ CORRECCIÓN: Quitamos el '!= RE' para poder encontrar la orden real sin importar su historial
        $expedientePrevio = DB::table('expedientetecnico')
            ->where('orden', $orden)
            ->where('fkTienda', $fkTienda) 
            ->orderBy('id', 'desc') // Evaluamos siempre el movimiento más reciente de la orden
            ->first();

        if ($expedientePrevio) {
            
            // 🛠️ VALIDACIÓN PRIORITARIA: Si el registro más reciente de la orden ya está cerrado ('C'), se bloquea por completo
            if ($expedientePrevio->ESTATUS === 'C') {
                $this->erroresReportados[] = [
                    'fila'   => $this->filaActual,
                    'orden'  => $orden,
                    'motivo' => "No se puede asignar o reasignar orden debido a que ya está cerrada, favor de crear orden de complemento."
                ];
                return null; // ✋ Frena la ejecución de inmediato. Evita el insert del final.
            }

            // CASO A: Si el técnico asignado en el Excel es idéntico al actual -> SE ACTUALIZA
            if ((int)$expedientePrevio->fkTecnico === (int)$tecnicoId) {
                DB::table('expedientetecnico')
                    ->where('id', $expedientePrevio->id)
                    ->update([
                        'status'           => !empty($statusLimpio) ? $statusLimpio : $expedientePrevio->status,
                        'ESTATUS'          => !empty($estatusGralLimpio) ? $estatusGralLimpio : $expedientePrevio->ESTATUS,
                        'FECHAINSTALACION' => $fechaInst,
                        'updated_at'       => $ahora
                    ]);

                $this->erroresReportados[] = [
                    'fila'   => $this->filaActual,
                    'orden'  => $orden,
                    'motivo' => "Se actualiza orden."
                ];
                return null; // ✋ Frena la ejecución. Evita el insert del final.
            } 

            // CASO B: Si la orden está abierta pero pertenece a OTRO técnico, se reasigna cambiándola a 'RE'
            // 🛠️ NOTA: Solo la reasignamos si no estaba ya en estatus 'RE' para evitar bucles de actualización innecesarios
            if ($expedientePrevio->ESTATUS !== 'RE') {
                DB::table('expedientetecnico')
                    ->where('id', $expedientePrevio->id)
                    ->update([
                        'ESTATUS'    => 'RE',
                        'obs'        => (($expedientePrevio->obs ?? '') . " | Reasignada a técnico ID: $tecnicoId por masivo de $nombreUsuario"),
                        'updated_at' => $ahora
                    ]);
            }
                
            // 🛠️ LÓGICA CLAVE: Tras marcar el registro viejo como 'RE', procedemos a insertar la nueva fila 
            // asignada al nuevo técnico (Quitamos el 'return null' de aquí para que el flujo continúe hacia el constructor del final)
            Log::info("Fila {$this->filaActual}: Generando nueva fila de reasignación para la orden {$orden}.");
        }



        // 3. 🛠️ DIRECTO A LA BASE DE DATOS (Sin try-catch interno para poder ver el error real si falla)
        return new Expedientetecnico([
            'Orden'            => $row['orden'] ?? null,
            'virtual'          => $row['virtual'] ?? null,
            'Status'           => $row['status'] ?? null,
            'Tipo_servicio'    => $row['tipo_servicio'] ?? null,
            'Tipo_orden'       => $row['tipo_orden'] ?? null,
            'NOMBRECLIENTE'    => $row['nombrecliente'] ?? null,
            'DIRECCION'        => $row['direccion'] ?? null,
            'OBS'              => $row['obs'] ?? null,
            'SIGLASCENTRAL'    => $row['siglascentral'] ?? null,
            'AREA'             => $row['area'] ?? null,
            'FECHAINSTALACION' => $fechaInst,
            'AUTORIZA'         => "1T", 
            'ESTATUS'          => $row['estatus'] ?? null,
            'TECNOLOGIA'       => $row['tecnologia'] ?? null,
            'fkTecnico'        => $tecnicoId, 
            'fkTienda'         => $fkTienda,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }
}
