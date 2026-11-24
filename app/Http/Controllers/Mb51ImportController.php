<?php

namespace App\Http\Controllers;

use App\Models\Mb51Importacion;
use App\Services\Mb51CsvParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// =========================================================================
// TRAIT (Autómata y utilidades matemáticas)
// =========================================================================
trait AutomataMB51MamoTrait
{
    private static ?\Symfony\Component\ExpressionLanguage\ExpressionLanguage $exprLang = null;
    private static array $astCache = [];
    private static array $resCache = [];

    protected function precargarContexto(string $orden, string $fkTienda): array
    {
        $t0 = microtime(true);

        $itemsEta = DB::table('mb51_importaciones')
            ->where('fkTienda', $fkTienda)
            ->whereRaw("REGEXP_SUBSTR(referencia, '[0-9]{8}') = ?", [$orden])
            ->select(
                'material as SKU', 
                'centro as CENTRO', 
                'nombre_1', // <-- AGREGADO
                DB::raw("'N/A' as TIPO_DE_SERVICIO"), 
                DB::raw('SUM(cantidad) as Cantidad')
            )
            ->groupBy('material', 'centro', 'nombre_1') // <-- AGREGADO AL GROUP BY
            ->get();

        $skusEta = $itemsEta->pluck('SKU')->map(fn($s) => trim((string)$s))->unique()->values()->all();

        // Árbol completo
        $arbol = DB::table('treematerialescategoria as tm')
            ->leftJoin('treematerialescategoria as tmc', 'tm.padre_id', '=', 'tmc.id')
            ->where('tm.fkTienda', $fkTienda)
            ->select(
                'tm.SKU as sku', 'tm.padre_id', 'tm.id as id_nodo',
                'tmc.id as id_padre', 'tmc.SKU as sku_padre', 'tmc.nombre as nombre_padre',
                'tmc.minimo as minimo_padre', 'tmc.limite as limite_padre',
                'tmc.tipo as tipo_padre', 'tmc.valor as valor_padre'
            )->get();

        $mapaNodo = []; $mapaHermanos = []; $padreCategoriaPorSku = [];
        foreach ($arbol as $row) {
            $skuL = trim((string)$row->sku);
            $mapaNodo[$skuL] = $row;
            if ($row->padre_id !== null) {
                $mapaHermanos[$row->padre_id][] = $skuL;
            }
            if ($row->sku_padre) {
                $padreCategoriaPorSku[$skuL] = (object)[
                    'SKU' => $row->sku_padre, 'nombre' => $row->nombre_padre,
                    'minimo' => $row->minimo_padre, 'limite' => $row->limite_padre,
                    'tipo' => $row->tipo_padre, 'valor' => $row->valor_padre,
                ];
            }
        }

        // Relaciones
        $relacionesRaw = collect();
        $skufinalesRelevantes = [];
        if (!empty($skusEta)) {
            $disparadores = DB::table('material_relaciones')
                ->where('fkTienda', $fkTienda)->where('minimo', 1.0)
                ->whereIn('depende_SKU', $skusEta)->get();

            foreach ($disparadores as $disparador) {
                $skufinalesRelevantes[] = trim((string)$disparador->skufinal);
                if ((float)$disparador->maximo == 10000.0) {
                    $nodoDisparador = $mapaNodo[trim((string)$disparador->depende_SKU)] ?? null;
                    if ($nodoDisparador && $nodoDisparador->padre_id !== null) {
                        $hermanos = $mapaHermanos[$nodoDisparador->padre_id] ?? [];
                        if (!empty($hermanos)) {
                            $skfHermanos = DB::table('material_relaciones')
                                ->where('fkTienda', $fkTienda)
                                ->where(function($q) use ($hermanos) {
                                    $q->whereIn('SKU', $hermanos)->orWhereIn('depende_SKU', $hermanos);
                                })->pluck('skufinal')->map(fn($s) => trim((string)$s))->all();
                            $skufinalesRelevantes = array_merge($skufinalesRelevantes, $skfHermanos);
                        }
                    }
                }
            }
            $skufinalesRelevantes = array_values(array_unique(array_filter($skufinalesRelevantes)));
            if (!empty($skufinalesRelevantes)) {
                $relacionesRaw = DB::table('material_relaciones')
                    ->where('fkTienda', $fkTienda)
                    ->whereIn('skufinal', $skufinalesRelevantes)
                    ->orderBy('id', 'ASC')->get();
            }
        }

        $relPorDepende = []; $relPorSkufinal = []; $relPorSku = []; $relacionesPorSkufinalSinFiltro = [];
        foreach ($relacionesRaw as $r) {
            $dep = trim((string)$r->depende_SKU);
            $sku = trim((string)$r->SKU);
            $skf = trim((string)$r->skufinal);
            $relPorDepende[$dep][] = $r;
            $relPorSkufinal[$skf][] = $r;
            $relPorSku[$sku][] = $r;
            $relacionesPorSkufinalSinFiltro[$skf][] = $r;
        }
        $conteoSinFiltroPorSkufinal = array_map('count', $relacionesPorSkufinalSinFiltro);

        $etaPorSku = [];
        foreach ($itemsEta as $it) {
            $etaPorSku[trim((string)$it->SKU)][] = $it;
        }
        $itemsReportados = array_flip($skusEta);

        return compact(
            'itemsEta', 'skusEta', 'mapaNodo', 'mapaHermanos', 'padreCategoriaPorSku',
            'relPorDepende', 'relPorSkufinal', 'relPorSku',
            'relacionesPorSkufinalSinFiltro', 'conteoSinFiltroPorSkufinal',
            'etaPorSku', 'itemsReportados'
        );
    }

