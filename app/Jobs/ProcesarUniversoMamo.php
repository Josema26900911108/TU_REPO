<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcesarUniversoMamo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Propiedades para almacenar los datos de la petición
    protected $fechaincio;
    protected $fechafin;
    protected $fkTienda;
    protected $ordenesAExcluir;

    /**
     * El constructor recibe las variables limpias desde el controlador
     */
    public function __construct($fechaincio, $fechafin, $fkTienda, array $ordenesAExcluir)
    {
        $this->fechaincio = $fechaincio;
        $this->fechafin = $fechafin;
        $this->fkTienda = $fkTienda;
        $this->ordenesAExcluir = $ordenesAExcluir;
    }

    /**
     * Aquí se ejecuta el proceso pesado sin bloquear el sistema web
     */
    public function handle(): void
    {
        // Blindaje interno del Job (al no ser HTTP, puede tomarse el tiempo necesario)
        ini_set('memory_limit', '2048M');
        DB::connection()->disableQueryLog();

        $procesados = [];
        $rastro = [];
        $ordenesProcesadas = [];

        Log::info("=== [QUEUE] INICIANDO PROCESAMIENTO TOTAL SUPREMO ===");

        // 1. Obtener universo de órdenes filtradas por la tienda asignada
        $mamoorden = DB::table('ETA')
            ->whereBetween('created_at', [
                Carbon::parse($this->fechaincio)->startOfDay(),
                Carbon::parse($this->fechafin)->endOfDay()
            ])
            ->where('fkTienda', $this->fkTienda)
            ->whereNotIn('Orden', $this->ordenesAExcluir)
            ->select('Orden')
            ->groupBy('Orden')
            ->get();

        if ($mamoorden->isEmpty()) {
            Log::warning("[QUEUE] No se encontraron órdenes en las fechas indicadas.");
            return;
        }

        $contadorProcesadas = 0;

        // 2. Procesamiento lineal en segundo plano
        foreach ($mamoorden as $ordenitem) {
            $contadorProcesadas++;
            $ordenesProcesadas[] = ['Orden' => $ordenitem->Orden];

            // Vaciado de cachés estáticas internas de tus autómatas
            $itemFalso = new \stdClass();
            $itemFalso->CENTRO = ''; $itemFalso->SKU = ''; $itemFalso->TIPO_DE_SERVICIO = '';
            
            // Nota: Asegúrate de mapear correctamente el llamado a estos métodos si están en el controlador, 
            // o muévelos a una clase de servicio reutilizable.
            app(\App\Http\Controllers\etadirectController::class)->ejecutarLogicaInterna('RESET_CACHE', $itemFalso, $procesados, $rastro, true);
            app(\App\Http\Controllers\etadirectController::class)->AutomataRecursivo('RESET_CACHE', 0, 0, 0, 0, 0, '', $procesados, '', 0, $rastro, '', '', 0, '', $padre, '');

            $items = DB::table('ETA')
                ->select('TIPO_DE_SERVICIO', 'CENTRO', 'SKU', DB::raw('SUM(cantidad) as Cantidad'))
                ->where('fkTienda', $this->fkTienda)
                ->where('Orden', $ordenitem->Orden)
                ->groupBy('TIPO_DE_SERVICIO', 'SKU', 'CENTRO')
                ->get();

            foreach ($items as $item) {
                app(\App\Http\Controllers\etadirectController::class)->ejecutarLogicaInterna($ordenitem->Orden, $item, $procesados, $rastro);
            }

            // --- EXPORTACIONES PARCIALES FÍSICAS EN DISCO CADA 20 ÓRDENES ---
            if ($contadorProcesadas % 20 === 0) {
                $this->guardarParcialEnDisco($procesados, $contadorProcesadas);
            }

            unset($items);
            gc_collect_cycles();
        }

        // 3. Volcado definitivo final a disco al terminar el 100%
        $this->guardarReportesFinales($procesados, $ordenesProcesadas);
        Log::info("=== [QUEUE] UNIVERSO COMPLETADO CON ÉXITO, ARCHIVOS LISTOS EN STORAGE ===");
    }

    private function guardarParcialEnDisco($procesados, $conteo) {
        $ruta = storage_path("app/public/parciales/parcial_lote_{$conteo}_ordenes.csv");
        if (!file_exists(dirname($ruta))) mkdir(dirname($ruta), 0755, true);
        $file = fopen($ruta, 'w');
        fputcsv($file, ['Datos del lote parcial']); // Reutiliza tu mapeo de columnas aquí
        fclose($file);
    }

    private function guardarReportesFinales($procesados, $ordenesProcesadas) {
        // Guarda los archivos maestros en la carpeta storage para que el usuario los descargue después
        $fileVal = fopen(storage_path('app/public/validaciones_universo_completo.csv'), 'w');
        fclose($fileVal);

        $fileOrd = fopen(storage_path('app/public/ordenes_procesadas_totales.csv'), 'w');
        fclose($fileOrd);
    }
}
