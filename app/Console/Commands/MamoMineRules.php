<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MamoMineRules extends Command
{
    protected $signature = 'mamo:mine-rules {--periodo=}';
    protected $description = 'Analiza datos mensuales y genera propuestas de reglas (sin modificar la tabla real)';

    public function handle()
    {
        $periodo = $this->option('periodo');
        $query = DB::table('monthly_learning_data')->where('resultado_real', 'aprobada');
        
        if ($periodo) {
            $query->where('periodo', $periodo);
        }

        $this->info("Iniciando minería de datos...");
        
        $this->buscarReglasFaltantes($query);
        $this->buscarAjustesMaximos($query);

        $this->info("Minería completada. Revisa la tabla 'rule_proposals'.");
    }

    private function buscarReglasFaltantes($baseQuery)
    {
        // Buscar pares SKU -> depende_SKU que se repiten mucho en órdenes aprobadas
        $pares = (clone $baseQuery)
            ->select('sku_nodo', 'depende_sku', DB::raw('COUNT(*) as frecuencia'))
            ->whereNotNull('depende_sku')
            ->groupBy('sku_nodo', 'depende_sku')
            ->having('frecuencia', '>=', 20) // Umbral mínimo de apariciones
            ->get();

        foreach ($pares as $par) {
            // Verificar si ya existe en material_relaciones
            $existe = DB::table('material_relaciones')
                ->where('SKU', $par->sku_nodo)
                ->where('depende_SKU', $par->depende_sku)
                ->exists();

            if (!$existe) {
                // Evitar duplicar propuestas pendientes
                $yaPropuesta = DB::table('rule_proposals')
                    ->where('sku_nodo', $par->sku_nodo)
                    ->where('depende_sku', $par->depende_sku)
                    ->where('estado', 'pendiente')
                    ->exists();

                if (!$yaPropuesta) {
                    DB::table('rule_proposals')->insert([
                        'tipo' => 'nueva_regla',
                        'sku_nodo' => $par->sku_nodo,
                        'depende_sku' => $par->depende_sku,
                        'datos_sugeridos' => json_encode(['frecuencia' => $par->frecuencia]),
                        'justificacion' => "El SKU {$par->sku_nodo} y {$par->depende_sku} aparecen juntos en {$par->frecuencia} órdenes aprobadas, pero no tienen regla.",
                        'estado' => 'pendiente',
                        'created_at' => now(),
                    ]);
                    $this->line("Propuesta creada: Nueva regla para {$par->sku_nodo} -> {$par->depende_sku}");
                }
            }
        }
    }

    private function buscarAjustesMaximos($baseQuery)
    {
        // Buscar casos donde el "usado" superó el "maximo" actual pero la orden fue aprobada
        $desviaciones = (clone $baseQuery)
            ->join('material_relaciones as r', function($join) {
                $join->on('monthly_learning_data.skufinal_regla', '=', 'r.skufinal')
                     ->on('monthly_learning_data.sku_nodo', '=', 'r.SKU');
            })
            ->select(
                'monthly_learning_data.skufinal_regla', 
                'r.maximo as maximo_actual', 
                DB::raw('MAX(monthly_learning_data.usado) as usado_maximo_historico')
            )
            ->whereRaw('monthly_learning_data.usado > r.maximo')
            ->where('r.maximo', '!=', 10000)
            ->groupBy('monthly_learning_data.skufinal_regla', 'r.maximo')
            ->get();

        foreach ($desviaciones as $desv) {
            $yaPropuesta = DB::table('rule_proposals')
                ->where('skufinal_propuesto', $desv->skufinal_regla)
                ->where('tipo', 'ajuste_maximo')
                ->where('estado', 'pendiente')
                ->exists();

            if (!$yaPropuesta) {
                DB::table('rule_proposals')->insert([
                    'tipo' => 'ajuste_maximo',
                    'skufinal_propuesto' => $desv->skufinal_regla,
                    'datos_sugeridos' => json_encode(['nuevo_maximo' => $desv->usado_maximo_historico, 'maximo_actual' => $desv->maximo_actual]),
                    'justificacion' => "Históricamente se usaron hasta {$desv->usado_maximo_historico} unidades (aprobadas), pero el máximo actual es {$desv->maximo_actual}.",
                    'estado' => 'pendiente',
                    'created_at' => now(),
                ]);
                $this->line("Propuesta creada: Ajustar máximo de {$desv->skufinal_regla} a {$desv->usado_maximo_historico}");
            }
        }
    }
}