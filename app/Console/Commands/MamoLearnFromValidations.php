<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MamoLearnFromValidations extends Command
{
    // Uso: php artisan mamo:learn-validations 2026-06
    protected $signature = 'mamo:learn-validations {periodo}';
    protected $description = 'Alimenta la IA con los resultados de las validaciones para que aprenda';

    public function handle()
    {
        $periodo = $this->argument('periodo');
        
        // Asumiendo que guardas los resultados en una tabla llamada 'validaciones_mamo'
        // Si los tienes en memoria o los pasas por parámetro, ajusta esta consulta.
        $validaciones = DB::table('validaciones_mamo')->get(); // <-- Ajusta el nombre de la tabla aquí

        if ($validaciones->isEmpty()) {
            $this->error("No hay validaciones para procesar.");
            return;
        }

        $count = 0;
        foreach ($validaciones as $val) {
            // Evitar duplicados
            $existe = DB::table('monthly_learning_data')
                ->where('orden_id', $val->Orden)
                ->where('sku_nodo', $val->SKU_Origen)
                ->where('skufinal_regla', $val->skuOrigenraiz)
                ->exists();

            if (!$existe) {
                DB::table('monthly_learning_data')->insert([
                    'periodo'          => $periodo,
                    'orden_id'         => $val->Orden,
                    'skufinal_regla'   => $val->skuOrigenraiz,
                    'sku_nodo'         => $val->SKU_Origen,
                    'depende_sku'      => $val->SKU_Destino,
                    'usado'            => (float)$val->ValorEntrada,
                    'valor'            => (float)$val->Resultado, // Resultado de la fórmula
                    'resultado_sistema'=> $val->TipoRelacion,     // 'requiere', 'requiere - Exceso'
                    'resultado_real'   => 'pendiente',            // El humano aún no decide
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
                $count++;
            }
        }

        $this->info("Se cargaron {$count} registros de validación en la memoria de la IA.");
    }
}