    private function sumarCantidades(array $skus, array $ctx): float
    {
        $t = 0.0;
        foreach ($skus as $s) {
            $s = trim((string)$s);
            if (isset($ctx['etaPorSku'][$s])) {
                foreach ($ctx['etaPorSku'][$s] as $row) {
                    $t += (float)$row->Cantidad;
                }
            }
        }
        return $t;
    }

    protected function getExpressionLanguage(): \Symfony\Component\ExpressionLanguage\ExpressionLanguage
    {
        if (self::$exprLang === null) {
            self::$exprLang = new \Symfony\Component\ExpressionLanguage\ExpressionLanguage(new \Symfony\Component\Cache\Adapter\ArrayAdapter());
            
            self::$exprLang->register('round', fn($v, $p = 0) => sprintf('round(%s, %s)', $v, $p), fn($vars, $v, $p = 0) => round((float)$v, (int)$p));
            self::$exprLang->register('floor', fn($v) => sprintf('floor(%s)', $v), fn($vars, $v) => floor((float)$v));
            self::$exprLang->register('ceil', fn($v) => sprintf('ceil(%s)', $v), fn($vars, $v) => ceil((float)$v));
            self::$exprLang->register('abs', fn($v) => sprintf('abs(%s)', $v), fn($vars, $v) => abs((float)$v));
            self::$exprLang->register('existe', fn($v) => sprintf('existe(%s)', $v), function ($vars, $v) { return ((float)$v > 0) ? 1 : 0; });
            self::$exprLang->register('siempre_positivo', fn($v) => sprintf('siempre_positivo(%s)', $v), function ($vars, $v) { return 1; });
            self::$exprLang->register('si_existe', fn($v) => sprintf('si_existe(%s)', $v), function ($vars, $v) { return ((float)$v > 0) ? $v : 0; });
            self::$exprLang->register('ambos_existen', fn($v1, $v2) => sprintf('ambos_existen(%s, %s)', $v1, $v2), function ($vars, $v1, $v2) { return (((float)$v1 > 0) && ((float)$v2 > 0)) ? 1 : 0; });
            self::$exprLang->register('solo_usado', fn($u, $v) => sprintf('solo_usado(%s, %s)', $u, $v), function ($vars, $u, $v) { return ((float)$u > 0 && (float)$v <= 0) ? 1 : 0; });
            self::$exprLang->register('solo_valor', fn($v, $u) => sprintf('solo_valor(%s, %s)', $v, $u), function ($vars, $v, $u) { return ((float)$v > 0 && (float)$u <= 0) ? 1 : 0; });
            self::$exprLang->register('ambos_existen_cero', fn($v, $u) => sprintf('ambos_existen_cero(%s, %s)', $v, $u), function ($vars, $v, $u) { return ((float)$v <= 0 && (float)$u <= 0) ? 1 : 0; });
            self::$exprLang->register('encontrar', fn($t, ...$p) => sprintf('encontrar(%s, %s)', $t, implode(', ', $p)), function ($vars, $t, ...$p) {
                $t = strtolower((string)$t);
                foreach ($p as $pal) { if ($pal !== '' && str_contains($t, strtolower((string)$pal))) return 1; }
                return 0;
            });
            self::$exprLang->register('max', fn(...$a) => sprintf('max(%s)', implode(', ', $a)), fn($vars, ...$a) => max(...$a));
            self::$exprLang->register('min', fn(...$a) => sprintf('min(%s)', implode(', ', $a)), fn($vars, ...$a) => min(...$a));
        }
        return self::$exprLang;
    }

