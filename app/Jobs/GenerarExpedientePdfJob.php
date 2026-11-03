<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Barryvdh\DomPDF\Facade\Pdf;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Str;

class GenerarExpedientePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 1;
    public $failOnTimeout = true;

    protected $excelPath;
    protected $tiendaId;
    protected $userId;
    protected $nombreArchivo;

    public function __construct($excelPath, $tiendaId, $userId)
    {
        $this->excelPath = $excelPath;
        $this->tiendaId = $tiendaId;
        $this->userId = $userId;
        $this->nombreArchivo = 'Expediente_' . $userId . '_' . Str::random(8) . '_' . date('Ymd_His') . '.pdf';
    }

    public function handle()
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '0');

        $tiempoInicioTotal = microtime(true);

        try {
            $nombreBucket  = 'sistema-pv-imagenes-tienda';
            $anchoMaxFirma = 300;
            $anchoMaxLogo  = 220;

            $tiempoInicioExcel = microtime(true);
            $path = Storage::disk('local')->path($this->excelPath);

            // 1. Leer Excel
            $spreadsheetInput = IOFactory::load($path);
            $worksheetInput   = $spreadsheetInput->getActiveSheet();
            $highestRow       = $worksheetInput->getHighestRow();
            $ordenesRaw       = [];

            for ($row = 2; $row <= $highestRow; $row++) {
                $valor = $worksheetInput->getCell('A' . $row)->getValue();
                if ($valor !== null) {
                    $valor = trim(preg_replace('/[\s\x{00a0}]+/u', ' ', (string) $valor));
                    if ($valor !== '') $ordenesRaw[] = $valor;
                }
            }
            $spreadsheetInput->disconnectWorksheets();
            unset($spreadsheetInput, $worksheetInput);

            $ordenes = array_values(array_unique($ordenesRaw));
            if (empty($ordenes)) {
                $this->marcarErrorEnSesion('El archivo Excel no contiene órdenes legibles.');
                return;
            }

            $tienda     = DB::table('tienda')->where('idTienda', $this->tiendaId)->first();
            $logoBase64 = $this->prepararImagenBase64($tienda->logo ?? null, $anchoMaxLogo);
            $firmaRep   = $this->prepararImagenBase64($tienda->firma_representante ?? null, $anchoMaxLogo);

            $tiempoExcel = round(microtime(true) - $tiempoInicioExcel, 2);
            Log::info("⏱️ [REPORTE] Lectura de Excel: {$tiempoExcel} segundos");

            $tiempoInicioBD = microtime(true);

            // 3. Extraer Expedientes
            $expedientesRaw = DB::table('expedientetecnico as ex')
                ->leftJoin('tecnico as t', 'ex.fkTecnico', '=', 't.id')
                ->leftJoin('users as u', 't.nombre', '=', 'u.name')
                ->whereIn(DB::raw('SUBSTRING(ex.Orden, 1, 8)'), $ordenes)
                ->where('ex.fkTienda', $this->tiendaId)
                ->select([
                    'ex.id', DB::raw('SUBSTRING(ex.Orden, 1, 8) as Orden'), 'ex.virtual', 'ex.Tipo_orden', 'ex.Tipo_servicio',
                    'ex.NOMBRECLIENTE', 'ex.DIRECCION', 'ex.FECHAINSTALACION', 'ex.OBS', 'ex.firma_cliente',
                    'ex.SIGLASCENTRAL', 'ex.AREA', 't.nombre as tecnico_nombre', 't.codigo as tecnico_codigo', 'u.firma as firma_usuario'
                ])
                ->distinct()
                ->get();

            if ($expedientesRaw->isEmpty()) {
                $this->marcarErrorEnSesion('No se encontraron expedientes válidos para las órdenes enviadas.');
                return;
            }

            $expedientesPorOrden = $expedientesRaw->groupBy('Orden');

            // 4. Materiales Movimientos
            $materialesMovimientos = DB::table('movimientomateriales as mm')
                ->join('MaterialManoObra as mamo', 'mm.SKU', '=', 'mamo.SKU')
                ->join('expedientetecnico as et_mov', 'mm.fkExpediente', '=', 'et_mov.id')
                ->leftJoin('arbolmaterial as abmamo', 'mm.fkTecnologiaarbol', '=', 'abmamo.id')
                ->where('mamo.CATEGORIA', '!=', 'MANO DE OBRA')
                ->whereIn(DB::raw('SUBSTRING(et_mov.Orden, 1, 8)'), $ordenes)
                ->where('mm.fkTienda', $this->tiendaId)
                ->select([
                    'mm.fkExpediente as expediente_movimiento', 'mm.SKU', 'mm.cantidad', 'mm.serie',
                    'mamo.Descripcion', 'mamo.TIPO', 'mamo.CATEGORIA', 'mamo.unidadmedida',
                    'abmamo.nombre as TecnologiaCatalogo', DB::raw('SUBSTRING(et_mov.Orden, 1, 8) as codigo_orden'),
                    DB::raw('CAST(mamo.CATEGORIACOBRO AS DECIMAL(10,2)) as precio_unitario')
                ])->get();

            $expedienteLiderPorOrden = $expedientesPorOrden->map(function ($grupo) {
                return $grupo->firstWhere(fn($e) => !empty($e->firma_cliente)) ?? $grupo->first();
            });

            $materialesMovimientos = $materialesMovimientos->map(function ($item) use ($expedienteLiderPorOrden) {
                $lider = $expedienteLiderPorOrden->get($item->codigo_orden);
                if ($lider) { $item->fkExpediente = $lider->id; return $item; }
                return null;
            })->filter()->groupBy(fn($i) => $i->fkExpediente . '|' . $i->SKU . '|' . ($i->serie ?? ''))
              ->map(function ($grupo) {
                  $base = clone $grupo->first();
                  $base->cantidad = (float) $grupo->sum('cantidad');
                  return $base;
              })->values();

            $mapaSkuTecnologia  = $materialesMovimientos->whereNotNull('TecnologiaCatalogo')->groupBy('SKU')->map(fn($g) => $g->first()->TecnologiaCatalogo)->toArray();
            $tecnologiasPorOrden = $materialesMovimientos->whereNotNull('TecnologiaCatalogo')->groupBy('codigo_orden')->map(fn($g) => $g->pluck('TecnologiaCatalogo')->unique()->values());

            // 5. Mano de Obra
            $materialesPagoTecnicoRaw = DB::table('pagotecnico as pt')
                ->join('MaterialManoObra as mamo', 'pt.SKU', '=', 'mamo.SKU')
                ->join('expedientetecnico as et', 'et.Orden', '=', 'pt.Orden')
                ->join('movimientomateriales as mm', 'et.id', '=', 'mm.fkExpediente')
                ->join('tecnico as t', 'et.fkTecnico', '=', 't.id')
                ->leftJoin('arbolmaterial as abmamo', 'mm.fkTecnologiaarbol', '=', 'abmamo.id')
                ->where('et.ESTATUS', 'C')->where('mamo.categoria', 'MANO DE OBRA')
                ->whereIn(DB::raw('SUBSTRING(pt.Orden, 1, 8)'), $ordenes)
                ->where('pt.fkTienda', $this->tiendaId)
                ->select([
                    'mm.fkExpediente as expediente_movimiento', 't.nombre', 't.codigo as CENTRO_CODIGO',
                    'pt.Orden', 'pt.SKU', 'mamo.Descripcion', 'pt.cantidad', 'mamo.CATEGORIACOBRO as COSTO',
                    DB::raw('(pt.cantidad * mamo.CATEGORIACOBRO) as Subtotal'), 'mamo.TIPO', 'abmamo.nombre as TecnologiaCatalogo',
                    'mamo.CATEGORIA', 'mamo.unidadmedida', DB::raw('SUBSTRING(pt.Orden, 1, 8) as OrdenSub'),
                    DB::raw('NULL as serie'), DB::raw('CAST(mamo.CATEGORIACOBRO AS DECIMAL(10,2)) as precio_unitario')
                ])->distinct()->get();

            $materialesPagoTecnico = collect();
            foreach ($materialesPagoTecnicoRaw as $pago) {
                $lider = $expedienteLiderPorOrden->get($pago->OrdenSub);
                if (!$lider) continue;
                $pago->fkExpediente = $lider->id;
                $desc = mb_strtoupper($pago->Descripcion);

                if (str_contains($desc, 'DTH')) $pago->TecnologiaCatalogo = 'DTH';
                elseif (str_contains($desc, 'WTTX')) $pago->TecnologiaCatalogo = 'WTTx';
                elseif (str_contains($desc, 'GPON') || str_contains($desc, 'FIBRA')) $pago->TecnologiaCatalogo = 'GPON';
                elseif (str_contains($desc, 'HFC') || str_contains($desc, 'COAXIAL')) $pago->TecnologiaCatalogo = 'HFC';
                else {
                    $tecnologiasDeLaOrden = $tecnologiasPorOrden->get($pago->OrdenSub, collect());
                    if ($tecnologiasDeLaOrden->count() === 1) $pago->TecnologiaCatalogo = $tecnologiasDeLaOrden->first();
                    elseif ($tecnologiasDeLaOrden->count() > 1) {
                        foreach ($tecnologiasDeLaOrden as $tec) {
                            $clon = clone $pago;
                            $clon->TecnologiaCatalogo = $tec;
                            $materialesPagoTecnico->push($clon);
                        }
                        continue;
                    } else $pago->TecnologiaCatalogo = $mapaSkuTecnologia[$pago->SKU] ?? 'OTRAS_TECNOLOGIAS';
                }
                $materialesPagoTecnico->push($pago);
            }

            $tiempoBD = round(microtime(true) - $tiempoInicioBD, 2);
            Log::info("⏱️ [REPORTE] Consultas a Base de Datos: {$tiempoBD} segundos");

            $tiempoInicioProcesamiento = microtime(true);

            // 6. Consolidar
            $todosMateriales  = collect();
            $duplasExistentes = [];
            foreach ($materialesMovimientos as $item) {
                $duplasExistentes[$item->fkExpediente . '|' . $item->SKU] = true;
                $todosMateriales->push($item);
            }
            foreach ($materialesPagoTecnico as $itemPago) {
                $llave = $itemPago->fkExpediente . '|' . $itemPago->SKU;
                if (!isset($duplasExistentes[$llave])) {
                    $duplasExistentes[$llave] = true;
                    $todosMateriales->push($itemPago);
                }
            }

            // 7. Agrupar Tecnologías
            $tecnologiasAgrupadas     = [];
            $materialesPorTecnologia  = $todosMateriales->groupBy(fn($item) => empty($item->TecnologiaCatalogo) ? 'OTRAS_TECNOLOGIAS' : $item->TecnologiaCatalogo);

            foreach ($materialesPorTecnologia as $nombreTecnologia => $materialesTec) {
                $ordenesDeEstaTecnologia = $materialesTec->pluck('codigo_orden')->filter()->unique()->values()->all();
                if (empty($ordenesDeEstaTecnologia)) continue;

                $listaExpedientes = $expedientesRaw->whereIn('Orden', $ordenesDeEstaTecnologia)->values();
                if ($listaExpedientes->isEmpty()) continue;

                $materialesFisicosTec = $materialesTec->where('CATEGORIA', '!=', 'MANO DE OBRA');
                $manoObraTec          = $materialesTec->where('CATEGORIA', '==', 'MANO DE OBRA');

                $resumenMO = []; $totalMO = 0;
                foreach ($manoObraTec->groupBy('Descripcion') as $desc => $itemsMO) {
                    $cantTotal = (float) $itemsMO->sum('cantidad');
                    $precio    = (float) ($itemsMO->first()->precio_unitario ?? 0);
                    $subtotal  = $cantTotal * $precio;
                    $totalMO  += $subtotal;
                    $resumenMO[] = ['descripcion' => $desc, 'unidad' => empty($itemsMO->first()->unidadmedida) ? 'UNIDAD' : $itemsMO->first()->unidadmedida, 'cantidad' => $cantTotal, 'precio' => $precio, 'total' => $subtotal];
                }
                $iva = $totalMO * 0.12; $totalConIva = $totalMO + $iva;

                $materialesGlobales = [];
                foreach ($materialesFisicosTec->groupBy('SKU') as $sku => $itemsMat) {
                    $materialesGlobales[] = ['sku' => $sku, 'descripcion' => $itemsMat->first()->Descripcion, 'cantidad' => $itemsMat->sum('cantidad')];
                }

                $materialesPorExpediente = $materialesFisicosTec->groupBy('fkExpediente');
                $expedientesProcesados   = [];

                foreach ($listaExpedientes as $exp) {
                    $materialesDelExpediente = $materialesPorExpediente->get($exp->id, collect());
                    $manoObraDelExpediente   = $manoObraTec->where('fkExpediente', $exp->id);

                    if ($materialesDelExpediente->isEmpty() && $manoObraDelExpediente->isEmpty()) {
                        continue;
                    }

                    $firmaBase64 = $this->obtenerFirmaBase64($exp->firma_cliente ?? null, $exp->id, $nombreBucket, $anchoMaxFirma);
                    $firmaTecnicoUserBase64 = null;
                    if (!empty($exp->firma_usuario)) {
                        $firmaLimpia = trim($exp->firma_usuario);
                        $firmaTecnicoUserBase64 = str_starts_with($firmaLimpia, 'data:image') ? $firmaLimpia : 'data:image/png;base64,' . $firmaLimpia;
                    }

                    $expedientesProcesados[] = [
                        'id' => $exp->id, 'Orden' => $exp->Orden, 'virtual' => $exp->virtual, 'Tipo_orden' => $exp->Tipo_orden,
                        'Tipo_servicio' => $exp->Tipo_servicio, 'NOMBRECLIENTE' => $exp->NOMBRECLIENTE, 'DIRECCION' => $exp->DIRECCION,
                        'FECHAINSTALACION' => $exp->FECHAINSTALACION ? Carbon::parse($exp->FECHAINSTALACION)->format('d/m/Y H:i') : 'N/A',
                        'tecnico_nombre' => $exp->tecnico_nombre, 'tecnico_codigo' => $exp->tecnico_codigo,
                        'firma_base64' => $firmaBase64, 'firma_tecnico_user' => $firmaTecnicoUserBase64,
                        'materiales' => $materialesDelExpediente,
                    ];
                }

                if (empty($expedientesProcesados)) continue;

                $tecnologiasAgrupadas[$nombreTecnologia] = [
                    'nombre' => $nombreTecnologia, 'resumenManoObra' => $resumenMO, 'totalManoObra' => $totalMO,
                    'iva' => $iva, 'totalConIva' => $totalConIva, 'materialesGlobales' => $materialesGlobales, 'expedientes' => $expedientesProcesados
                ];
            }

            $tiempoProcesamiento = round(microtime(true) - $tiempoInicioProcesamiento, 2);
            Log::info("⏱️ [REPORTE] Procesamiento PHP y Firmas: {$tiempoProcesamiento} segundos");

            if (empty($tecnologiasAgrupadas)) {
                $this->marcarErrorEnSesion('No se generaron tecnologías con expedientes válidos.');
                return;
            }

            // ================================================================
            // 8. GENERACIÓN DE PDF (UNO POR TECNOLOGÍA, CON CHUNK INTERNO)
            // ================================================================
            $tiempoInicioPDF = microtime(true);
            $periodoDinamico = $this->calcularPeriodo($expedientesRaw);

            if (!Storage::disk('public')->exists('reportes')) {
                Storage::disk('public')->makeDirectory('reportes');
            }

            $archivosGenerados = [];
            $loteTamano        = 30; // órdenes por PDF parcial

            foreach ($tecnologiasAgrupadas as $nombreTec => $tec) {
                $lotes = array_chunk($tec['expedientes'], $loteTamano);

                foreach ($lotes as $idxLote => $loteExp) {
                    $tecParcial = $tec;
                    $tecParcial['expedientes'] = $loteExp;

                    $dataReporte = [
                        'fecha_reporte'       => Carbon::now()->format('d/m/Y'),
                        'representante'       => $tienda->representante ?? 'Distribuidor Autorizado',
                        'periodo_mes'         => $periodoDinamico,
                        'logo_tienda'         => $logoBase64,
                        'firma_representante' => $firmaRep,
                        'nombre_tienda'       => $tienda->Nombre ?? 'Distribuidor Autorizado',
                        'tecnologias'         => [$nombreTec => $tecParcial],
                    ];

                    $sufijo = count($lotes) > 1 ? '_lote' . ($idxLote + 1) : '';
                    $nombreParcial = 'Expediente_' . $this->userId . '_'
                        . Str::slug($nombreTec) . $sufijo . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf';

                    $tIniTec = microtime(true);

                    $pdf = Pdf::loadView('reportes.expediente_completo_pdf', $dataReporte)
                        ->setPaper('letter', 'portrait')
                        ->setOptions(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true]);

                    $pdf->save(storage_path('app/public/reportes/' . $nombreParcial));

                    $tTec = round(microtime(true) - $tIniTec, 2);
                    Log::info("⏱️ [REPORTE] PDF {$nombreTec}{$sufijo} ({$tTec}s) → {$nombreParcial}");

                    $archivosGenerados[] = $nombreParcial;

                    unset($pdf, $dataReporte, $tecParcial);
                    gc_collect_cycles();
                }
            }

            if (empty($archivosGenerados)) {
                throw new \RuntimeException('No se generó ningún PDF (sin expedientes válidos por tecnología).');
            }

            // 9. Empaquetar resultado final
            if (count($archivosGenerados) === 1) {
                $archivoFinal = $archivosGenerados[0];
            } else {
                $zipNombre = 'Expedientes_' . $this->userId . '_' . date('Ymd_His') . '_' . Str::random(6) . '.zip';
                $zipPath   = storage_path('app/public/reportes/' . $zipNombre);
                $zip = new \ZipArchive();
                if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                    throw new \RuntimeException('No se pudo crear el ZIP final.');
                }
                foreach ($archivosGenerados as $f) {
                    $zip->addFile(storage_path('app/public/reportes/' . $f), $f);
                }
                $zip->close();

                foreach ($archivosGenerados as $f) {
                    @unlink(storage_path('app/public/reportes/' . $f));
                }
                $archivoFinal = $zipNombre;
            }

            $this->nombreArchivo = $archivoFinal;

            $tiempoPDF = round(microtime(true) - $tiempoInicioPDF, 2);
            Log::info("⏱️ [REPORTE] Generación total PDFs: {$tiempoPDF} segundos ({$archivoFinal})");

            $tiempoTotal = round(microtime(true) - $tiempoInicioTotal, 2);
            Log::info("✅ [REPORTE] TIEMPO TOTAL DEL JOB: {$tiempoTotal} segundos");

            // Guardar registro en Base de Datos
            DB::table('reportes_generados')->insert([
                'fkUsuario'      => $this->userId,
                'nombre_archivo' => $this->nombreArchivo,
                'estado'         => 'completado',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // Guardar en Caché para el navegador
            Cache::put('reporte_status_' . $this->userId, [
                'estado'  => 'completado',
                'mensaje' => 'Reporte generado con éxito.',
                'archivo' => $this->nombreArchivo,
            ], now()->addMinutes(10));

            Storage::disk('local')->delete($this->excelPath);

        } catch (\Throwable $e) {
            Log::error('Job Falló: ' . $e->getMessage() . ' en línea ' . $e->getLine());
            Cache::put('reporte_status_' . $this->userId, [
                'estado'  => 'error',
                'mensaje' => 'Error: ' . $e->getMessage(),
            ], now()->addMinutes(10));
            throw $e; // relanza para que Laravel dispare failed()
        }
    }

