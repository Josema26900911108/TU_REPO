<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;use Carbon\Carbon;

class GenerarDespachoCiclicoDiario implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Fijamos el objetivo: Planificar el despacho para el día de MAÑANA
        $manana = Carbon::tomorrow();
        $diaMesManana = (int) $manana->format('d'); // Ejemplo: 5, 20, 30
        $diaSemanaManana = (int) $manana->format('N'); // 1=Lunes, ..., 6=Sábado, 7=Domingo
        
        // Calcular si mañana cae en la Semana 1 o Semana 2 del año para el ciclo quincenal
        $semanaDelAno = (int) $manana->format('W');
        $esSemanaUnoQuincena = ($semanaDelAno % 2 !== 0); // Si es impar = Semana 1, si es par = Semana 2

        // 2. Traer todas las asignaciones cíclicas de la base de datos
        // Hacemos un Join para saber qué tipo de ciclo tiene la ruta de cada asignación
        $asignaciones = DB::table('ruta_dia_cliente')
            ->join('rutas', 'ruta_dia_cliente.ruta_id', '=', 'rutas.id')
            ->select('ruta_dia_cliente.*', 'rutas.tipo_ciclo', 'rutas.centro_costos as ruta_cc')
            ->get();

        foreach ($asignaciones as $asig) {
            $tocaVisitar = false;
            $diaCiclo = (int) $asig->dia_semana;

            // --- EVALUACIÓN DE REGLAS DE NEGOCIO SEGÚN FRECUENCIA ---
            
            // Regla A: Semanal estándar
            if ($asig->tipo_ciclo === 'semanal' && $diaCiclo === $diaSemanaManana) {
                $tocaVisitar = true;
            }
            
            // Regla B: Fin de Semana (6 = Sábado, 7 = Domingo en la DB)
            elseif ($asig->tipo_ciclo === 'fin_de_semana' && $diaCiclo === $diaSemanaManana) {
                $tocaVisitar = true;
            }
            
            // Regla C: Quincenal (Vista maneja del 1 al 14)
            elseif ($asig->tipo_ciclo === 'quincenal') {
                if ($esSemanaUnoQuincena && $diaCiclo === $diaSemanaManana) {
                    $tocaVisitar = true; // Visita en la primera semana
                } elseif (!$esSemanaUnoQuincena && $diaCiclo === ($diaSemanaManana + 7)) {
                    $tocaVisitar = true; // Visita en la segunda semana (8 al 14)
                }
            }
            
            // Regla D: Mensual (1 al 30) con regla de candado para fin de mes (Febrero)
            elseif ($asig->tipo_ciclo === 'mensual' || $asig->tipo_ciclo === 'personalizado') {
                $ultimoDiaMesActual = (int) $manana->copy()->endOfMonth()->format('d');

                if ($diaCiclo === $diaMesManana) {
                    $tocaVisitar = true;
                } 
                // Candado de fin de mes: Si el cliente está agendado el día 30, pero mañana es 28 de Febrero, se ejecuta
                elseif ($diaMesManana === $ultimoDiaMesActual && $diaCiclo > $ultimoDiaMesActual) {
                    $tocaVisitar = true;
                }
            }

            // 3. GENERAR LA ORDEN DE DESPACHO REAL SI PASÓ LAS VALIDACIONES
            // 3. GENERAR LA ORDEN DE DESPACHO REAL SI PASÓ LAS VALIDACIONES
            if ($tocaVisitar) {
                $centroCostosFinal = $asig->centro_costos ?? $asig->ruta_cc;
                
                // Forzar la captura de la tienda. Si la asignación no la tiene, la hereda de la ruta base
                $tiendaFinal = $asig->fkTienda; 
                if (is_null($tiendaFinal)) {
                    $rutaBase = DB::table('rutas')->where('id', $asig->ruta_id)->first();
                    $tiendaFinal = $rutaBase ? $rutaBase->fkTienda : null;
                }

                // VALIDADOR DE SEGURIDAD EXCLUSIVO: Asegurar que el ID de la tienda y del cliente existan físicamente
                $existeTiendaReal = DB::table('tienda')->where('idTienda', $tiendaFinal)->exists();
                $existeClienteReal = DB::table('clientes')->where('id', $asig->cliente_id)->exists();

                if (!is_null($centroCostosFinal) && $existeTiendaReal && $existeClienteReal) {
                    // Evitar duplicar la misma orden de despacho para el mismo cliente el mismo día
                    DB::table('despachos_diarios_pilotos')->updateOrInsert(
                        [
                            'fecha_despacho' => $manana->format('Y-m-d'),
                            'cliente_id' => (int) $asig->cliente_id,
                            'ruta_id' => (int) $asig->ruta_id,
                        ],
                        [
                            'fkTienda' => $tiendaFinal, // Se inserta el ID validado contra idTienda
                            'centro_costos' => trim($centroCostosFinal), 
                            'orden_visita' => (int) $asig->orden, 
                            'estatus_entrega' => 'PENDIENTE',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                } else {
                    // Mensaje de auditoría interno en los logs de Laravel para que sepas qué fila está fallando
                    Log::warning("Se omitió una asignación en el Job por inconsistencia de datos. Ruta ID: {$asig->ruta_id}, Cliente ID: {$asig->cliente_id}, Tienda ID: {$tiendaFinal}");
                }
            }

        }
    }
}
