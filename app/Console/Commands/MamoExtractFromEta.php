<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MamoExtractFromEta extends Command
{
    protected $signature = 'mamo:extract-eta {periodo}';
    protected $description = 'Extrae datos de ETA procesando por bloques';

    public function handle()
    {
        $periodo = $this->argument('periodo');
        $this->info("Extrayendo datos para {$periodo}...");

        // Limpiar datos previos de este periodo
        DB::table('monthly_learning_data')->where('periodo', $periodo)->delete();

        // Mapa de relaciones (SKU -> depende_SKU)
        $relaciones = DB::table('material_relaciones')->get()->keyBy(function ($item) {
            return trim($item->SKU);
        });

        $count = 0;

        // Procesar en bloques de 5000
        DB::table('ETA')
            ->whereBetween('created_at', ["{$periodo}-01 00:00:00", "{$periodo}-31 23:59:59"])
            ->select('Orden', 'SKU', 'CENTRO', 'fkTienda', 'TIPO_DE_SERVICIO')
            ->orderBy('id')
            ->chunk(1000, function ($items) use ($periodo, $relaciones, &$count) {
                
                // 1. Agrupar cantidades del bloque actual
                $ordenesEnBloque = $items->pluck('Orden')->unique()->toArray();
                
                $cantidadesEta = DB::table('ETA')
                    ->whereIn('Orden', $ordenesEnBloque)
                    ->whereBetween('created_at', ["{$periodo}-01 00:00:00", "{$periodo}-31 23:59:59"])
                    ->select('Orden', 'SKU', DB::raw('SUM(Cantidad) as total'))
                    ->groupBy('Orden', 'SKU')
                    ->get();

                $cantidadesMap = [];
                foreach ($cantidadesEta as $c) {
                    $cantidadesMap[$c->Orden][trim($c->SKU)] = (float)$c->total;
                }

                // 2. Armar registros
                $datosInsertar = [];
                foreach ($items as $item) {
                    $sku = trim($item->SKU);
                    $orden = $item->Orden;
                    $relacion = $relaciones->get($sku);

                    $skufinal = null;
                    $dependeSku = null;
                    $valorReal = 0;

                    if ($relacion) {
                        $skufinal = $relacion->skufinal;
                        $dependeSku = trim($relacion->depende_SKU ?? '');
                        if ($dependeSku !== '' && isset($cantidadesMap[$orden][$dependeSku])) {
                            $valorReal = $cantidadesMap[$orden][$dependeSku];
                        }
                    }

                    $datosInsertar[] = [
                        'periodo'          => $periodo,
                        'fkTienda'         => (string)$item->fkTienda,
                        'centro'           => (string)$item->CENTRO,
                        'tipo_servicio'    => (string)$item->TIPO_DE_SERVICIO,
                        'orden_id'         => $orden,
                        'skufinal_regla'   => $skufinal,
                        'sku_nodo'         => $sku,
                        'depende_sku'      => $dependeSku,
                        'usado'            => $cantidadesMap[$orden][$sku] ?? 1,
                        'valor'            => $valorReal,
                        'valant_final'     => 0,
                        'resultado_real'   => 'aprobada', 
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ];
                    $count++;
                }

                if (!empty($datosInsertar)) {
                    DB::table('monthly_learning_data')->insert($datosInsertar);
                }
            });

        $this->info("¡Listo! Se cargaron {$count} registros correctamente.");
    }
}