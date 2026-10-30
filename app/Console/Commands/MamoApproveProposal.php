<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MamoApproveProposal extends Command
{
    protected $signature = 'mamo:approve-proposal {id}';
    protected $description = 'Aprueba una propuesta y la convierte en regla real o ajuste';

    public function handle()
    {
        $id = $this->argument('id');
        $propuesta = DB::table('rule_proposals')->where('id', $id)->where('estado', 'pendiente')->first();

        if (!$propuesta) {
            $this->error("Propuesta no encontrada o ya procesada.");
            return;
        }

        DB::beginTransaction();
        try {
            if ($propuesta->tipo === 'nueva_regla') {
                // Insertar nueva regla en material_relaciones
                DB::table('material_relaciones')->insert([
                    'SKU' => $propuesta->sku_nodo,
                    'depende_SKU' => $propuesta->depende_sku,
                    'skufinal' => $propuesta->sku_nodo, // Sugerencia: usar el SKU como nombre de regla
                    'tipo_relacion' => 'calculo', // Por defecto
                    'minimo' => 1, // Activador
                    'maximo' => 10000,
                    'formula' => 'valant',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info("Nueva regla insertada para {$propuesta->sku_nodo}");
            } 
            elseif ($propuesta->tipo === 'ajuste_maximo') {
                $datos = json_decode($propuesta->datos_sugeridos, true);
                // Actualizar todas las reglas de ese skufinal
                DB::table('material_relaciones')
                    ->where('skufinal', $propuesta->skufinal_propuesto)
                    ->update(['maximo' => $datos['nuevo_maximo'], 'updated_at' => now()]);
                $this->info("Máximo actualizado para {$propuesta->skufinal_propuesto}");
            }

            // Marcar como aprobada
            DB::table('rule_proposals')->where('id', $id)->update(['estado' => 'aprobada', 'updated_at' => now()]);
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error al aprobar propuesta: " . $e->getMessage());
        }
    }
}