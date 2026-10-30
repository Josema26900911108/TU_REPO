<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MamoFixValues extends Command
{
    protected $signature = 'mamo:fix-values {periodo}';
    protected $description = 'Recalcula el campo valor (depende_sku) en monthly_learning_data';

    public function handle()
    {
        $periodo = $this->argument('periodo');
        $this->info("Calculando valores reales para el periodo {$periodo}...");

        // Buscamos los registros de aprendizaje que tienen depende_sku pero valor = 0
        $registros = DB::table('monthly_learning_data')
            ->where('periodo', $periodo)
            ->whereNotNull('depende_sku')
            ->where('valor', 0)
            ->get();

        $count = 0;

        foreach ($registros as $reg) {
            // Buscar si el depende_sku se reportó en la misma orden de ETA
            $cantidadPadre = DB::table('ETA')
                ->where('Orden', $reg->orden_id)
                ->where('SKU', $reg->depende_sku)
                ->sum('Cantidad');

            if ($cantidadPadre > 0) {
                DB::table('monthly_learning_data')
                    ->where('id', $reg->id)
                    ->update(['valor' => $cantidadPadre]);
                $count++;
            }
        }

        $this->info("Se actualizaron {$count} registros con su valor real.");
    }
}