    protected function evaluarFormulaexp(string $formula, array $variables)
    {
        $formula = strtolower(trim($formula));
        if ($formula === '') return 0;
        if (($variables['usado'] ?? null) == 1 && str_contains($formula, 'usado - 1')) return 0;

        $a = substr_count($formula, '('); $c = substr_count($formula, ')');
        if ($a > $c) $formula .= str_repeat(')', $a - $c);

        $hash = md5($formula . '|' . serialize($variables));
        if (isset(self::$resCache[$hash])) return self::$resCache[$hash];

        try {
            $expr = $this->getExpressionLanguage();
            return self::$resCache[$hash] = $expr->evaluate($formula, $variables);
        } catch (\Throwable $e) {
            Log::error('Error evaluando fórmula: ' . $e->getMessage(), ['formula' => $formula]);
            return self::$resCache[$hash] = 0;
        }
    }
}

// =========================================================================
// CONTROLADOR PRINCIPAL
// =========================================================================
class Mb51ImportController extends Controller
{
    use AutomataMB51MamoTrait;

    /* ---------- Vista principal ---------- */
    public function form()
    {
        return view('mb51.import');
    }

    /* ---------- Importación CSV ---------- */
    public function importar(Request $request, Mb51CsvParser $parser)
    {
        $request->validate(['archivo' => 'required|file|mimes:csv,txt|max:51200']);
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $parsed = $parser->parse($request->file('archivo')->getRealPath());
        $encabezado = $parsed['encabezado'];
        $filas = $parsed['filas'];

        $requeridas = ['Material', 'Referencia', 'Cantidad', 'Clase de movimiento', 'Texto de clase de movimiento', 'Centro'];
        $faltantes = array_diff($requeridas, $encabezado);
        if (!empty($faltantes)) {
            return back()->with('error', 'Faltan columnas obligatorias: ' . implode(', ', $faltantes));
        }

        $insertados = 0; $omitidos = 0; $keysArchivo = [];
        foreach ($filas as $fila) {
            $mapa = $this->mapearFila($fila);
            $key = Mb51Importacion::buildUniqueKey($mapa);
            $mapa['unique_key'] = $key;
            if (isset($keysArchivo[$key])) { $omitidos++; continue; }
            $keysArchivo[$key] = $mapa;
        }

        $existentes = [];
        foreach (array_chunk(array_keys($keysArchivo), 900) as $chunk) {
            $existentes = array_merge($existentes, Mb51Importacion::whereIn('unique_key', $chunk)->pluck('unique_key')->all());
        }
        $existentes = array_flip($existentes);

        $batch = []; $now = now();
        $fkTienda = session('user_fkTienda');
        
        foreach ($keysArchivo as $key => $data) {
            if (isset($existentes[$key])) { $omitidos++; continue; }
            $data['created_at'] = $now; 
            $data['updated_at'] = $now; 
            $data['fk_usuario'] = Auth::id();
            $data['fkTienda']   = $fkTienda; // <-- NUEVA LÍNEA
            $batch[] = $data;
            if (count($batch) >= 500) { $insertados += Mb51Importacion::insertOrIgnore($batch); $batch = []; gc_collect_cycles(); }
        }
        if (!empty($batch)) { $insertados += Mb51Importacion::insertOrIgnore($batch); }

        return back()->with('success', "Importación finalizada: {$insertados} insertados, {$omitidos} omitidos (duplicados).");
    }