private function marcarErrorEnSesion($mensaje)
{
    Log::warning("⚠️ [REPORTE] Job terminado sin PDF: {$mensaje}");

    Cache::put('reporte_status_' . $this->userId, [
        'estado'  => 'error',
        'mensaje' => $mensaje,
    ], now()->addMinutes(10));

    if (Storage::disk('local')->exists($this->excelPath)) {
        Storage::disk('local')->delete($this->excelPath);
    }
}

    // --- HELPERS ---
    private function prepararImagenBase64(?string $base64Puro, $maxAncho): ?string
    {
        if (empty($base64Puro)) return null;
        try {
            $binary = base64_decode(trim($base64Puro), true);
            if ($binary === false) return null;

            $img = Image::make($binary)
                ->resize($maxAncho, null, function ($c) { $c->aspectRatio(); $c->upsize(); })
                ->encode('jpg', 75);

            return 'data:image/jpeg;base64,' . base64_encode((string) $img);
        } catch (\Throwable $e) { return null; }
    }

    private function obtenerFirmaBase64(?string $urlFirma, $cacheKey, $nombreBucket, $maxAncho): ?string
    {
        if (empty($urlFirma)) return null;
        $cachePath = "firmas_cache/{$cacheKey}.bin";
        try {
            if (Storage::disk('local')->exists($cachePath)) {
                $binaryFirma = Storage::disk('local')->get($cachePath);
            } else {
                $pathBucket = $urlFirma;
                if (str_contains($urlFirma, $nombreBucket)) {
                    $pos = strpos($urlFirma, $nombreBucket);
                    $pathBucket = ltrim(substr($urlFirma, $pos + strlen($nombreBucket)), '/');
                } else {
                    $pathBucket = ltrim(parse_url($urlFirma, PHP_URL_PATH) ?? '', '/');
                }
                if (!Storage::disk('gcs_images')->exists($pathBucket)) return null;
                $binaryFirma = Storage::disk('gcs_images')->get($pathBucket);
                Storage::disk('local')->makeDirectory('firmas_cache');
                Storage::disk('local')->put($cachePath, $binaryFirma);
            }

            $img = Image::make($binaryFirma)
                ->resize(200, null, function ($c) { $c->aspectRatio(); $c->upsize(); })
                ->encode('jpg', 70);

            return 'data:image/jpeg;base64,' . base64_encode((string) $img);
        } catch (\Throwable $e) { return null; }
    }

    private function calcularPeriodo(Collection $expedientesRaw): string
    {
        $fechas = $expedientesRaw->pluck('FECHAINSTALACION')->filter()->map(fn($f) => Carbon::parse($f));
        if ($fechas->isEmpty()) return 'PERIODO DE ÓRDENES PROCESADAS';
        $min = $fechas->min(); $max = $fechas->max();
        if ($min->format('m-Y') === $max->format('m-Y')) {
            $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
            return "DEL {$min->day} AL {$max->day} DE " . strtoupper($meses[$min->month - 1]) . " DEL {$min->year}";
        }
        return "DEL " . $min->format('d/m/Y') . " AL " . $max->format('d/m/Y');
    }

    public function failed(\Throwable $exception)
    {
        Log::error('El Job falló de forma catastrófica: ' . $exception->getMessage());

        Cache::put('reporte_status_' . $this->userId, [
            'estado'  => 'error',
            'mensaje' => 'Error grave en el servidor: ' . $exception->getMessage(),
        ], now()->addMinutes(10));
    }
}