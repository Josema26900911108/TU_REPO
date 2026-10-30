<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class MamoSuggestFormula extends Command
{
    // Uso: php artisan mamo:suggest-formula {skufinal} {centro} {fkTienda}
    protected $signature = 'mamo:suggest-formula {skufinal} {centro} {fkTienda}';
    protected $description = 'Prueba fórmulas contra el historial segmentado por centro y tienda';

    public function handle()
    {
        $skufinal = $this->argument('skufinal');
        $centro = $this->argument('centro');
        $fkTienda = $this->argument('fkTienda');

        $this->info("Analizando NODO POR NODO para Regla: {$skufinal} | Centro: {$centro} | Tienda: {$fkTienda}");

        // 1. Buscamos los IDs de material_relaciones para esta regla
        // Esto nos ayuda a saber qué ID corresponde a qué SKU
        $nodosRegla = DB::table('material_relaciones')
            ->where('skufinal', $skufinal)
            ->get()
            ->keyBy('SKU'); // Agrupamos por SKU para buscarlos rápido

        // 2. Obtener datos históricos
        $datosAgrupados = DB::table('monthly_learning_data')
            ->where('skufinal_regla', $skufinal)
            ->where('centro', $centro)
            ->where('fkTienda', $fkTienda)
            ->where('resultado_real', '!=', 'pendiente')
            ->get()
            ->groupBy('sku_nodo');

        if ($datosAgrupados->isEmpty()) {
            $this->error("No hay datos suficientes para este centro.");
            return;
        }

        $exprLang = new ExpressionLanguage();
        $formulasCandidatas = [
            'usado - valor',
            '(usado / 2) - valor',
            'usado > 0 ? (valor==0 and usado>0 ? -1 : valant - 1) : 0',
            'usado>0 ? (valor>1 ? usado+valor : (usado>1 ? usado : 0) ) :  (valor>1 ? usado : 0)'
        ];

        foreach ($datosAgrupados as $skuNodo => $datos) {
            if ($datos->count() < 5) continue; 

            // Obtenemos el ID real de este nodo en la base de datos
            $nodoDb = $nodosRegla->get($skuNodo);
            $idNodo = $nodoDb ? $nodoDb->id : '?';

            $this->info("\n--- NODO: ID [{$idNodo}] | SKU: {$skuNodo} ({$datos->count()} registros) ---");
            
            $resultados = [];
            $mejorFormula = null;
            $mejorAcierto = -1;

            foreach ($formulasCandidatas as $formula) {
                $aciertos = 0;
                foreach ($datos as $dato) {
                    try {
                        $res = $exprLang->evaluate($formula, [
                            'usado' => $dato->usado, 
                            'valor' => $dato->valor, 
                            'valant' => $dato->valant_final ?? 0
                        ]);
                        if (($dato->resultado_real === 'aprobada') === ($res > 0)) $aciertos++;
                    } catch (\Exception $e) {}
                }
                $porcentaje = round(($aciertos / $datos->count()) * 100, 2);
                $resultados[] = [$formula, $porcentaje . '%'];
                
                if ($porcentaje > $mejorAcierto) {
                    $mejorAcierto = $porcentaje;
                    $mejorFormula = $formula;
                }
            }

            $this->table(['Fórmula Candidata', '% Acierto'], $resultados);
            $this->line("👉 Mejor opción: '{$mejorFormula}' ({$mejorAcierto}%)");
        }
    }
}