    /* ---------- Descarga de formato MB51 SAP ---------- */
    public function descargarFormato()
    {
        $headers = [
            'Material', 'Texto breve de material', 'Referencia', 'Fe.contabilización',
            'Cantidad', 'Nombre del usuario', 'Clase de movimiento',
            'Texto de clase de movimiento', 'Documento material', 'Pedido',
            'Texto cab.documento', 'Nº reserva', 'Grafo', 'Importe ML',
            'Centro', 'Nombre 1', 'Almacén', 'Sociedad',
            'Fecha de entrada', 'Hora de entrada',
        ];
        $filename = 'Formato_MB51_SAP_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ',');
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

        private function limpiarSerie(?string $serie): string
    {
        if (empty($serie)) return '0';
        
        $s = trim($serie);
        $s = strtoupper($s);
        
        // 🔥 DETECCIÓN DE NOTACIÓN CIENTÍFICA (Ej: 1.42879E+11)
        if (preg_match('/^\d+(\.\d+)?E\+\d+$/i', $s)) {
            // Si Excel lo convirtió a científico, lo pasamos a número entero (float)
            // Esto puede perder los últimos dígitos, pero es mejor que fallar.
            $s = number_format((float) str_replace(',', '.', $s), 0, '', '');
        }

        $s = preg_replace('/OFF.*$/', '', $s); 
        $s = preg_replace('/HNN.*$/', '', $s);
        $s = preg_replace('/[^A-Z0-9]/', '', $s);
        
        if (in_array($s, ['', '0', 'NA', 'SN', 'SINSERIE'])) {
            return '0';
        }

        return $s;
    }

    /* ---------- Descarga de formato Pendientes de Justificar ---------- */
public function descargarPendientes()
{
    $headers = ['Material', 'Denominación', 'Número de serie', 'Centro', 'CANTIDAD'];
    
    return \Maatwebsite\Excel\Facades\Excel::download(
        new class($headers) implements FromArray, WithHeadings, WithColumnFormatting {
            public function __construct(private array $headers) {}
            
            public function array(): array { return []; } // Solo queremos los encabezados
            
            public function headings(): array { return $this->headers; }
            
            public function columnFormats(): array {
                // Forzar la columna C (Número de serie) a ser TEXTO puro
                return ['C' => NumberFormat::FORMAT_TEXT];
            }
        }, 
        'Formato_Pendientes_Justificar_' . date('Ymd_His') . '.xlsx'
    );
}

    /* ---------- Listado paginado ---------- */
    public function tabla(Request $request)
    {
        $q = Mb51Importacion::query();
        if ($request->filled('fechain')) $q->whereDate('created_at', '>=', $request->fechain);
        if ($request->filled('fechafin')) $q->whereDate('created_at', '<=', $request->fechafin);
        if ($request->filled('search')) {
            $s = trim($request->search);
            $q->where(function ($w) use ($s) {
                $w->where('material', 'like', "%{$s}%")
                  ->orWhere('referencia', 'like', "%{$s}%")
                  ->orWhere('texto_breve_material', 'like', "%{$s}%")
                  ->orWhere('centro', 'like', "%{$s}%")
                  ->orWhere('documento_material', 'like', "%{$s}%")
                  ->orWhere('pedido', 'like', "%{$s}%");
            });
        }
        $registros = $q->orderByDesc('id')->paginate(25)->withQueryString();
        return view('mb51._tabla', compact('registros'));
    }

    /* ---------- Reporte de Justificación (3 Hojas en Memoria) ---------- */
public function generarReporteJustificacion(Request $request)
{
    try {
        $request->validate([
            'archivo_csv'  => 'required|file|mimes:csv,txt,xlsx,xls',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $fkTienda = (int) session('user_fkTienda');
        $file = $request->file('archivo_csv');
        $ext  = strtolower($file->getClientOriginalExtension());

        $csvNombre = 'reportes_justificacion/upload_' . uniqid() . '.csv';

        if (in_array($ext, ['xlsx', 'xls'])) {
            // Convertir el Excel a CSV en el servidor
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $destino = storage_path('app/' . $csvNombre);
            @mkdir(dirname($destino), 0777, true);
            
            $out = fopen($destino, 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 para que Excel respete acentos
            foreach ($sheet->toArray() as $row) {
                fputcsv($out, $row, ',');
            }
            fclose($out);
        } else {
            // Si ya es CSV, se guarda directo
            $file->storeAs('reportes_justificacion', basename($csvNombre), 'local');
        }

        $jobId = 'just_' . uniqid();

        // dispatchSync ejecuta el job en el momento
        \App\Jobs\GenerarReporteJustificacionJob::dispatchSync(
            $jobId,
            $csvNombre,
            $fkTienda,
            $request->fecha_inicio . ' 00:00:00',
            $request->fecha_fin    . ' 23:59:59'
        );

        return response()->json([
            'status'  => 'ok',
            'job_id'  => $jobId,
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['error' => 'Validación fallida: ' . implode(', ', $e->validator->errors()->all())], 422);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error del Servidor: ' . $e->getMessage()], 500);
    }
}
public function progresoReporte($jobId)
{
    $file = storage_path("app/reportes_justificacion/progress_{$jobId}.json");
    if (!file_exists($file)) {
        return response()->json(['estado' => 'desconocido'], 404);
    }
    return response()->json(json_decode(file_get_contents($file), true));
}

public function descargarReporte($jobId)
{
    $file = storage_path("app/reportes_justificacion/reporte_{$jobId}.xlsx");
    if (!file_exists($file)) {
        return response()->json(['error' => 'Archivo no encontrado'], 404);
    }
    return response()->download($file, "Reporte_Justificacion_{$jobId}.xlsx")->deleteFileAfterSend(true);
}

    /* ---------- Helper para generar el Excel ---------- */
    private function descargarExcelJustificacion($hoja1, $hoja2, $hoja3)
    {
        return Excel::download(new class($hoja1, $hoja2, $hoja3) implements WithMultipleSheets {
            private $h1, $h2, $h3;
            public function __construct($h1, $h2, $h3) { $this->h1 = $h1; $this->h2 = $h2; $this->h3 = $h3; }

            public function sheets(): array
            {
                return [
                    new class($this->h1) implements FromArray, WithTitle, WithHeadings {
                        private $data;
                        public function __construct($data) { $this->data = $data; }
                        public function array(): array { return $this->data; }
                        public function headings(): array { return array_keys($this->data[0] ?? []); }
                        public function title(): string { return '1. Analisis MB51'; }
                    },
                    new class($this->h2) implements FromArray, WithTitle, WithHeadings {
                        private $data;
                        public function __construct($data) { $this->data = $data; }
                        public function array(): array { return $this->data; }
                        public function headings(): array { return array_keys($this->data[0] ?? []); }
                        public function title(): string { return '2. Discrepancia ETA'; }
                    },
                    new class($this->h3) implements FromArray, WithTitle, WithHeadings {
                        private $data;
                        public function __construct($data) { $this->data = $data; }
                        public function array(): array { return $this->data; }
                        public function headings(): array { return array_keys($this->data[0] ?? []); }
                        public function title(): string { return '3. Discrepancia Movimientos'; }
                    }
                ];
            }
        }, 'Reporte_Justificacion_' . date('Ymd_His') . '.xlsx');
    }

    /* ---------- Helpers ---------- */
    private function determinarEstatus($esperado, $reportado) {
        if ($reportado == 0) return 'NO REPORTADO';
        if ($reportado < $esperado) return 'FALTANTE';
        if ($reportado > $esperado) return 'EXCESO';
        return 'CORRECTO';
    }

    private function mapearFila(array $fila): array
    {
        return [
            'material'               => $fila['Material'] ?? '',
            'texto_breve_material'   => $fila['Texto breve de material'] ?? '',
            'referencia'             => $fila['Referencia'] ?? '',
            'fe_contabilizacion'     => $this->parseFecha($fila['Fe.contabilización'] ?? ''),
            'cantidad'               => $this->parseNumero($fila['Cantidad'] ?? ''),
            'nombre_usuario'         => $fila['Nombre del usuario'] ?? '',
            'clase_movimiento'       => $fila['Clase de movimiento'] ?? '',
            'texto_clase_movimiento' => $fila['Texto de clase de movimiento'] ?? '',
            'documento_material'     => $fila['Documento material'] ?? '',
            'pedido'                 => $fila['Pedido'] ?? '',
            'texto_cab_documento'    => $fila['Texto cab.documento'] ?? '',
            'num_reserva'            => $fila['Nº reserva'] ?? '',
            'grafo'                  => $fila['Grafo'] ?? '',
            'importe_ml'             => $this->parseNumero($fila['Importe ML'] ?? ''),
            'centro'                 => $fila['Centro'] ?? '',
            'nombre_1'               => $fila['Nombre 1'] ?? '',
            'almacen'                => $fila['Almacén'] ?? '',
            'sociedad'               => $fila['Sociedad'] ?? '',
            'fecha_entrada'          => $this->parseFecha($fila['Fecha de entrada'] ?? ''),
            'hora_entrada'           => $fila['Hora de entrada'] ?? '',
        ];
    }

    private function parseFecha(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') return null;
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd.m.Y', 'Y/m/d', 'd/m/y'] as $f) {
            try {
                $d = Carbon::createFromFormat($f, $raw);
                if ($d && $d->format($f) === $raw) return $d->format('Y-m-d');
            } catch (\Exception) {}
        }
        try { return Carbon::parse($raw)->format('Y-m-d'); } catch (\Exception) { return null; }
    }

    private function parseNumero(?string $raw): float
    {
        $raw = trim((string) $raw);
        if ($raw === '') return 0.0;
        $neg = false;
        if (preg_match('/^\((.*)\)$/', $raw, $m)) { $neg = true; $raw = $m[1]; }
        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            if (strrpos($raw, ',') > strrpos($raw, '.')) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        } elseif (str_contains($raw, ',')) {
            $raw = str_replace(',', '.', $raw);
        }
        $raw = preg_replace('/[^\d\.\-]/', '', $raw);
        $val = (float) $raw;
        return $neg ? -$val : $val;
    }
}