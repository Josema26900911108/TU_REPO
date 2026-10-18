<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; // Asegúrate de que esta línea esté presente

use App\Models\Materialmanoobra;
use Illuminate\Http\Request;
use App\Http\Requests\StorePersonaRequest;
use App\Models\Eta;
use App\Models\Material_relaciones;
use App\Models\Persona;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use MathParser\StdMathParser;
use MathParser\Interpreting\Evaluator;
use  App\Models\Caracteristica;
use Illuminate\Support\Facades\Cache;

class etadirectController extends Controller
{
    public function __construct()
    {
        // Aplicar middleware de permisos
        $this->middleware('permission:ver-eta', ['only' => ['index']]);
        $this->middleware('permission:crear-eta', ['only' => ['create', 'store', 'exist']]);
        $this->middleware('permission:editar-eta', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-eta', ['only' => ['destroy']]);

    }

    public function index()
    {

                if(!Auth::check()){
            return redirect()->route('login');
        }
        return view('ETA.index');
    }

public function reporteDiscrepancias(Request $request)
{
    // 1. Validar las fechas que vienen del formulario
    $request->validate([
        'fechaincio' => 'required|date',
        'fechafin'   => 'required|date',
    ]);

    // Ajustar horas para cubrir el día completo en el filtro BETWEEN
    $fechaInicio = $request->fechaincio . ' 00:00:00';
    $fechaFin    = $request->fechafin . ' 23:59:59';

    // 2. Tu consulta original con la inyección segura de la descripción al final
    $query = "
SELECT 
    DATE_FORMAT(m_e.Fecha_Auditoria, '%Y-%m-%d') AS Fecha_Auditoria,
    IFNULL(t.nombre, 'Técnico No Registrado') AS Tecnico_Nombre,
    IFNULL(t.codigo, m_e.Tecnico_Final) AS Tecnico_Codigo,
    m_e.Orden_Plataforma AS Orden,
    m_e.Serie_Original AS Serie,
    m_e.SKU,
    IFNULL(mamo.Descripcion, 'Sin Descripción en Catálogo') AS Descripcion,
    m_e.Cantidad_Interna,
    m_e.Cantidad_Externa,
    (m_e.Cantidad_Externa - m_e.Cantidad_Interna) AS Complemento_Requerido,
    /* 🛠️ CORRECCIÓN EN LA EVALUACIÓN DE DISCREPANCIAS */
    CASE 
        WHEN m_e.Cantidad_Interna = m_e.Cantidad_Externa THEN 'Cantidades Correctas'
        WHEN m_e.Cantidad_Interna > 0 AND m_e.Cantidad_Externa = 0 THEN 'Solo en Interno (Falta reportar en ETA)'
        WHEN m_e.Cantidad_Interna = 0 AND m_e.Cantidad_Externa > 0 THEN 'Solo en Externo (Falta registrar internamente)'
        ELSE 'Discrepancia: Ajustar cantidades'
    END AS Accion_Reporte
FROM (
    /* ========================================================== */
    /* ENTRADA DE DATOS UNIFICADA Y LIMPIA                        */
    /* ========================================================== */
    SELECT 
        LEFT(REGEXP_REPLACE(TRIM(e.Orden), '[^0-9]', ''), 8) AS Orden_Plataforma,
        e.SKU,
        IF(e.serie_homologada = 'SIN_SERIE', '0', e.serie_homologada) AS Serie_Original,
        IFNULL(m.Cantidad_Interna, 0) AS Cantidad_Interna,
        e.Cantidad_Externa,
        COALESCE(e.EMPLEADO, m.Tecnico_Interno) AS Tecnico_Final,
        e.fecha_ref AS Fecha_Auditoria
    FROM (
        SELECT 
            LEFT(REGEXP_REPLACE(TRIM(Orden), '[^0-9]', ''), 8) AS Orden, 
            SKU,
            EMPLEADO,
            CASE WHEN Serie IN ('0', 0, 'N/A', 'S/N', 'SIN SERIE', '') OR Serie IS NULL THEN 'SIN_SERIE' ELSE TRIM(Serie) END AS serie_homologada,
            SUM(Cantidad) AS Cantidad_Externa,
            MIN(created_at) AS fecha_ref
        FROM ETA 
        GROUP BY LEFT(REGEXP_REPLACE(TRIM(Orden), '[^0-9]', ''), 8), SKU, EMPLEADO, serie_homologada
    ) e
    LEFT JOIN (
        SELECT 
            LEFT(REGEXP_REPLACE(TRIM(ex_sub.Orden), '[^0-9]', ''), 8) AS Orden, 
            m_sub.SKU,
            MAX(COALESCE(m_sub.fkTecnico, ex_sub.fkTecnico)) AS Tecnico_Interno,
            CASE WHEN m_sub.serie IN ('0', 0, 'N/A', 'S/N', 'SIN SERIE', '') OR m_sub.serie IS NULL THEN 'SIN_SERIE' ELSE TRIM(m_sub.serie) END AS serie_homologada,
            SUM(m_sub.cantidad) AS Cantidad_Interna
        FROM movimientomateriales m_sub
        INNER JOIN expedientetecnico ex_sub ON m_sub.fkExpediente = ex_sub.id
        GROUP BY LEFT(REGEXP_REPLACE(TRIM(ex_sub.Orden), '[^0-9]', ''), 8), m_sub.SKU, serie_homologada
    ) m ON e.Orden = m.Orden AND e.SKU = m.SKU AND e.serie_homologada = m.serie_homologada

    UNION ALL

    SELECT 
        LEFT(REGEXP_REPLACE(TRIM(m.Orden), '[^0-9]', ''), 8) AS Orden_Plataforma,
        m.SKU,
        IF(m.serie_homologada = 'SIN_SERIE', '0', m.serie_homologada) AS Serie_Original,
        m.Cantidad_Interna,
        0 AS Cantidad_Externa,
        m.Tecnico_Interno AS Tecnico_Final,
        m.fecha_ref AS Fecha_Auditoria
    FROM (
        SELECT 
            LEFT(REGEXP_REPLACE(TRIM(ex_sub.Orden), '[^0-9]', ''), 8) AS Orden, 
            m_sub.SKU,
            MAX(COALESCE(m_sub.fkTecnico, ex_sub.fkTecnico)) AS Tecnico_Interno,
            CASE WHEN m_sub.serie IN ('0', 0, 'N/A', 'S/N', 'SIN SERIE', '') OR m_sub.serie IS NULL THEN 'SIN_SERIE' ELSE TRIM(m_sub.serie) END AS serie_homologada,
            SUM(m_sub.cantidad) AS Cantidad_Interna,
            MIN(COALESCE(ex_sub.FECHAINSTALACION, m_sub.created_at)) AS fecha_ref
        FROM movimientomateriales m_sub
        INNER JOIN expedientetecnico ex_sub ON m_sub.fkExpediente = ex_sub.id
        GROUP BY LEFT(REGEXP_REPLACE(TRIM(ex_sub.Orden), '[^0-9]', ''), 8), m_sub.SKU, serie_homologada
    ) m
    LEFT JOIN (
        SELECT 
            LEFT(REGEXP_REPLACE(TRIM(Orden), '[^0-9]', ''), 8) AS Orden, 
            SKU,
            CASE WHEN Serie IN ('0', 0, 'N/A', 'S/N', 'SIN SERIE', '') OR Serie IS NULL THEN 'SIN_SERIE' ELSE TRIM(Serie) END AS serie_homologada
        FROM ETA
        GROUP BY LEFT(REGEXP_REPLACE(TRIM(Orden), '[^0-9]', ''), 8), SKU, serie_homologada
    ) e ON m.Orden = e.Orden AND m.SKU = e.SKU AND m.serie_homologada = e.serie_homologada
    WHERE e.SKU IS NULL
) m_e
LEFT JOIN tecnico t ON (m_e.Tecnico_Final = t.id OR m_e.Tecnico_Final = t.codigo)

LEFT JOIN (
    SELECT SKU, MAX(Descripcion) AS Descripcion 
    FROM MaterialManoObra 
    GROUP BY SKU
) mamo ON m_e.SKU = mamo.SKU
        WHERE m_e.Fecha_Auditoria BETWEEN :fecha_inicio AND :fecha_fin
          AND m_e.Cantidad_Interna != m_e.Cantidad_Externa and (m_e.Cantidad_Externa - m_e.Cantidad_Interna)<>0
        ORDER BY Accion_Reporte ASC, m_e.Orden_Plataforma ASC
    ";

    $resultados = DB::select($query, [
        'fecha_inicio' => $fechaInicio,
        'fecha_fin'    => $fechaFin
    ]);

    // 3. Generar la respuesta en formato de archivo CSV descargable
    $fileName = 'Reporte_Discrepancias_Materiales_' . date('Ymd_His') . '.csv';
    
    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $callback = function() use($resultados) {
        $file = fopen('php://output', 'w');
        
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($file, [
            'Técnico Nombre', 'Técnico Código', 'Orden', 'Serie', 'SKU', 'Descripcion',
            'Cantidad Interna', 'Cantidad Externa', 'Complemento Requerido', 'Acción Reporte'
        ]);

        foreach ($resultados as $fila) {
            fputcsv($file, [
                $fila->Fecha_Auditoria,
                $fila->Tecnico_Nombre,
                $fila->Tecnico_Codigo,
                $fila->Orden,
                $fila->Serie,
                $fila->SKU,
                $fila->Descripcion, // Agregado correctamente a las columnas del CSV
                $fila->Cantidad_Interna,
                $fila->Cantidad_Externa,
                $fila->Complemento_Requerido,
                $fila->Accion_Reporte
            ]);
        }
        
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}




    public function exportarExcel(Request $request)
{
    // 1. Capturar exactamente los mismos filtros que tienes en tu consulta de la tabla
    $fkTienda = session('user_fkTienda');
    $fechain = $request->input('fechain', now()->subDays(3)->format('Y-m-d'));
    $fechafin = $request->input('fechafin', now()->addDay()->format('Y-m-d'));
    $search = $request->input('search');
    $idTecnico = $request->input('id');

    // 2. Consulta idéntica sin paginar (Trae todos los filtrados de golpe)
    $query = Eta::where('fkTienda', $fkTienda)
        ->whereBetween('created_at', [$fechain . ' 00:00:00', $fechafin . ' 23:59:59']);

    if ($idTecnico) {
        $query->where('EMPLEADO', $idTecnico);
    }

    if (!empty($search)) {
        $query->where(function($q) use ($search) {
            $q->where('Orden', 'LIKE', "%{$search}%")
              ->orWhere('SKU', 'LIKE', "%{$search}%")
              ->orWhere('Descripcion', 'LIKE', "%{$search}%")
              ->orWhere('Serie', 'LIKE', "%{$search}%")
              ->orWhere('EMPLEADO', 'LIKE', "%{$search}%");
        });
    }

    // 3. Configurar cabeceras de descarga HTTP para Excel/CSV
    $fileName = 'Reporte_ETA_' . date('Y-m-d_H-i') . '.csv';
    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    // 4. Generar el archivo en streaming línea por línea para cuidar la RAM
    $callback = function() use($query) {
        $file = fopen('php://output', 'w');
        
        // Agregar BOM UTF-8 para que Excel reconozca correctamente las tildes y caracteres especiales
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Cabeceras del Excel columnas
        fputcsv($file, ['Orden', 'SKU', 'Descripcion', 'Cantidad', 'Serie', 'MAC1', 'MAC2', 'MAC3', 'Centro', 'Empleado', 'Fecha']);

        // Procesar los registros en bloques de 1000 para no agotar memoria (Chunking)
        $query->chunk(1000, function($registros) use($file) {
            foreach ($registros as $row) {
                fputcsv($file, [
                    $row->Orden,
                    $row->SKU,
                    $row->Descripcion,
                    $row->Cantidad,
                    $row->Serie,
                    $row->MAC1,
                    $row->MAC2,
                    $row->MAC3,
                    $row->CENTRO,
                    $row->EMPLEADO,
                    date('d-m-Y', strtotime($row->created_at))
                ]);
            }
        });

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}


public function fetchrelacionEta(Request $request)
{
      DB::connection()->disableQueryLog();
      
    try {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $fechain = $request->input('fechain', now()->subDays(3)->format('Y-m-d'));
        $fechafin = $request->input('fechafin', now()->addDay()->format('Y-m-d'));
        
        // NUEVO: Capturar el parámetro de búsqueda
        $search = $request->input('search'); 

        // Consulta base con la relación de la tienda
        $query = Eta::with('tienda')
            ->where('fkTienda', $fkTienda)
            ->whereBetween('created_at', [$fechain . ' 00:00:00', $fechafin . ' 23:59:59']);

        if ($request->has('id') && !empty($request->input('id'))) {
            $query->where('EMPLEADO', $request->input('id'));
        }

        // NUEVO: Si el usuario escribió algo, filtramos de manera global en la Base de Datos
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('Orden', 'LIKE', "%{$search}%")
                  ->orWhere('SKU', 'LIKE', "%{$search}%")
                  ->orWhere('Descripcion', 'LIKE', "%{$search}%")
                  ->orWhere('MAC1', 'LIKE', "%{$search}%")
                  ->orWhere('MAC2', 'LIKE', "%{$search}%")    
                ->orWhere('MAC3', 'LIKE', "%{$search}%")    
                  ->orWhere('Serie', 'LIKE', "%{$search}%")
                  ->orWhere('EMPLEADO', 'LIKE', "%{$search}%");
            });
        }

        // Paginamos los resultados ya filtrados de forma limpia
        $eta = $query->paginate(15);

        if ($request->ajax()) {
            return view('ETA.tabla.etatable', compact('eta'))->render();
        }

        return view('ETA.index', compact('eta'));

    } catch (\Exception $e) {
        return response()->json(['error' => 'Error al filtrar: ' . $e->getMessage()], 500);
    }
}



public function reporteTecnicos()
{
    return DB::table('movimiento_materiales')
        ->select(
            'contrata as tecnico',
            'fkMateriales',
            DB::raw('SUM(CASE WHEN clase_movimiento = "251" THEN cantidad ELSE -cantidad END) as saldo_pendiente'),
            DB::raw('MIN(created_at) as fecha_entrega_mas_antigua')
        )
        ->whereIn('clase_movimiento', ['261','251', '252', '221','311','641','101']) // Salida, Devolución, Consumo
        ->where('fkTienda', session('user_fkTienda'))
        ->groupBy('contrata', 'fkMateriales')
        ->having('saldo_pendiente', '>', 0)
        ->get();
}

    public function create()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $materialmanoobra = Materialmanoobra::all();
        return view('materialmanoobra.create', compact('materialmanoobra'));
    }

public function show(){
                    if(!Auth::check()){
            return redirect()->route('login');
        }

    $materialmanoobra = Materialmanoobra::all();
}


public function importarMAMO(Request $request)
{
    DB::connection()->disableQueryLog();
    if(!Auth::check()){
        return redirect()->route('login');
    }

    $fkTienda = session('user_fkTienda');
    
    set_time_limit(0); 
    ini_set('memory_limit', '512M');

    $request->validate([
        'archivo' => 'required|file|mimes:csv,txt',
    ]);

    $file = fopen($request->file('archivo')->getRealPath(), 'r');
    
    $delimiter = ","; // Cambiar a "\t" si tu archivo usa tabulaciones
    $encabezado = fgetcsv($file, 0, $delimiter);

    // 1. 🧹 LIMPIEZA INMEDIATA DE ENCABEZADOS (Remueve BOM UTF-8 y espacios)
    if (!empty($encabezado)) {
        $encabezado = preg_replace('/[\x{00EF}\x{00BB}\x{00BF}\x{FEFF}]/u', '', $encabezado);
        $encabezado = array_map('trim', $encabezado);
    }

    $insertados = 0;
    $omitidos = 0;
    
    $batchSize = 500; 
    $batchData = [];
    $now = now();

    // Función interna para estandarizar strings vacíos y codificación
    $limpiarTexto = function($valor) {
        if ($valor === null) return '';
        $valor = trim($valor);
        if ($valor === "'" || $valor === "") {
            return '';
        }
        return mb_convert_encoding($valor, 'UTF-8', 'ISO-8859-1');
    };

    try {
        while (($linea = fgetcsv($file, 0, $delimiter)) !== false) {
            if (count($encabezado) !== count($linea)) {
                $omitidos++;
                continue;
            }

            $data = array_combine($encabezado, $linea);

            if (empty($data['Cantidad']) || empty($data['Orden']) || empty($data['SKU'])) {
                $omitidos++;
                continue;
            }

            // 2. 🗓️ VALIDACIÓN DE FECHA ULTRA-FLEXIBLE
            $fechaRaw = isset($data['created_at']) ? trim($data['created_at']) : null;
            $fecha = null;

            if ($fechaRaw) {
                try {
                    $fechaRawLimpia = str_replace("'", "", $fechaRaw); 
                    $fecha = \Carbon\Carbon::createFromFormat('d/m/Y', trim($fechaRawLimpia))->format('Y-m-d');
                } catch (\Exception $e) {
                    $fecha = null;
                }
            }

            if (!$fecha) {
                $fecha = \Carbon\Carbon::now()->format('Y-m-d');
            }

            $orden    = trim($data['Orden']);
            $sku      = trim($data['SKU']);
            $cantidad = trim($data['Cantidad']);
            $serie    = $limpiarTexto($data['Serie']);
            $mac1     = $limpiarTexto($data['MAC1']);
            $mac2     = $limpiarTexto($data['MAC2']);
            $mac3     = $limpiarTexto($data['MAC3']);

            // 3. 🔑 LLAVE ÚNICA ABSOLUTA DE LA FILA (Convertida a MD5 para evitar fallos de formato)
            $stringLlave = "{$orden}|{$sku}|{$cantidad}|{$fecha}|{$serie}|{$mac1}|{$mac2}|{$mac3}";
            $uniqueKey = md5($stringLlave);

            // Evitar duplicados idénticos dentro del mismo archivo
            if (isset($batchData[$uniqueKey])) {
                $omitidos++;
                continue;
            }

            $batchData[$uniqueKey] = [
                'Orden'            => $orden,
                'SKU'              => $sku,
                'Descripcion'      => $limpiarTexto($data['Descripcion']),
                'Cantidad'         => $cantidad,
                'Serie'            => $serie,
                'MAC1'             => $mac1,
                'MAC2'             => $mac2,
                'MAC3'             => $mac3,
                'TIPO_DE_SERVICIO' => $limpiarTexto($data['TIPO_DE_SERVICIO']),
                'TIPO_DE_ORDEN'    => $limpiarTexto($data['TIPO_DE_ORDEN']),
                'CENTRO'           => $limpiarTexto($data['CENTRO']),
                'EMPLEADO'         => $limpiarTexto($data['EMPLEADO']),
                'Naturaleza'       => 'S',
                'Status'           => 'Pe',
                'fkTienda'         => $fkTienda,
                'created_at'       => $fecha,
                'updated_at'       => $now,
            ];

            // 4. PROCESAMIENTO CUANDO SE ALCANZA EL BATCH SIZE
            if (count($batchData) >= $batchSize) {
                $insertados += $this->procesarYFiltrarLote($batchData, $limpiarTexto);
                $batchData = [];
                gc_collect_cycles(); 
            }
        }

        // 5. 🎯 PROCESAR EL RESIDUO FINAL (Aquí es donde entran tus archivos de menos de 500 filas)
        if (!empty($batchData)) {
            $insertados += $this->procesarYFiltrarLote($batchData, $limpiarTexto);
        }

        fclose($file);

        // Calculamos las filas omitidas reales
        $totalLineasProcesadas = $insertados + $omitidos;

        return back()->with('success', "Importación finalizada con éxito: Se guardaron {$insertados} registros nuevos. Se omitieron duplicados.");

    } catch (\Exception $e) {
        if (is_resource($file)) {
            fclose($file);
        }
        return back()->with('error', 'Error crítico en Cloud: ' . $e->getMessage());
    }
}



/**
 * 🛡️ Compara el lote actual con los registros reales de la base de datos
 */
/**
 * Helper para procesar, consultar BD e insertar el lote de forma limpia
 */
private function procesarYFiltrarLote(array $batchData, callable $limpiarTexto): int
{
    $enviarABD = [];
    $ordenesLote = array_column($batchData, 'Orden');

    // 💡 SOLUCIÓN: Cambiado al nombre real de tu tabla 'ETA'
    $existentesBD = DB::table('ETA')
        ->whereIn('Orden', $ordenesLote)
        ->get(['Orden', 'SKU', 'Cantidad', 'created_at', 'Serie', 'MAC1', 'MAC2', 'MAC3']);

    // Mapeamos lo que ya existe en la base de datos usando el formato MD5
    $mapaBD = [];
    foreach ($existentesBD as $reg) {
        $fechaBD = \Carbon\Carbon::parse($reg->created_at)->format('Y-m-d');
        
        // Formateamos igual que la memoria para comparar de forma idéntica
        $llaveBD = md5("{$reg->Orden}|{$reg->SKU}|{$reg->Cantidad}|{$fechaBD}|" . $limpiarTexto($reg->Serie) . "|" . $limpiarTexto($reg->MAC1) . "|" . $limpiarTexto($reg->MAC2) . "|" . $limpiarTexto($reg->MAC3));
        $mapaBD[$llaveBD] = true;
    }

    // Comparamos el lote contra la BD
    foreach ($batchData as $keyMemoria => $valoresFila) {
        if (!isset($mapaBD[$keyMemoria])) {
            $enviarABD[] = $valoresFila;
        }
    }

    // Insertamos únicamente lo que pasó el filtro de duplicados
    if (!empty($enviarABD)) {
        DB::transaction(function () use ($enviarABD) {
            $this->insertOrUpdateBatch($enviarABD);
        });
        return count($enviarABD);
    }

    return 0;
}

private function ejecutarLogicaInterna($orden, $item, &$procesados, &$rastro, $limpiarCache = false)
{
    // Variables estáticas para persistencia en memoria RAM de consultas repetitivas de base de datos
    static $cacheNormales = [];
    static $cachePadres   = [];

    // 🚀 INTERCEPTOR MASIVO: Si se activa el reseteo por cambio de orden, vaciamos las cachés de SQL
    if ($limpiarCache === true || $orden === 'RESET_CACHE' || $item === null) {
        $cacheNormales = [];
        $cachePadres   = [];
        return; 
    }

    $centrosEspeciales = ["'MGG845", "'MGG830", "'G888", "'MGG840","'MJG845", "'MJG830", "'MJG840","'M7G845", "'M7G830", "'M7G840"];
    $patronG8 = "G888";

    $esEspecial = false;
    if (str_contains($item->CENTRO, $patronG8)) {
        $esEspecial = true;
    }

    $centroLimpio = "'".$item->TIPO_DE_SERVICIO . substr($item->CENTRO, 1, 4);
    $centrosEspeciales = ["'MGG845", "'MGG830", "'MGG840","'MJG845", "'MJG830", "'MJG840","'M7G845", "'M7G830", "'M7G840"];
    $patronG8 = "G8";

    if (str_contains($centroLimpio, $patronG8)) {
        $esEspecial = true;
    }

    if ($esEspecial) {
        $skuConComilla = $item->TIPO_DE_SERVICIO . substr($item->CENTRO, 1, 100) . trim($item->SKU);

        $especifica = Material_relaciones::selectRaw("*, 1 as prioridad")
            ->where('skufinal', 'like', '%' . $skuConComilla . '%')
            ->where('fkTienda', session('user_fkTienda'))
            ->where('minimo', 1);

        $SKUPAT = trim($item->SKU);
        
        $general = Material_relaciones::selectRaw("*, 2 as prioridad")
            ->where('depende_SKU', $item->SKU)
            ->where('minimo', 1)
            ->where('fkTienda', session('user_fkTienda'))
            ->where(function($q) use ($centrosEspeciales, $SKUPAT) {
                $q->where(function($subQuery) use ($centrosEspeciales) {
                    foreach ($centrosEspeciales as $index => $ce) {
                        if ($index === 0) {
                            $subQuery->where('skufinal', 'like', '%' . $ce . '%');
                        } else {
                            $subQuery->orWhere('skufinal', 'like', '%' . $ce . '%');
                        }
                    }
                });
            });

        $ignorarpatron = Material_relaciones::selectRaw("*, 3 as prioridad")
            ->where('skufinal', 'like', '%G888' . trim($item->SKU) . '%')
            ->where('fkTienda', session('user_fkTienda'))
            ->where('minimo', 1);

        $generalTOTAL = Material_relaciones::selectRaw("*, 2 as prioridad")
            ->where('depende_SKU', $item->SKU)
            ->where('fkTienda', session('user_fkTienda'))
            ->where('minimo', 1)
            ->where(function($q) use ($patronG8, $item) {
                $q->where('skufinal', 'not like', $patronG8 . '%')
                  ->where('skufinal', 'not like', $item->SKU . '%')
                  ->where('skufinal', '<>', "'G888" . $item->SKU);
            });

        $relaciones = $especifica->union($general)->union($generalTOTAL)
            ->union($ignorarpatron)
            ->orderBy('prioridad', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()             
            ->unique('id');     

        $resultadoFinal = $relaciones->when($relaciones->contains('prioridad', 1), function ($coleccion) {
            return $coleccion->reject(function ($registro) {
                return $registro->prioridad == 3;
            });
        });

    } else {
        $llaveNormal = "normal_" . $item->SKU;
        if (!isset($cacheNormales[$llaveNormal])) {
            $cacheNormales[$llaveNormal] = Material_relaciones::where('depende_SKU', $item->SKU)
                ->where('minimo', 1)
                ->where('fkTienda', session('user_fkTienda'))
                ->where(function($q) use ($centrosEspeciales, $patronG8) {
                    foreach ($centrosEspeciales as $ce) {
                        $q->where('skufinal', 'not like', '%' . $ce . '%');
                    }
                    $q->where('skufinal', 'not like', '%' . $patronG8 . '%');
                })
                ->orderBy('id', 'ASC')
                ->get();
        }
        $resultadoFinal = $cacheNormales[$llaveNormal];
    }

    if ($resultadoFinal->isEmpty()) {
        return;
    }

    foreach ($resultadoFinal as $relacion) {
        $conteo = Material_relaciones::where('depende_SKU', $relacion->SKU)
            ->whereExists(function ($q) use ($orden) {
                $q->select(DB::raw(1))
                  ->from('ETA')
                  ->whereColumn('ETA.SKU', 'material_relaciones.SKU')
                  ->where('ETA.Orden', $orden)
                  ->where('ETA.fkTienda', session('user_fkTienda'));
            })->count();

        // 🚀 OMITIDO EL BLOQUE IF MAXIMO == 10000:
        // El cálculo pesado de sumatoria de categorías ya lo realiza el nuevo query en AutomataRecursivo.

        $llavePadre = "padre_" . $relacion->SKU;
        if (!isset($cachePadres[$llavePadre])) {
            $cachePadres[$llavePadre] = DB::table('treematerialescategoria as tm')
                ->join('treematerialescategoria as tmc', 'tm.padre_id', '=', 'tmc.id')
                ->where('tm.SKU', $relacion->SKU)
                ->where('tmc.fkTienda', session('user_fkTienda'))
                ->select('tmc.SKU', 'tmc.nombre', 'tmc.minimo', 'tmc.limite', 'tmc.tipo', 'tmc.valor')
                ->first();
        }
        $padre = $cachePadres[$llavePadre];

        if ($padre) {
            $this->AutomataRecursivo(
                $relacion->SKU,      
                $orden,               
                $conteo,              
                $item->Cantidad,  // Entra directo, la recursión calculará si requiere sumatoria grupal    
                $relacion->maximo ?? 0,
                $relacion->minimo ?? 0,
                $relacion->formula ?? '',
                $procesados,
                $relacion->tipo_relacion,
                0,                    
                $rastro,
                $item->SKU,           
                $relacion->SKU,       
                0,                    
                $relacion->skufinal,
                $padre,
                $item->CENTRO,
                $relacion->nombre
            );
        }
    }
}


private function ejecutarLogicaInternaVista($orden, $item, &$procesados, &$rastro, array $itemsSimulados, $tipoOrden)
{
    $centrosEspeciales = ["'MGG845", "'MGG830", "'G888", "'MGG840","'MJG845", "'MJG830", "'MJG840","'M7G845", "'M7G830", "'M7G840"];
    $patronG8 = "'G8";

    // 💡 CORRECCIÓN DE SINTAXIS: Accedemos como array usando las llaves del Front-end
    $centroLimpio = $tipoOrden . ($item['centro'] ?? $item['CENTRO'] ?? "'G888");
    $itemSKU      = trim($item['sku'] ?? $item['SKU'] ?? '');
    $itemCantidad = (float)($item['cantidad'] ?? $item['Cantidad'] ?? 0);

    // Forzamos la detección de centros especiales
    $esEspecial = false;
    if (strpos($centroLimpio, $patronG8) !== false) {
        $esEspecial = true;
    }

    if ($esEspecial) {
        // CONSULTA ESPECÍFICA (Prioridad 1)
        $especifica = Material_relaciones::selectRaw("*, 1 as prioridad")
            ->where('skufinal', 'like', '%' . trim($centroLimpio) . $itemSKU . '%')
            ->where('fkTienda', session('user_fkTienda'))
            ->where('minimo', '>=', 1);

        // CONSULTA GENERAL (Prioridad 2)
        $general = Material_relaciones::selectRaw("*, 2 as prioridad")
            ->where('depende_SKU', $itemSKU)
            ->where('fkTienda', session('user_fkTienda'))
            ->where('minimo', '>=', 1)
            ->where(function($q) use ($centrosEspeciales, $patronG8) {
                $q->where('skufinal', 'like', '%' . $patronG8 . '%');
                foreach ($centrosEspeciales as $ce) {
                    $q->orWhere('skufinal', 'like', '%' . $ce . '%');
                }
            });

        $generalTOTAL = Material_relaciones::selectRaw("*, 2 as prioridad")
            ->where('depende_SKU', $itemSKU)
            ->where('fkTienda', session('user_fkTienda'))
            ->where('minimo', '>=', 1)
            ->where(function($q) use ($patronG8, $itemSKU) {
                $q->where('skufinal', 'not like', $patronG8 . '%');
                $q->where('skufinal', 'not like', $itemSKU . '%');
            });

        $relaciones = $especifica->unionAll($general)->unionAll($generalTOTAL)
            ->orderBy('prioridad', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();

    } else {
        // Centros normales
        $relaciones = Material_relaciones::where('depende_SKU', $itemSKU)
            ->where('fkTienda', session('user_fkTienda'))
            ->where('minimo', '>=', 1)
            ->where(function($q) use ($centrosEspeciales, $patronG8) {
                foreach ($centrosEspeciales as $ce) {
                    $q->where('skufinal', 'not like', '%' . $ce . '%');
                }
                $q->where('skufinal', 'not like', '%' . $patronG8 . '%');
            })
            ->orderBy('id', 'ASC')
            ->get();
    }

    if ($relaciones->isEmpty()) {
        return;
    }

    // Normalización de la colección virtual
    $coleccionVirtual = collect($itemsSimulados)->map(function($i) {
        $obj = (array)$i;
        return (object)[
            'SKU'      => trim($obj['SKU'] ?? $obj['sku'] ?? $obj['arraysku'] ?? ''),
            'Cantidad' => (float)($obj['Cantidad'] ?? $obj['cantidad'] ?? $obj['arraycantidad'] ?? 0),
            'CENTRO'   => $obj['CENTRO'] ?? $obj['centro'] ?? "'G888"
        ];
    });

    foreach ($relaciones as $relacion) {
        
        $conteo = $coleccionVirtual->where('SKU', $relacion->SKU)->count();

        // Acumulado de categoría (Máximo 10000)
        // CORRECCIÓN 4: Acumulado de categoría (Máximo 10000) resuelto con el envío real de la vista
        if ($relacion->maximo == 10000) {
            // 1. Buscamos los SKUs que pertenecen al mismo nodo jerárquico (Categoría común)
            $skusEnMismaCategoria = DB::table('treematerialescategoria as tm')
                ->join('treematerialescategoria as tmc', 'tm.padre_id', '=', 'tmc.id')
                ->where('tmc.fkTienda', session('user_fkTienda'))
                ->whereIn('tmc.SKU', function($q) use ($itemSKU) {
                    $q->select('tmc2.SKU')
                      ->from('treematerialescategoria as tm2')
                      ->join('treematerialescategoria as tmc2', 'tm2.padre_id', '=', 'tmc2.id')
                      ->where('tm2.SKU', $itemSKU)
                      ->where('tmc2.fkTienda', session('user_fkTienda'));
                })->pluck('tm.SKU')->toArray();

            // 2. 🎯 SUMA REAL DE LA VISTA: 
            // Sumamos lo que ya estaba en la memoria virtual para esa categoría, asegurando
            // que incluya el valor del nuevo ítem que el técnico está intentando agregar
            $itemCantidad = $coleccionVirtual->whereIn('SKU', $skusEnMismaCategoria)->sum('Cantidad');
            
            // Si por alguna razón la colección virtual aún no tenía el ítem integrado en el conteo,
            // garantizamos que el objeto del bucle mantenga el peso de la cantidad enviada:
            if ($itemCantidad == 0) {
                $itemCantidad = (float)($item['cantidad'] ?? $item['Cantidad'] ?? 0);
            }

        }

        $padre = DB::table('treematerialescategoria as tm')
            ->join('treematerialescategoria as tmc', 'tm.padre_id', '=', 'tmc.id')
            ->where('tm.SKU', $relacion->SKU)
            ->where('tmc.fkTienda', session('user_fkTienda'))
            ->select('tmc.SKU', 'tmc.nombre', 'tmc.minimo', 'tmc.limite', 'tmc.tipo', 'tmc.valor')
            ->first();

        if ($padre) {
            // Creamos un objeto limpio para pasar a la recursión y que no rompa la firma técnica
            $itemObjeto = (object)[
                'CENTRO'   => $centroLimpio,
                'SKU'      => $itemSKU,
                'Cantidad' => $itemCantidad
            ];

            $this->AutomataRecursivoVista(
                $relacion->SKU, $orden, $conteo, $itemCantidad, $relacion->maximo ?? 0, $relacion->minimo ?? 0, $relacion->formula ?? '',
                $procesados, $relacion->tipo_relacion, $itemCantidad, $rastro, $itemSKU, $relacion->SKU, 0, $relacion->skufinal, $padre, $centroLimpio, $relacion->nombre,
                $coleccionVirtual->toArray(),
                $itemObjeto // ← Enviamos el ítem actual normalizado como objeto
            );
        }
    }
}


public function AutomataRecursivo(
    string $skuActual,
    int $orden,
    int $recuento,
    float $valor,
    float $maximo,
    float $minimo,
    string $formula,
    array &$procesados,
    string $tipoRelacion,
    float $val,
    array &$rastro,
    string $skuOrigen,
    string $skuOrigenraiz,
    int $nivel = 0,
    string $skufinal,
    ?object &$padre,
    string $Centro,
    string $mensaj=''
) {
    try {
        // 🚀 MEMORIA RAM ESTÁTICA EXCLUSIVA DEL AUTÓMATA (Evita duplicar queries pesados en MySQL)
        static $cacheTotalesRecursivo   = [];
        static $cachePadresCalculo      = [];
        static $cachePadresCategoria    = [];
        static $cacheRelacionesNodo     = [];

        // Interceptor de control para vaciar cachés de BD entre cambios de órdenes del lote
        if ($skuActual === 'RESET_CACHE' || $orden === 0) {
            $cacheTotalesRecursivo   = [];
            $cachePadresCalculo      = [];
            $cachePadresCategoria    = [];
            $cacheRelacionesNodo     = [];
            return;
        }

        // 🛑 CASO BASE 1
        if ($valor <= 0 && $nivel == 0) {
            return;
        }
        $cantidad = substr_count($skuOrigen, ".");

        $clave = $orden . '_' . $skuActual. '_' .$skuOrigen . '_' . $skufinal.'_'.$tipoRelacion;
        $CANT = substr_count($clave, "40144661001856");
        if($CANT > 0){
            $a = "25188580_34028679_1021133_102113334028679";
        }

        if (isset($procesados[$clave])) {
            return; // 🚫 Ya fue procesado este Orden + SKU
        }

        // 🚀 OPTIMIZACIÓN SUPREMA O(1): Buscamos por LLAVE en lugar de in_array pesados.
        // El rastro NO se borra, sigue acumulándose de forma nativa e intacta.
        if (isset($rastro[$clave])) {
            return; // 🔁 ciclo detectado de forma instantánea
        }

        // Guardamos la marca usando la clave como LLAVE del arreglo asociativo
        $rastro[$clave] = true;

        if($nivel > ($recuento + 1) && $tipoRelacion == 'calculo' ){
            return;
        }



    // =========================================================================
    // 🌳 1. DETERMINACIÓN DEL PIVOTE Y CÁLCULO DE "VALOR" (Fijado contra Binary Error)
    // =========================================================================
    $llaveValorRAM = "valor_{$orden}_{$skuActual}_{$skuOrigen}_{$maximo}_{$minimo}";

    if (!isset($cacheTotalesRecursivo[$llaveValorRAM])) {

        $totalValor = DB::selectOne("
            SELECT COALESCE(SUM(e.Cantidad), 0) AS total_valor_calculado
            FROM ETA e
            INNER JOIN treematerialescategoria tm 
               ON CAST(e.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(tm.SKU AS CHAR) COLLATE utf8mb4_unicode_ci
            WHERE e.Orden = ? 
              AND e.fkTienda = ?
              AND tm.fkTienda = ?
              AND (
                  -- 🔹 ESCENARIO 1: Se cumple la regla y el SKU NO tiene puntos (Busca hermanos)
                  (
                      ? = 10000.0 AND ? = 10000.0 
                      AND INSTR(CAST(? AS CHAR), '.') = 0 
                      AND tm.padre_id = (
                          SELECT sub_tm.padre_id 
                          FROM treematerialescategoria sub_tm 
                          WHERE CAST(sub_tm.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(? AS CHAR) COLLATE utf8mb4_unicode_ci 
                            AND sub_tm.fkTienda = ? 
                          LIMIT 1
                      )
                  )
                  OR
                  -- 🔹 ESCENARIO 2: El SKU origen YA es la categoría padre (Tiene puntos ej: '04.05')
                  (
                      INSTR(CAST(? AS CHAR), '.') > 0 
                      AND tm.padre_id = (
                          SELECT sub_tmc.id 
                          FROM treematerialescategoria sub_tmc 
                          WHERE CAST(sub_tmc.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(? AS CHAR) COLLATE utf8mb4_unicode_ci 
                            AND sub_tmc.fkTienda = ? 
                          LIMIT 1
                      )
                  )
                  OR
                  -- 🔹 ESCENARIO 3: Es un material individual normal estándar (Suma solo el SKU origen)
                  (
                      NOT (? = 10000.0 AND ? = 10000.0 AND INSTR(CAST(? AS CHAR), '.') = 0)
                      AND INSTR(CAST(? AS CHAR), '.') = 0 
                      AND CAST(tm.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(? AS CHAR) COLLATE utf8mb4_unicode_ci
                  )
              )
        ", [
            $orden, session('user_fkTienda'), session('user_fkTienda'),
            $maximo, $minimo, $skuOrigen, $skuOrigen, session('user_fkTienda'), // Escenario 1
            $skuOrigen, $skuOrigen, session('user_fkTienda'), // Escenario 2
            $maximo, $minimo, $skuOrigen, $skuOrigen, $skuOrigen // Escenario 3
        ]);

        $cacheTotalesRecursivo[$llaveValorRAM] = $totalValor->total_valor_calculado ?? 0;
    }
    $valor = $cacheTotalesRecursivo[$llaveValorRAM];


    // =========================================================================
    // 🌳 2. DETERMINACIÓN DEL PIVOTE Y CÁLCULO DE "USADO" (Fijado contra Binary Error)
    // =========================================================================
    $llaveUsadoRAM = "usado_{$orden}_{$skuActual}_{$maximo}";

    if (!isset($cacheTotalesRecursivo[$llaveUsadoRAM])) {

        $totalUsado = DB::selectOne("
            SELECT COALESCE(SUM(e.Cantidad), 0) AS total_usado_calculado
            FROM ETA e
            INNER JOIN treematerialescategoria tm 
               ON CAST(e.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(tm.SKU AS CHAR) COLLATE utf8mb4_unicode_ci
            WHERE e.Orden = ? 
              AND e.fkTienda = ?
              AND tm.fkTienda = ?
              AND (
                  -- 🔹 ESCENARIO 1: Tiene puntos (ej: '04.03') -> Busca directo por la categoría del padre
                  (
                      INSTR(CAST(? AS CHAR), '.') > 0 
                      AND tm.padre_id = (
                          SELECT sub_tmc.id 
                          FROM treematerialescategoria sub_tmc 
                          WHERE CAST(sub_tmc.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(? AS CHAR) COLLATE utf8mb4_unicode_ci 
                            AND sub_tmc.fkTienda = ? 
                          LIMIT 1
                      )
                  )
                  OR
                  -- 🔹 ESCENARIO 2: NO tiene puntos pero maximo = 10000 (Es un hijo que debe sumar a sus hermanos)
                  (
                      INSTR(CAST(? AS CHAR), '.') = 0 
                      AND ? = 10000.0
                      AND tm.padre_id = (
                          SELECT sub_tm.padre_id 
                          FROM treematerialescategoria sub_tm 
                          WHERE CAST(sub_tm.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(? AS CHAR) COLLATE utf8mb4_unicode_ci 
                            AND sub_tm.fkTienda = ? 
                          LIMIT 1
                      )
                  )
                  OR
                  -- 🔹 ESCENARIO 3: Es un material individual normal estándar (Suma solo el SKU actual)
                  (
                      INSTR(CAST(? AS CHAR), '.') = 0 
                      AND ? <> 10000.0
                      AND CAST(tm.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(? AS CHAR) COLLATE utf8mb4_unicode_ci
                  )
              )
        ", [
            $orden, session('user_fkTienda'), session('user_fkTienda'),
            $skuActual, $skuActual, session('user_fkTienda'), // Escenario 1
            $skuActual, $maximo, $skuActual, session('user_fkTienda'), // Escenario 2
            $skuActual, $maximo, $skuActual
        ]);

        $cacheTotalesRecursivo[$llaveUsadoRAM] = $totalUsado->total_usado_calculado ?? 0;
    }
    $usado = $cacheTotalesRecursivo[$llaveUsadoRAM];



        $variables = [
            'minimo' => $minimo ?? 0,
            'maximo' => $maximo ?? 0,
            'valor'  => $valor ?? 0,
            'usado'  => $usado ?? 0,
            'total'  => $nivel ?? 0,
            'valant' => $val ?? 0,
        ];

        if($clave == "25542286_02.15_34033641_02.1534033641UNIDAD_requiere"){
            $a = "25188580_34028673_34028677_102113334028679";
        }

        $resultado = 0;
        $resultado = $this->evaluarFormulaexp($formula, $variables);

        $resultadoMostrar = $resultado;
        $resultado = $resultado == 20000 ? 0 : $resultado;

        if($resultado > 0 || $resultado < 0) {
            if ($tipoRelacion == 'requiere' || $tipoRelacion == 'incompatible') {
                $padrerel = DB::selectOne("
                    SELECT distinct e.SKU FROM ETA e
                    inner join material_relaciones mr on e.SKU=mr.SKU where e.Orden=? and e.SKU=? and mr.fkTienda=? ;
                ", [$orden, $skuActual, session('user_fkTienda')]);

                if($resultado <> 0){
                    $nodo = (object)[
                        'Orden'         => $orden,
                        'SKU_Origen'    => $skuActual,
                        'SKU_Destino'   => $skuOrigen,
                        'msj'           => $mensaj,
                        'ValorEntrada'  => $valor,
                        'Resultado'     => $resultado == 10000 ? $val : $resultado,
                        'TipoRelacion'  => $resultado < 0 ? $tipoRelacion . " - Exceso" : $tipoRelacion,
                        'formula'       => $formula,
                        'Nivel'         => $nivel,
                        'children'      => [],
                        'skuOrigenraiz' => $skufinal,
                        'CENTRO'        => $Centro,
                        'claveunica'     => $clave,
                        'NOMBRE_Destino' => $padre->nombre ?? "SKU materia analizado ".$tipoRelacion." ".$skuActual,
                    ];
                    $procesados[$clave] = $nodo;
                }
                elseif($padrerel){
                    $nodo = (object)[
                        'Orden'         => $orden,
                        'SKU_Origen'    => $skuActual,
                        'SKU_Destino'   => $skuOrigen,
                        'msj'           => $mensaj,
                        'ValorEntrada'  => $valor,
                        'Resultado'     => $resultado == 10000 ? $val : $resultado,
                        'TipoRelacion'  => $resultado < 0 ? $tipoRelacion . " - Exceso" : $tipoRelacion,
                        'formula'       => $formula,
                        'Nivel'         => $nivel,
                        'children'      => [],
                        'skuOrigenraiz' => $skufinal,
                        'CENTRO'        => $Centro,
                        'claveunica'     => $clave,
                        'NOMBRE_Destino' => $padre->nombre ?? "SKU materia analizado ".$tipoRelacion." ".$skuActual,
                    ];
                    $procesados[$clave] = $nodo;
                }
            }
        }

        // 🚀 CACHÉ RECURSIVA: Relaciones de fin de nodo para anular subconsultas redundantes a la BD
        $llaveNodoFinal = "nodo_" . $skufinal;
        if (!isset($cacheRelacionesNodo[$llaveNodoFinal])) {
            $cacheRelacionesNodo[$llaveNodoFinal] = (object)[
                'relaciones' => Material_relaciones::where('skufinal', $skufinal)->orderBy('id', 'ASC')->get(),
                'conteo'     => Material_relaciones::where('skufinal', $skufinal)->count()
            ];
        }

        $relaciones = $cacheRelacionesNodo[$llaveNodoFinal]->relaciones;
        $conteo     = $cacheRelacionesNodo[$llaveNodoFinal]->conteo;

        // 🧠 CACHÉ DE INSTANCIA LOCAL: Mapea los SKUs reales reportados en esta orden para búsqueda O(1)
        // Evita hacer consultas SQL dentro del foreach de relaciones
        $llaveOrdenItems = "items_orden_" . $orden;
        if (!isset($cacheItemsReportadosOrden[$llaveOrdenItems])) {
            $cacheItemsReportadosOrden[$llaveOrdenItems] = DB::table('ETA')
                ->where('Orden', $orden)
                ->where('fkTienda', session('user_fkTienda'))
                ->pluck('SKU')
                ->map(function($item) { return trim($item); })
                ->flip() // Convierte los valores en llaves para usar isset() a velocidad luz
                ->toArray();
        }
        $itemsReportados = $cacheItemsReportadosOrden[$llaveOrdenItems];

        $iteracionActual = 0; // Contador para identificar el último nodo obligatorio

        foreach ($relaciones as $relacion) {

        
            $iteracionActual++;
            $esUltimoNodo = ($iteracionActual === (int)$conteo);

                        // ------------------------------------------------------------------------
            // 🎯 NUEVO MOTOR DE FILTRADO Y VALIDACIÓN DE PADRES E HIJOS
            // ------------------------------------------------------------------------
            if (!$esUltimoNodo) {
                $skuMatchEncontrado = false;
                $dependeSkuMatchEncontrado = false;

                // --- EVALUACIÓN DE CAMPO: SKU ---
                $skuAEvaluar = trim($relacion->SKU);
                if (isset($relacion->maximo) && (int)$relacion->maximo === 10000) {
                    // Obliga a buscar por el nodo Padre si es Hijo (contiene punto)
                    if (strpos($skuAEvaluar, '.') !== false) {
                        $partesSku = explode('.', $skuAEvaluar);
                        $padrePrefix = $partesSku[0]; // Extrae la raíz antes del punto
                        
                        // Buscamos si existe algún ítem reportado que empiece con la raíz del padre
                        foreach (array_keys($itemsReportados) as $itemReportadoReal) {
                            if (strpos($itemReportadoReal, $padrePrefix) === 0) {
                                $skuMatchEncontrado = true;
                                break;
                            }
                        }
                    } else {
                        // Si no tiene punto se evalúa de forma directa en el mapa O(1)
                        $skuMatchEncontrado = isset($itemsReportados[$skuAEvaluar]);
                    }
                } else {
                    // Comportamiento normal sin bandera de fuerza
                    $skuMatchEncontrado = isset($itemsReportados[$skuAEvaluar]);
                }

                // --- EVALUACIÓN DE CAMPO: depende_SKU ---
                $dependeSkuAEvaluar = trim($relacion->depende_SKU);
                if (!empty($dependeSkuAEvaluar)) {
                    if (isset($relacion->minimo) && (int)$relacion->minimo === 10000) {
                        // Obliga a buscar por el nodo Padre si depende_SKU contiene un punto
                        if (strpos($dependeSkuAEvaluar, '.') !== false) {
                            $partesDepende = explode('.', $dependeSkuAEvaluar);
                            $padreDependePrefix = $partesDepende[0];
                            
                            foreach (array_keys($itemsReportados) as $itemReportadoReal) {
                                if (strpos($itemReportadoReal, $padreDependePrefix) === 0) {
                                    $dependeSkuMatchEncontrado = true;
                                    break;
                                }
                            }
                        } else {
                            $dependeSkuMatchEncontrado = isset($itemsReportados[$dependeSkuAEvaluar]);
                        }
                    } else {
                        $dependeSkuMatchEncontrado = isset($itemsReportados[$dependeSkuAEvaluar]);
                    }
                } else {
                    // Si depende_SKU viene vacío de base de datos, no interfiere negativamente
                    $dependeSkuMatchEncontrado = false;
                }

                // 🚫 REGLA DE SALTO (NODO NO REPORTADO): 
                // Si ninguno de los dos SKUs hace match con la orden, nos saltamos este nodo
                if (!$skuMatchEncontrado && !$dependeSkuMatchEncontrado) {
                    continue; 
                }
            }
            // ------------------------------------------------------------------------

            $claveHijo = $orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion;
            
            if($orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion == "25658138_34028704_34028710_40144661001856_calculo"){
                $a = $orden.''.$relacion->SKU.''.$relacion->depende_SKU.''.$relacion->skufinal.''.$relacion->tipo_relacion;
            }
            
            // 🚀 BÚSQUEDA INSTANTÁNEA: Validación contra el rastro asociativo global intacto por LLAVE
            if (isset($rastro[$claveHijo])) {
                continue;
            }
            
            $aSKUFILTRO = $clave;
            $tipoRelacion = $relacion->tipo_relacion;
            if($aSKUFILTRO == $orden.''.$relacion->SKU.''.$relacion->depende_SKU.''.$relacion->skufinal.''.$relacion->tipo_relacion){
                $a = "25188580_34028673_34028677_102113334028679";
                $claveHijoFalso = $orden.''.$relacion->SKU.''.$relacion->depende_SKU.'_'.$relacion->skufinal;
                $rastro[$claveHijoFalso] = true;
                continue; // 🚫 Evitar procesar el mismo nodo en esta rama
            }
            
            $SKUSSS = $relacion->SKU;
            if($tipoRelacion == "calculo"){
                $llaveCalculoPadre = "calc_{$orden}_{$relacion->SKU}";
                if (!isset($cachePadresCalculo[$llaveCalculoPadre])) {
                    $cachePadresCalculo[$llaveCalculoPadre] = DB::selectOne("
                        SELECT distinct e.SKU FROM ETA e
                        inner join material_relaciones mr on e.SKU=mr.depende_SKU where e.Orden=? and mr.depende_SKU=? and mr.fkTienda=?;
                    ", [$orden, $relacion->SKU, session('user_fkTienda')]);
                }

                $padreCalculo = $cachePadresCalculo[$llaveCalculoPadre];
                if (!$padreCalculo) {
                    $cantidad = substr_count($relacion->depende_SKU, ".");
                    if($cantidad == 0){
                        $cantidad = substr_count($relacion->SKU, ".");
                        if($cantidad == 0){
                            continue;
                        }
                    }
                }
            }



            // 1️⃣ Obtener SKU padre (Optimizado en Caché Estática por SKU)
            $llavePadreCat = $relacion->SKU;
            if (!isset($cachePadresCategoria[$llavePadreCat])) {
                $cachePadresCategoria[$llavePadreCat] = DB::selectOne("
                    SELECT tmc.SKU, tmc.nombre, tmc.minimo, tmc.limite, tmc.tipo, tmc.valor
                    FROM treematerialescategoria tm
                    INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
                    WHERE tm.SKU = ? AND tmc.fkTienda = ? LIMIT 1
                ", [$relacion->SKU, session('user_fkTienda')]);
            }
            $padreObjeto = $cachePadresCategoria[$llavePadreCat];
            
            // 🛑 CASO BASE 2
            if (!$padreObjeto) {
                continue;
            }
            
            if($aSKUFILTRO == "25298449_34025712_4018238_401823834028673_calculo"){
                $a = "25188580_34028673_34028677_102113334028679";
            }
            
            // 🔁 LLAMADA RECURSIVA INTEGRAL NATIVA
            $this->AutomataRecursivo(
                $relacion->SKU,
                $orden,
                $conteo,
                $resultadoMostrar = is_null($resultadoMostrar) ? 0 : (($resultadoMostrar == 20000) ? $val : $resultadoMostrar),
                $relacion->maximo   ?? 0,
                $relacion->minimo   ?? 0,
                $relacion->formula,
                $procesados,
                $relacion->tipo_relacion,
                $resultadoMostrar = is_null($resultadoMostrar) ? 0 : (($resultadoMostrar == 20000) ? $val : $resultadoMostrar),
                $rastro, // Pasado acumulativo intacto
                $relacion->depende_SKU,
                $skuOrigenraiz,
                $nivel + 1,
                $relacion->skufinal,
                $padreObjeto,
                $Centro,
                $relacion->nombre
            );
        }

} catch (\Exception $e) {
Log::error('Error crítico en AutomataRecursivo: ' . $e->getMessage());
}
}

public function AutomataRecursivoRR(
    string $skuActual,
    int $orden,
    int $recuento,
    float $valor,
    float $maximo,
    float $minimo,
    string $formula,
    array &$procesados,
    string $tipoRelacion,
    float $val,
    array &$rastro,
    string $skuOrigen,
    string $skuOrigenraiz,
    int $nivel = 0,
    string $skufinal,
    ?object &$padre,
    string $Centro,
    string $mensaj=''
) {
    try {
        // 🚀 MEMORIA RAM ESTÁTICA EXCLUSIVA DEL AUTÓMATA (Evita duplicar queries pesados en MySQL)
        static $cacheTotalesRecursivo   = [];
        static $cachePadresCalculo      = [];
        static $cachePadresCategoria    = [];
        static $cacheRelacionesNodo     = [];

        // Interceptor de control para vaciar cachés de BD entre cambios de órdenes del lote
        if ($skuActual === 'RESET_CACHE' || $orden === 0) {
            $cacheTotalesRecursivo   = [];
            $cachePadresCalculo      = [];
            $cachePadresCategoria    = [];
            $cacheRelacionesNodo     = [];
            return;
        }

        // 🛑 CASO BASE 1
        if ($valor <= 0 && $nivel == 0) {
            return;
        }
        $cantidad = substr_count($skuOrigen, ".");

        $clave = $orden . '_' . $skuActual. '_' .$skuOrigen . '_' . $skufinal.'_'.$tipoRelacion;
        $CANT = substr_count($clave, "01.011007881TRAMO");
        if($CANT > 0){
            $a = "25188580_34028679_1021133_102113334028679";
        }

        if (isset($procesados[$clave])) {
            return; // 🚫 Ya fue procesado este Orden + SKU
        }

        // 🚀 OPTIMIZACIÓN SUPREMA O(1): Buscamos por LLAVE en lugar de in_array pesados.
        // El rastro NO se borra, sigue acumulándose de forma nativa e intacta.
        if (isset($rastro[$clave])) {
            return; // 🔁 ciclo detectado de forma instantánea
        }

        // Guardamos la marca usando la clave como LLAVE del arreglo asociativo
        $rastro[$clave] = true;

        if($nivel > ($recuento + 1) && $tipoRelacion == 'calculo' ){
            return;
        }



    // =========================================================================
    // 🌳 1. DETERMINACIÓN DEL PIVOTE Y CÁLCULO DE "VALOR" (Fijado contra Binary Error)
    // =========================================================================
    $llaveValorRAM = "valor_{$orden}_{$skuActual}_{$skuOrigen}_{$maximo}_{$minimo}";

    if (!isset($cacheTotalesRecursivo[$llaveValorRAM])) {

        $totalValor = DB::selectOne("
            SELECT COALESCE(SUM(e.Cantidad), 0) AS total_valor_calculado
            FROM ETA e
            INNER JOIN treematerialescategoria tm 
               ON CAST(e.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(tm.SKU AS CHAR) COLLATE utf8mb4_unicode_ci
            WHERE e.Orden = ? 
              AND e.fkTienda = ?
              AND tm.fkTienda = ?
              AND (
                  -- 🔹 ESCENARIO 1: Se cumple la regla y el SKU NO tiene puntos (Busca hermanos)
                  (
                      ? = 10000.0 AND ? = 10000.0 
                      AND INSTR(CAST(? AS CHAR), '.') = 0 
                      AND tm.padre_id = (
                          SELECT sub_tm.padre_id 
                          FROM treematerialescategoria sub_tm 
                          WHERE CAST(sub_tm.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(? AS CHAR) COLLATE utf8mb4_unicode_ci 
                            AND sub_tm.fkTienda = ? 
                          LIMIT 1
                      )
                  )
                  OR
                  -- 🔹 ESCENARIO 2: El SKU origen YA es la categoría padre (Tiene puntos ej: '04.05')
                  (
                      INSTR(CAST(? AS CHAR), '.') > 0 
                      AND tm.padre_id = (
                          SELECT sub_tmc.id 
                          FROM treematerialescategoria sub_tmc 
                          WHERE CAST(sub_tmc.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(? AS CHAR) COLLATE utf8mb4_unicode_ci 
                            AND sub_tmc.fkTienda = ? 
                          LIMIT 1
                      )
                  )
                  OR
                  -- 🔹 ESCENARIO 3: Es un material individual normal estándar (Suma solo el SKU origen)
                  (
                      NOT (? = 10000.0 AND ? = 10000.0 AND INSTR(CAST(? AS CHAR), '.') = 0)
                      AND INSTR(CAST(? AS CHAR), '.') = 0 
                      AND CAST(tm.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(? AS CHAR) COLLATE utf8mb4_unicode_ci
                  )
              )
        ", [
            $orden, session('user_fkTienda'), session('user_fkTienda'),
            $maximo, $minimo, $skuOrigen, $skuOrigen, session('user_fkTienda'), // Escenario 1
            $skuOrigen, $skuOrigen, session('user_fkTienda'), // Escenario 2
            $maximo, $minimo, $skuOrigen, $skuOrigen, $skuOrigen // Escenario 3
        ]);

        $cacheTotalesRecursivo[$llaveValorRAM] = $totalValor->total_valor_calculado ?? 0;
    }
    $valor = $cacheTotalesRecursivo[$llaveValorRAM];


    // =========================================================================
    // 🌳 2. DETERMINACIÓN DEL PIVOTE Y CÁLCULO DE "USADO" (Fijado contra Binary Error)
    // =========================================================================
    $llaveUsadoRAM = "usado_{$orden}_{$skuActual}_{$maximo}";

    if (!isset($cacheTotalesRecursivo[$llaveUsadoRAM])) {

        $totalUsado = DB::selectOne("
            SELECT COALESCE(SUM(e.Cantidad), 0) AS total_usado_calculado
            FROM ETA e
            INNER JOIN treematerialescategoria tm 
               ON CAST(e.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(tm.SKU AS CHAR) COLLATE utf8mb4_unicode_ci
            WHERE e.Orden = ? 
              AND e.fkTienda = ?
              AND tm.fkTienda = ?
              AND (
                  -- 🔹 ESCENARIO 1: Tiene puntos (ej: '04.03') -> Busca directo por la categoría del padre
                  (
                      INSTR(CAST(? AS CHAR), '.') > 0 
                      AND tm.padre_id = (
                          SELECT sub_tmc.id 
                          FROM treematerialescategoria sub_tmc 
                          WHERE CAST(sub_tmc.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(? AS CHAR) COLLATE utf8mb4_unicode_ci 
                            AND sub_tmc.fkTienda = ? 
                          LIMIT 1
                      )
                  )
                  OR
                  -- 🔹 ESCENARIO 2: NO tiene puntos pero maximo = 10000 (Es un hijo que debe sumar a sus hermanos)
                  (
                      INSTR(CAST(? AS CHAR), '.') = 0 
                      AND ? = 10000.0
                      AND tm.padre_id = (
                          SELECT sub_tm.padre_id 
                          FROM treematerialescategoria sub_tm 
                          WHERE CAST(sub_tm.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(? AS CHAR) COLLATE utf8mb4_unicode_ci 
                            AND sub_tm.fkTienda = ? 
                          LIMIT 1
                      )
                  )
                  OR
                  -- 🔹 ESCENARIO 3: Es un material individual normal estándar (Suma solo el SKU actual)
                  (
                      INSTR(CAST(? AS CHAR), '.') = 0 
                      AND ? <> 10000.0
                      AND CAST(tm.SKU AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(? AS CHAR) COLLATE utf8mb4_unicode_ci
                  )
              )
        ", [
            $orden, session('user_fkTienda'), session('user_fkTienda'),
            $skuActual, $skuActual, session('user_fkTienda'), // Escenario 1
            $skuActual, $maximo, $skuActual, session('user_fkTienda'), // Escenario 2
            $skuActual, $maximo, $skuActual
        ]);

        $cacheTotalesRecursivo[$llaveUsadoRAM] = $totalUsado->total_usado_calculado ?? 0;
    }
    $usado = $cacheTotalesRecursivo[$llaveUsadoRAM];



        $variables = [
            'minimo' => $minimo ?? 0,
            'maximo' => $maximo ?? 0,
            'valor'  => $valor ?? 0,
            'usado'  => $usado ?? 0,
            'total'  => $nivel ?? 0,
            'valant' => $val ?? 0,
        ];

        if($clave == "25542286_02.15_34033641_02.1534033641UNIDAD_requiere"){
            $a = "25188580_34028673_34028677_102113334028679";
        }

        $resultado = 0;
        $resultado = $this->evaluarFormulaexp($formula, $variables);

        $resultadoMostrar = $resultado;
        $resultado = $resultado == 20000 ? 0 : $resultado;

        if($resultado > 0 || $resultado < 0) {
            if ($tipoRelacion == 'requiere' || $tipoRelacion == 'incompatible') {
                $padrerel = DB::selectOne("
                    SELECT distinct e.SKU FROM ETA e
                    inner join material_relaciones mr on e.SKU=mr.SKU where e.Orden=? and e.SKU=? and mr.fkTienda=? ;
                ", [$orden, $skuActual, session('user_fkTienda')]);

                if($resultado <> 0){
                    $nodo = (object)[
                        'Orden'         => $orden,
                        'SKU_Origen'    => $skuActual,
                        'SKU_Destino'   => $skuOrigen,
                        'msj'           => $mensaj,
                        'ValorEntrada'  => $valor,
                        'Resultado'     => $resultado == 10000 ? $val : $resultado,
                        'TipoRelacion'  => $resultado < 0 ? $tipoRelacion . " - Exceso" : $tipoRelacion,
                        'formula'       => $formula,
                        'Nivel'         => $nivel,
                        'children'      => [],
                        'skuOrigenraiz' => $skufinal,
                        'CENTRO'        => $Centro,
                        'claveunica'     => $clave,
                        'NOMBRE_Destino' => $padre->nombre ?? "SKU materia analizado ".$tipoRelacion." ".$skuActual,
                    ];
                    $procesados[$clave] = $nodo;
                }
                elseif($padrerel){
                    $nodo = (object)[
                        'Orden'         => $orden,
                        'SKU_Origen'    => $skuActual,
                        'SKU_Destino'   => $skuOrigen,
                        'msj'           => $mensaj,
                        'ValorEntrada'  => $valor,
                        'Resultado'     => $resultado == 10000 ? $val : $resultado,
                        'TipoRelacion'  => $resultado < 0 ? $tipoRelacion . " - Exceso" : $tipoRelacion,
                        'formula'       => $formula,
                        'Nivel'         => $nivel,
                        'children'      => [],
                        'skuOrigenraiz' => $skufinal,
                        'CENTRO'        => $Centro,
                        'claveunica'     => $clave,
                        'NOMBRE_Destino' => $padre->nombre ?? "SKU materia analizado ".$tipoRelacion." ".$skuActual,
                    ];
                    $procesados[$clave] = $nodo;
                }
            }
        }

        // 🚀 CACHÉ RECURSIVA: Relaciones de fin de nodo para anular subconsultas redundantes a la BD
        $llaveNodoFinal = "nodo_" . $skufinal;
        if (!isset($cacheRelacionesNodo[$llaveNodoFinal])) {
            $cacheRelacionesNodo[$llaveNodoFinal] = (object)[
                'relaciones' => Material_relaciones::where('skufinal', $skufinal)->orderBy('id', 'ASC')->get(),
                'conteo'     => Material_relaciones::where('skufinal', $skufinal)->count()
            ];
        }

        $relaciones = $cacheRelacionesNodo[$llaveNodoFinal]->relaciones;
        $conteo     = $cacheRelacionesNodo[$llaveNodoFinal]->conteo;

        foreach ($relaciones as $relacion) {
            $claveHijo = $orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion;
            
            if($orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion == "25658138_34028704_34028710_40144661001856_calculo"){
            $a = $orden.''.$relacion->SKU.''.$relacion->depende_SKU.''.$relacion->skufinal.''.$relacion->tipo_relacion;
            }
            // 🚀 BÚSQUEDA INSTANTÁNEA: Validación contra el rastro asociativo global intacto por LLAVE
            if (isset($rastro[$claveHijo])) {
            continue;
            }
            $aSKUFILTRO = $clave;
            $tipoRelacion = $relacion->tipo_relacion;
            if($aSKUFILTRO == $orden.''.$relacion->SKU.''.$relacion->depende_SKU.''.$relacion->skufinal.''.$relacion->tipo_relacion){
            $a = "25188580_34028673_34028677_102113334028679";
            $claveHijoFalso = $orden.''.$relacion->SKU.''.$relacion->depende_SKU.'_'.$relacion->skufinal;
            $rastro[$claveHijoFalso] = true;
            continue; // 🚫 Evitar procesar el mismo nodo en esta rama
            }
            $SKUSSS = $relacion->SKU;
                if($tipoRelacion == "calculo"){
                $llaveCalculoPadre = "calc_{$orden}_{$relacion->SKU}";
                        if (!isset($cachePadresCalculo[$llaveCalculoPadre])) {
                        $cachePadresCalculo[$llaveCalculoPadre] = DB::selectOne("
                        SELECT distinct e.SKU FROM ETA e
                        inner join material_relaciones mr on e.SKU=mr.depende_SKU where e.Orden=? and mr.depende_SKU=? and mr.fkTienda=?;
                        ", [$orden, $relacion->SKU, session('user_fkTienda')]);
                        }

                $padreCalculo = $cachePadresCalculo[$llaveCalculoPadre];
                        if (!$padreCalculo) {
                        $cantidad = substr_count($relacion->depende_SKU, ".");
                            if($cantidad == 0){
                                $cantidad = substr_count($relacion->SKU, ".");
                                if($cantidad == 0){
                                continue;
                                }
                            }
                        }
                }
            // 1️⃣ Obtener SKU padre (Optimizado en Caché Estática por SKU)
            $llavePadreCat = $relacion->SKU;
            if (!isset($cachePadresCategoria[$llavePadreCat])) {
            $cachePadresCategoria[$llavePadreCat] = DB::selectOne("
            SELECT tmc.SKU, tmc.nombre, tmc.minimo, tmc.limite, tmc.tipo, tmc.valor
            FROM treematerialescategoria tm
            INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
            WHERE tm.SKU = ? AND tmc.fkTienda = ? LIMIT 1
            ", [$relacion->SKU, session('user_fkTienda')]);
            }
            $padreObjeto = $cachePadresCategoria[$llavePadreCat];
            // 🛑 CASO BASE 2
            if (!$padreObjeto) {
            continue;
            }
            if($aSKUFILTRO == "25298449_34025712_4018238_401823834028673_calculo"){
            $a = "25188580_34028673_34028677_102113334028679";
            }
            // 🔁 LLAMADA RECURSIVA INTEGRAL NATIVA
            $this->AutomataRecursivo(
            $relacion->SKU,
            $orden,
            $conteo,
            $resultadoMostrar = is_null($resultadoMostrar) ? 0 : (($resultadoMostrar == 20000) ? $val : $resultadoMostrar),
            $relacion->maximo   ?? 0,
            $relacion->minimo   ?? 0,
            $relacion->formula,
            $procesados,
            $relacion->tipo_relacion,
            $resultadoMostrar = is_null($resultadoMostrar) ? 0 : (($resultadoMostrar == 20000) ? $val : $resultadoMostrar),
            $rastro, // Pasado acumulativo intacto
            $relacion->depende_SKU,
            $skuOrigenraiz,
            $nivel + 1,
            $relacion->skufinal,
            $padreObjeto,
            $Centro,
            $relacion->nombre
            );
}
} catch (\Exception $e) {
Log::error('Error crítico en AutomataRecursivo: ' . $e->getMessage());
}
}

public function AutomataValidarMamoOrdenTecnico(Request $request)
{
    try {
    if(!Auth::check()) return response()->json(['error' => 'No autorizado'], 401);
    $procesados = []; 
    $rastro = [];
    $orden = $request->input('Orden');
    $skuNuevo = trim($request->input('SKU_Nuevo', ''));
    $cantidadNueva = (float)$request->input('Cantidad_Nueva', 0);
    $idTienda = session('user_fkTienda');

    $itemsMemoria = collect($request->input('Items_Memoria', []))
    ->groupBy('sku')
    ->map(function ($group) {
        // Mantiene los datos del primer item y actualiza la cantidad total
        $firstItem = $group->first();
        $firstItem['cantidad'] = $group->sum('cantidad'); 
        return $firstItem;
    })
    ->values()
    ->all();


    $itemnuevo = $request->input('ItemVirtual');
    
    if (!is_array($itemsMemoria)) { 
        $itemsMemoria = []; 
    }

    // 💡 TU LÓGICA PRINCIPAL: Si ya existen elementos en la lista de memoria (allItems)
    if ($itemsMemoria) {
        foreach ($itemsMemoria as $item) {
            $this->ejecutarLogicaInternaVista($orden, $item, $procesados, $rastro, $itemsMemoria, $request->input('Tipo_Orden', ''));
        }
    } else {
        // 🎯 TU LÓGICA DEL PRIMER REGISTRO: Cuando Items_Memoria está vacío
        if (!is_array($itemnuevo)) {
            $itemsMemoria = [];
        }

        // Estructuramos el arreglo plano tal como lo definiste
        $itemFormateado = [
            'index'    => trim($itemnuevo['index'] ?? '1'),
            'sku'      => trim($itemnuevo['sku'] ?? $skuNuevo),
            'cantidad' => (float)($itemnuevo['cantidad'] ?? $cantidadNueva),
            'centro'   => trim($itemnuevo['CENTRO'] ?? $itemnuevo['centro'] ?? "'G888")
        ];
        
        // 💡 CORRECCIÓN DE MATRIZ: Envolvemos el ítem para que finja ser una tabla de 1 fila
        // Esto evita que las funciones ->where() y ->sum() devuelvan cero o lancen errores
        $tablaVirtual = [$itemFormateado];
        
        // Ejecutamos pasando el registro individual y la lista simulada de una fila
        $this->ejecutarLogicaInternaVista($orden, $itemFormateado, $procesados, $rastro, $tablaVirtual, $request->input('Tipo_Orden', ''));
        
    }

    // Consolidación de mermas e incompatibles procesados en la memoria RAM
 $validaciones = $this->quitarDuplicadosPorOrdenYSKU($procesados);

// 1. Arreglo para acumular todos los problemas detectados
$alertasDetectadas = [];
$tieneExceso = false;
$tieneFalta = false;

foreach ($validaciones as $val) {
    if ($val) {
        $skuNodo = trim($val->SKU_Origen ?? $val->SKU ?? $skuNuevo);

        $categoriaData = DB::table('treematerialescategoria')
            ->where('SKU', $skuNodo)
            ->where('fkTienda', $idTienda)
            ->select('limite', 'minimo', 'nombre')
            ->first();

        $calculado = (float)($val->valor_calculado ?? $val->Resultado ?? $val->cantidad ?? 0);
        
        $maximo = $categoriaData ? (float)$categoriaData->limite : (float)($val->maximo_calculado ?? $val->maximo ?? 0);
        $minimo = $categoriaData ? (float)$categoriaData->minimo : (float)($val->minimo_calculado ?? $val->minimo ?? 0);
        
        $nombreBase = $categoriaData ? $categoriaData->nombre : ($val->NOMBRE_Destino ?? $val->nombre_material ?? "Material técnico");
        $nombreMaterial = $nombreBase . ' - ' . ($val->msj ?? '');

        // 🚫 Evaluación de Exceso: Acumulamos en lugar de retornar inmediatamente
        if ($calculado > 0) {
            $diffExceso = $calculado - $maximo;
            $tieneExceso = true;
            $alertasDetectadas[] = "Regla ('{$val->skuOrigenraiz}')⚠️ LÍMITE SUPERADO: Requerimiento insatisfecho para '{$nombreMaterial}'. Tipo: {$val->TipoRelacion} - Resultado: {$val->Resultado}";
        }

        // 💡 Evaluación de Faltante: Acumulamos en lugar de retornar inmediatamente
        if ($calculado < 0) {
            $diffFalta = $minimo - $calculado;
            $tieneFalta = true;
            $alertasDetectadas[] = "Regla ('{$val->skuOrigenraiz}')💡 FALTA MATERIAL: Requerimiento insatisfecho para '{$nombreMaterial}'. Tipo: {$val->TipoRelacion} - Resultado: {$val->Resultado}";
        }
    }
}

// 2. Evaluamos los resultados una vez terminado el bucle foreach
if (!empty($alertasDetectadas)) {
    // Definimos un estado jerárquico (si hay excesos, priorizamos mandar 'exceso')
    $statusFinal = $tieneExceso ? 'exceso' : 'falta';

    return response()->json([
        'status' => $statusFinal,
        'mensajes' => $alertasDetectadas // Enviamos el arreglo completo de alertas
    ], 200);
}


    return response()->json(['status' => 'ok', 'mensaje' => 'Validación correcta.'], 200);

    }catch (\Exception $e) {
        logger()->error('Error en AutomataValidarMamoOrdenTecnico: ' . $e->getMessage());
        return response()->json(['error' => 'Error en la validación: ' . $e->getMessage()], 500);
    }
}
public function AutomataValidarMamo(Request $request)
{
    if(!Auth::check()) return redirect()->route('login');

    // Blindaje básico para micro-lotes
    ini_set('max_execution_time', 300);
    ini_set('memory_limit', '512M');
    DB::connection()->disableQueryLog();

    $ordenesAExcluir = [];

    // 1. CARGA INICIAL: Configuración de exclusiones y reseteo de acumuladores
    if ($request->input('obtener_total') == 1) {
        // Limpiamos el historial de órdenes analizadas de ejecuciones anteriores
        session()->forget('mamo_ordenes_analizadas_acumulado');

        if ($request->hasFile('csv_excluidas') && $request->file('csv_excluidas')->isValid()) {
            $path = $request->file('csv_excluidas')->getRealPath();
            if (($handle = fopen($path, 'r')) !== false) {
                $primeraLinea = fgets($handle);
                $delimitador = ","; // Por defecto
                
                if ($primeraLinea !== false) {
                    $conteoComas = substr_count($primeraLinea, ',');
                    $conteoPuntosComas = substr_count($primeraLinea, ';');
                    if ($conteoPuntosComas > $conteoComas) $delimitador = ";";
                }
                
                rewind($handle);
                while (($data = fgetcsv($handle, 0, $delimitador)) !== false) {
                    if (isset($data[0])) {
                        $ordenLimpia = trim($data[0]); 
                        if ($ordenLimpia !== '') $ordenesAExcluir[] = $ordenLimpia;
                    }
                }
                fclose($handle);
            }
        }
        session(['mamo_ordenes_excluidas_historico' => $ordenesAExcluir]);
    } else {
        $ordenesAExcluir = session('mamo_ordenes_excluidas_historico', []);
    }

    // 2. CONSTRUCCIÓN DEL UNIVERSO BASE EN SQL
    $queryOrdenes = DB::table('ETA')
        ->whereBetween('created_at', [
            Carbon::parse($request->fechaincio)->startOfDay(),
            Carbon::parse($request->fechafin)->endOfDay()
        ])
        ->where('fkTienda', session('user_fkTienda'))
        ->when(!empty($ordenesAExcluir), function($query) use ($ordenesAExcluir) {
            return $query->whereNotIn('Orden', $ordenesAExcluir);
        })
        ->select('Orden')
        ->groupBy('Orden')
        ->orderBy('Orden', 'asc'); 

    // Si es el arranque, respondemos con el total de trabajo
    if ($request->input('obtener_total') == 1) {
        return response()->json([
            'status' => 'inicio',
            'total_ordenes' => $queryOrdenes->get()->count()
        ]);
    }

    // 3. CONTROL DE PAGINACIÓN ASÍNCRONA (Peticiones de JavaScript)
    $offset = (int) $request->input('offset', 0);
    $limiteLote = 2; 

    $mamoorden = $queryOrdenes->skip($offset)->take($limiteLote)->get();

    // 🚨 REGRESO FINAL MAESTRO: Si ya no hay más órdenes en la cola, devolvemos el acumulado completo
    if ($mamoorden->isEmpty()) {
        $totalAnalizadas = session('mamo_ordenes_analizadas_acumulado', []);
        
        // Limpiamos las sesiones para dejar el servidor impecable
        session()->forget(['mamo_ordenes_excluidas_historico', 'mamo_ordenes_analizadas_acumulado']);

        return response()->json([
            'status' => 'completado',
            'ordenes_analizadas_totales' => $totalAnalizadas // 🚀 Aquí viaja tu lista gigante con todo lo analizado
        ]);
    }

    $procesados = []; 
    $rastro = []; 
    $ordenesProcesadasEnEsteLote = [];
    
    // Recuperamos lo que llevamos analizado en los turnos anteriores desde la sesión
    $acumuladoAnalizadas = session('mamo_ordenes_analizadas_acumulado', []);

    // 4. PROCESAMIENTO DEL BLOQUE ACTUAL
    foreach($mamoorden as $ordenitem) {
        
        // 🔄 CAPTURA INMEDIATA: Se registra como ANALIZADA (Sin importar si arroja datos o no)
        $acumuladoAnalizadas[] = ['Orden' => $ordenitem->Orden];
        
        // También la guardamos de momento en el lote para control del Front-end
        $ordenesProcesadasEnEsteLote[] = ['Orden' => $ordenitem->Orden];

        // Limpieza de cachés estáticas internas
        $itemFalso = new \stdClass();
        $itemFalso->CENTRO = ''; $itemFalso->SKU = ''; $itemFalso->TIPO_DE_SERVICIO = '';
        $this->ejecutarLogicaInterna('RESET_CACHE', $itemFalso, $procesados, $rastro, true);
        $this->AutomataRecursivo('RESET_CACHE', 0, 0, 0, 0, 0, '', $procesados, '', 0, $rastro, '', '', 0, '', $padre, '');

        // Consulta de ítems
        $items = DB::table('ETA')
            ->select('TIPO_DE_SERVICIO', 'CENTRO', 'SKU', DB::raw('SUM(cantidad) as Cantidad'))
            ->where('fkTienda', session('user_fkTienda'))
            ->where('Orden', $ordenitem->Orden)
            ->groupBy('TIPO_DE_SERVICIO', 'SKU', 'CENTRO')
            ->get();

        foreach ($items as $item) {
            $this->ejecutarLogicaInterna($ordenitem->Orden, $item, $procesados, $rastro);
        }

        unset($items);
    }

    // Guardamos la lista de analizadas actualizada en la sesión para el siguiente bloque
    session(['mamo_ordenes_analizadas_acumulado' => $acumuladoAnalizadas]);

    // Devolvemos el avance del lote a JavaScript
    return response()->json([
        'status' => 'progreso',
        'datos_lote' => $this->quitarDuplicadosPorOrdenYSKU($procesados),
        'ordenes_lote' => $ordenesProcesadasEnEsteLote
    ]);
}

public function AutomataValidarMamogg(Request $request)
{
    if(!Auth::check()) return redirect()->route('login');

    // Blindaje básico para micro-lotes
    ini_set('max_execution_time', 300);
    ini_set('memory_limit', '512M');
    DB::connection()->disableQueryLog();

    // --- NUEVO: CARGAR ÓRDENES A OMITIR DESDE EL CSV EN MEMORIA ---

// --- CARGAR ÓRDENES A OMITIR CON DETECTOR DINÁMICO DE DELIMITADOR (;,\t) ---
$ordenesAExcluir = [];
if ($request->hasFile('csv_excluidas') && $request->file('csv_excluidas')->isValid()) {
    $path = $request->file('csv_excluidas')->getRealPath();
    
    if (($handle = fopen($path, 'r')) !== false) {
        // 🔍 PASO 1: Leer la primera línea para detectar el delimitador de la PC
        $primeraLinea = fgets($handle);
        $delimitador = ","; // Por defecto
        
        if ($primeraLinea !== false) {
            // Contamos cuántas comas y cuántos puntos y comas hay en la línea
            $conteoComas = substr_count($primeraLinea, ',');
            $conteoPuntosComas = substr_count($primeraLinea, ';');
            $conteoTabuladores = substr_count($primeraLinea, "\t");

            if ($conteoPuntosComas > $conteoComas) {
                $delimitador = ";";
            } elseif ($conteoTabuladores > $conteoComas && $conteoTabuladores > $conteoPuntosComas) {
                $delimitador = "\t"; // Soporte para archivos separados por pestañas
            }
        }
        
        // 🔄 PASO 2: Resetear el puntero para leer el archivo desde el principio
        rewind($handle);

        // 🚗 PASO 3: Leer el archivo con el delimitador correcto detectado
        while (($data = fgetcsv($handle, 0, $delimitador)) !== false) {
            if (isset($data[0])) {
                $ordenLimpia = trim($data[0]); 
                if ($ordenLimpia !== '') {
                    $ordenesAExcluir[] = $ordenLimpia;
                }
            }
        }
        fclose($handle);
    }
}



    // 1. Obtener los IDs de las órdenes que entran en el rango de fechas
    $queryOrdenes = DB::table('ETA')
        ->whereBetween('created_at', [
            Carbon::parse($request->fechaincio)->startOfDay(),
            Carbon::parse($request->fechafin)->endOfDay()
        ])
        ->where('fkTienda', session('user_fkTienda'))
        // --- NUEVO: SI HAY ÓRDENES EN EL CSV, LAS FILTRAMOS DIRECTAMENTE DESDE SQL ---
        ->when(!empty($ordenesAExcluir), function($query) use ($ordenesAExcluir) {
            return $query->whereNotIn('Orden', $ordenesAExcluir);
        })
        ->select('Orden')
        ->groupBy('Orden');

    // Si es la primera llamada (bloque = 0), devolvemos solo el conteo total restando las excluidas
    if ($request->input('obtener_total') == 1) {
        return response()->json([
            'status' => 'inicio',
            'total_ordenes' => $queryOrdenes->get()->count()
        ]);
    }

    // 2. Control de paginación enviado desde JavaScript
    $offset = (int) $request->input('offset', 0);
    $limiteLote = 2; // Procesamos estrictamente de 2 en 2

    $mamoorden = $queryOrdenes->skip($offset)->take($limiteLote)->get();

    if ($mamoorden->isEmpty()) {
        return response()->json(['status' => 'completado']);
    }

    $procesados = []; 
    $rastro = []; 
    $ordenesProcesadasEnEsteLote = [];

    // 3. Procesar únicamente las 2 órdenes de este turno
    foreach($mamoorden as $ordenitem) {
        
        $ordenesProcesadasEnEsteLote[] = ['Orden' => $ordenitem->Orden];

        // Limpieza de caché estática interna
        $itemFalso = new \stdClass();
        $itemFalso->CENTRO = ''; $itemFalso->SKU = ''; $itemFalso->TIPO_DE_SERVICIO = '';
        $this->ejecutarLogicaInterna('RESET_CACHE', $itemFalso, $procesados, $rastro, true);
        $this->AutomataRecursivo('RESET_CACHE', 0, 0, 0, 0, 0, '', $procesados, '', 0, $rastro, '', '', 0, '', $padre, '');

        // Consulta de ítems
        $items = DB::table('ETA')
            ->select('TIPO_DE_SERVICIO', 'CENTRO', 'SKU', DB::raw('SUM(cantidad) as Cantidad'))
            ->where('fkTienda', session('user_fkTienda'))
            ->where('Orden', $ordenitem->Orden)
            ->groupBy('TIPO_DE_SERVICIO', 'SKU', 'CENTRO')
            ->get();

        foreach ($items as $item) {
            $this->ejecutarLogicaInterna($ordenitem->Orden, $item, $procesados, $rastro);
        }

        unset($items);
    }

    // 4. Retornar los resultados de estas 2 órdenes específicas
    return response()->json([
        'status' => 'progreso',
        'datos_lote' => $this->quitarDuplicadosPorOrdenYSKU($procesados),
        'ordenes_lote' => $ordenesProcesadasEnEsteLote
    ]);
}

public function AutomataValidarMamoFF(Request $request)
{
    if(!Auth::check()) return redirect()->route('login');

    // 1. BLINDAJE MÁXIMO DE TIEMPO Y MEMORIA RAM (Para soportar las 3,000 órdenes de golpe)
    ini_set('max_execution_time', 3000); // 8 horas de tolerancia máxima en el servidor
    ini_set('memory_limit', '2048M');     // 2 GB de RAM para almacenar el mapa global acumulado
    set_time_limit(3000);

    DB::connection()->disableQueryLog();

    $procesados = []; 
    $rastro = []; // Mantiene el historial acumulativo global intacto durante todo el universo de órdenes

    Log::info("=== INICIANDO PROCESAMIENTO TOTAL SUPREMO ===");

    // 2. 🚀 OPTIMIZACIÓN: OBTENER ABSOLUTAMENTE TODAS LAS ÓRDENES SIN LÍMITES
    $mamoorden = Eta::whereBetween('created_at', [
            Carbon::parse($request->fechaincio)->startOfDay(),
            Carbon::parse($request->fechafin)->endOfDay()
        ])
        ->where('fkTienda', session('user_fkTienda'))
        ->select('Orden')
        ->groupBy('Orden')
        // El ->limit() ha sido removido para procesar todo lo que encuentre en el rango
        ->get();

    if ($mamoorden->isEmpty()) {
        Log::warning("AutomataValidarMamo: No se encontraron órdenes en las fechas indicadas.");
        return redirect()->back()->with('error', 'No se encontraron órdenes en este rango de fechas.');
    }

    Log::info("Universo total de órdenes detectadas para procesar: " . $mamoorden->count());

    // 3. PROCESAMIENTO LINEAL EN MEMORIA CON REINICIO DE CACHÉ DE CONSULTAS SQL
    foreach($mamoorden as $ordenitem) {
        
        // Objeto simulado para vaciar las cachés estáticas de queries de la orden anterior.
        // El $rastro y los $procesados NO se borran, se mantienen acumulativos a nivel global.
        $itemFalso = new \stdClass();
        $itemFalso->CENTRO = ''; 
        $itemFalso->SKU = ''; 
        $itemFalso->TIPO_DE_SERVICIO = '';
        Log::info("👉 Procesando Orden en lote: [ {$ordenitem->Orden} ]");

        $this->ejecutarLogicaInterna('RESET_CACHE', $itemFalso, $procesados, $rastro, true);
        $this->AutomataRecursivo('RESET_CACHE', 0, 0, 0, 0, 0, '', $procesados, '', 0, $rastro, '', '', 0, '', $padre, '');

        // Consultamos los ítems específicos de esta orden (Tu consulta original infalible)
        $items = DB::table('ETA')
            ->select('TIPO_DE_SERVICIO', 'CENTRO', 'SKU', DB::raw('SUM(cantidad) as Cantidad'))
            ->where('fkTienda', session('user_fkTienda'))
            ->where('Orden', $ordenitem->Orden)
            ->groupBy('TIPO_DE_SERVICIO', 'SKU', 'CENTRO')
            ->get();

        foreach ($items as $item) {
            $this->ejecutarLogicaInterna($ordenitem->Orden, $item, $procesados, $rastro);
        }

        // Liberación preventiva de memoria intermedia al terminar cada orden
        unset($items);
        gc_collect_cycles(); 
    }

    Log::info("=== UNIVERSO COMPLETADO CON ÉXITO, GENERANDO DESCARGA ===");
    
    // Generamos el CSV final con todo el universo consolidado de registros
    return $this->descargarCSV($this->quitarDuplicadosPorOrdenYSKU($procesados), 'validaciones_universo_completo.csv');
}


public function AutomataValidarMamoSS(Request $request)
{
    if(!Auth::check()) return redirect()->route('login');

    ini_set('max_execution_time', 100); // Elevamos a 10 minutos para soportar 100 órdenes juntas
    ini_set('memory_limit', '1024M');    
    set_time_limit(100);
    DB::connection()->disableQueryLog();

    $procesados = []; $rastro = [];
    
    // 📦 REGLA DE PAGINACIÓN: Tamaño de bloque fijo de 100
    $limite = 100; 
    
    // Leemos el bloque/página actual desde la petición (lote 1, lote 2, lote 3...). Si no viene, por defecto es 1.
    $paginaActual = (int) $request->input('lote', 1); 
    $offset = ($paginaActual - 1) * $limite;
    
    // 1. Obtener el segmento de órdenes que corresponden a ESTE lote específico
    $mamoorden = Eta::whereBetween('created_at', [
            Carbon::parse($request->fechaincio)->startOfDay(),
            Carbon::parse($request->fechafin)->endOfDay()
        ])
        ->where('fkTienda', session('user_fkTienda'))
        ->select('Orden')
        ->groupBy('Orden')
        ->skip($offset)       
        ->take($limite)       
        ->get();

    if ($mamoorden->isEmpty()) {
        return response()->json(['message' => 'No hay más órdenes para procesar en este rango o lote.'], 200);
    }

    //Log::info("=== INICIANDO PROCESO MASIVO SEGURO ===");
    //Log::info("Procesando Lote N°: {$paginaActual} | Órdenes en este bloque: " . $mamoorden->count() . " | Saltando previas: {$offset}");

    foreach($mamoorden as $ordenitem) {
        
        // 🚀 LIMPIEZA BLINDADA: Vaciamos la memoria RAM estática antes de evaluar la orden actual
        $itemFalso = new \stdClass();
        $itemFalso->CENTRO = '';
        $itemFalso->SKU = '';
        $itemFalso->TIPO_DE_SERVICIO = '';
        
        $this->ejecutarLogicaInterna('RESET_CACHE', $itemFalso, $procesados, $rastro);

        Log::info("👉 Procesando Orden en lote: [ {$ordenitem->Orden} ]");

        $items = DB::table('ETA')
            ->select('TIPO_DE_SERVICIO', 'CENTRO', 'SKU', DB::raw('SUM(cantidad) as Cantidad'))
            ->where('fkTienda', session('user_fkTienda'))
            ->where('Orden', $ordenitem->Orden)
            ->groupBy('TIPO_DE_SERVICIO', 'SKU', 'CENTRO')
            ->get();

        foreach ($items as $item) {
            $this->ejecutarLogicaInterna($ordenitem->Orden, $item, $procesados, $rastro);
        }

        unset($items);
        gc_collect_cycles();
    }

    Log::info("=== PROCESO TERMINADO CON ÉXITO PARA EL LOTE {$paginaActual} ===");
    
    // El archivo de descarga ahora incluirá de forma dinámica el número de bloque para no sobreescribirse
    $nombreArchivo = 'validaciones_lote_' . $paginaActual . '_ordenes_' . ($offset + 1) . '_al_' . ($offset + $mamoorden->count()) . '.csv';
    
    return $this->descargarCSV($this->quitarDuplicadosPorOrdenYSKU($procesados), $nombreArchivo);
}




public function AutomataValidarMamoOrden(Request $request)
{
    if(!Auth::check()) return response()->json(['error' => 'No autorizado'], 401);
    $procesados = []; $rastro = [];
    $orden = $request->input('Orden');

    // 📦 DEFINICIÓN DE LOTES (500 órdenes por petición)
    $tamanoLote = 10;
    $paginaActual = (int) $request->input('lote', 1); // Lee el número de lote actual (1, 2, 3...)
    $offset = ($paginaActual - 1) * $tamanoLote;


    $items = DB::table('ETA')->select('TIPO_DE_SERVICIO', 'CENTRO', 'SKU', DB::raw('SUM(cantidad) as Cantidad'))
             ->where('Orden', $orden)
             ->where('fkTienda', session('user_fkTienda'))
             ->groupBy('SKU', 'CENTRO','TIPO_DE_SERVICIO')->get();
    $this->ejecutarLogicaInterna(null, null, $procesados, $rastro, true);

    foreach ($items as $item) {
        
        $this->ejecutarLogicaInterna($orden, $item, $procesados, $rastro);
    }

    $validaciones = $this->quitarDuplicadosPorOrdenYSKU($procesados);
        // 💾 Nombre dinámico del archivo para identificar qué lote se está descargando
    
        $nombreArchivo = 'validaciones_lote_' . $paginaActual . '_ordenes_' . ($offset + 1) . '.csv';

    

    return response()->json(['validaciones' => $validaciones], 200, [], JSON_PRETTY_PRINT);
}

public function AutomataValidarMamoOrdenExcel(Request $request)
{
    if(!Auth::check()) return response()->json(['error' => 'No autorizado'], 401);
    
    // ====================================================================
    // BLINDAJE LOCAL CONTRA CAÍDAS DE SERVIDOR
    // ====================================================================
    ini_set('max_execution_time', 300); // 5 minutos máximo para una sola orden
    ini_set('memory_limit', '512M');
    set_time_limit(300);
    DB::connection()->disableQueryLog();

    $procesados = []; 
    $rastro = [];
    $orden = $request->input('Orden');

    // Validación preventiva por si el input llega vacío
    if (!$orden) {
        return response()->json(['error' => 'El número de orden es requerido o inválido.'], 400);
    }

    // 🎯 Consulta limpia de ítems para la orden única solicitada
    $items = DB::table('ETA')->select('TIPO_DE_SERVICIO', 'CENTRO', 'SKU', DB::raw('SUM(cantidad) as Cantidad'))
             ->where('Orden', $orden)
             ->where('fkTienda', session('user_fkTienda'))
             ->groupBy('SKU', 'CENTRO','TIPO_DE_SERVICIO')
             ->get();
    $this->ejecutarLogicaInterna(null, null, $procesados, $rastro, true);
    
    // Procesamos la jerarquía del autómata recursivo
    foreach ($items as $item) {
        $this->ejecutarLogicaInterna($orden, $item, $procesados, $rastro);
    }

    // Removemos registros duplicados antes de exportar
    $validaciones = $this->quitarDuplicadosPorOrdenYSKU($procesados);
    
    // Nombre de archivo limpio y descriptivo
    $nombreArchivo = "validaciones_orden_{$orden}_" . time() . ".csv";

    // 🚀 STREAMING DIRECTO A MEMORIA OPTIMIZADO Y BLINDADO
    return response()->streamDownload(function () use ($validaciones) {
        $file = fopen('php://output', 'w');
        
        // Inyectamos BOM UTF-8 para compatibilidad perfecta con Excel
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        if (!empty($validaciones) && is_array($validaciones)) {
            // Extraemos de forma limpia las llaves del primer elemento
            $primerElemento = current($validaciones);
            $encabezados = is_object($primerElemento) ? get_object_vars($primerElemento) : (array) $primerElemento;
            fputcsv($file, array_keys($encabezados), ';');
            
            // Escribimos los datos fila por fila evitando desbordamientos de memoria
            foreach ($validaciones as $nodo) {
                // Convertimos el nodo a un array plano limpio de referencias circulares
                $filaPlana = is_object($nodo) ? get_object_vars($nodo) : (array) $nodo;
                
                // Eliminamos cualquier posible sub-arreglo (como colecciones de hijos) que rompa fputcsv
                $filaFinal = array_map(function($valor) {
                    return (is_array($valor) || is_object($valor)) ? json_encode($valor) : $valor;
                }, $filaPlana);

                fputcsv($file, array_values($filaFinal), ';');
            }
        } else {
            fputcsv($file, ['Resultado'], ';');
            fputcsv($file, ['No se encontraron relaciones jerarquicas para esta orden'], ';');
        }

        fclose($file);
    }, $nombreArchivo, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}\"",
        'Pragma' => 'no-cache',
        'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        'Expires' => '0',
    ]);

}


private function descargarCSV($validaciones, $nombreArchivo)
{
    // 🛡️ Blindaje defensivo contra datos vacíos o corruptos
    if (empty($validaciones)) {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
            fputcsv($handle, ['Resultado'], ';');
            fputcsv($handle, ['No se encontraron registros validos para exportar'], ';');
            fclose($handle);
        }, $nombreArchivo, ['Content-Type' => 'text/csv']);
    }

    // 🚀 STREAMING SEGURO DIRECTO A DISCO
    return response()->streamDownload(function () use ($validaciones) {
        $handle = fopen('php://output', 'w');
        
        // 🌟 Corrección 1: Agregar el BOM UTF-8 indispensable para que Excel muestre acentos y eñes
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados limpios utilizando el separador estándar ';'
        fputcsv($handle, [
            'Orden', 'SKU_Origen', 'SKU_Destino', 'msj', 'ValorEntrada', 
            'Resultado', 'TipoRelacion', 'formula', 'Nivel', 'skuOrigenraiz', 
            'CENTRO', 'NOMBRE_Destino'
        ], ';');

        foreach ($validaciones as $f) {
            // 🌟 Corrección 2: Protección total contra propiedades no definidas en PHP 8.2
            // Usamos el operador de fusión nula (??) para que si la propiedad no existe, ponga un texto vacío en su lugar
            fputcsv($handle, [
                $f->Orden ?? '',
                $f->SKU_Origen ?? '',
                $f->SKU_Destino ?? '',
                $f->msj ?? '',
                $f->ValorEntrada ?? 0,
                $f->Resultado ?? 0,
                $f->TipoRelacion ?? '',
                $f->formula ?? '',
                $f->Nivel ?? 0,
                $f->skuOrigenraiz ?? '',
                $f->CENTRO ?? '',
                $f->NOMBRE_Destino ?? ($f->Nombre_Destino ?? '') // Maneja ambas variaciones de mayúsculas/minúsculas
            ], ';');
        }
        
        fclose($handle);
    }, $nombreArchivo, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}\"",
        'Pragma' => 'no-cache',
        'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        'Expires' => '0',
    ]);
}


private function quitarDuplicadosPorOrdenYSKU(array $items): array
{
    $unicos = [];

    foreach ($items as $item) {
        // Creamos la clave combinando el Nombre y la Orden como solicitaste
        $clave = $item->NOMBRE_Destino . '_' . $item->Orden. '_' . $item->msj;

        // Si la clave no existe, guardamos el item actual
        if (!isset($unicos[$clave])) {
            $unicos[$clave] = $item;
        } else {
            // LÓGICA DE PRIORIDAD:
            // Si el que ya tenemos guardado NO cumple (Resultado <= 0)
            // pero el nuevo SI cumple (Resultado > 0), lo reemplazamos
            $actualCumple = $unicos[$clave]->Resultado > 0;
            $nuevoCumple = $item->Resultado > 0;

            if (!$actualCumple && $nuevoCumple) {
                $unicos[$clave] = $item;
            }
        }
    }

    return array_values($unicos);
}

public function AutomataRecursivoSS(
    string $skuActual,
    int $orden,
    int $recuento,
    float $valor,
    float $maximo,
    float $minimo,
    string $formula,
    array &$procesados,
    string $tipoRelacion,
    float $val,
    array &$rastro,
    string $skuOrigen,
    string $skuOrigenraiz,
    int $nivel = 0,
    string $skufinal,
    ?object &$padre,
    string $Centro,
    string $mensaj=''
) {
    try {

    // 🛑 CASO BASE 1
    if ($valor <= 0 && $nivel==0) {
        return;
    }
    $cantidad = substr_count($skuOrigen, ".");

    if($skufinal=="'G8884018238"){
        $a="25188580_34028679_1021133_102113334028679";
    }

  $clave = $orden . '_' . $skuActual. '_' .$skuOrigen . '_' . $skufinal.'_'.$tipoRelacion;
$CANT = substr_count($clave, "'G888UNIDADPRINCIPAL");
      if($CANT>0){
        $a="25188580_34028679_1021133_102113334028679";
    }

if (isset($procesados[$clave])) {
    return false; // 🚫 Ya fue procesado este Orden + SKU
}

if (in_array($clave, $rastro)) {
    return false; // 🔁 ciclo detectado
}

$rastro[] = $clave;

    if($nivel>($recuento+1) && $tipoRelacion=='calculo' ){
        return false;
    }

    if(substr_count($clave, "40144661001856")>0){
    $a="25188580_34028673_34028677_102113334028679";
}
    // =========================================================================
    // 🌳 1. DETERMINACIÓN DEL PIVOTE Y CÁLCULO DE \"VALOR\" (MySQL Puro Adaptativo)
    // =========================================================================
    // 🚀 CACHÉ EN RAM: Llave única combinada para anular consultas redundantes en el bucle masivo
    $llaveValorRAM = "valor_{$orden}_{$skuActual}_{$skuOrigen}_{$maximo}_{$minimo}";

    if (!isset($cacheTotalesRecursivo[$llaveValorRAM])) {

        // Ejecutamos tu Query unificado adaptativo respetando tus variables de control
        $totalValor = DB::selectOne("
            SELECT COALESCE(SUM(e.Cantidad), 0) AS total_valor_calculado
            FROM ETA e
            INNER JOIN treematerialescategoria tm 
               ON e.SKU COLLATE utf8mb4_unicode_ci = tm.SKU COLLATE utf8mb4_unicode_ci
            WHERE e.Orden = ? 
              AND e.fkTienda = ?
              AND tm.fkTienda = ?
              AND (
                  -- 🔹 ESCENARIO 1: Se cumple la regla y el SKU NO tiene puntos (Busca hermanos)
                  (
                      ? = 10000.0 AND ? = 10000.0 
                      AND INSTR(?, '.') = 0 
                      AND tm.padre_id = (
                          SELECT sub_tm.padre_id 
                          FROM treematerialescategoria sub_tm 
                          WHERE sub_tm.SKU COLLATE utf8mb4_unicode_ci = ? COLLATE utf8mb4_unicode_ci 
                            AND sub_tm.fkTienda = ? 
                          LIMIT 1
                      )
                  )
                  OR
                  -- 🔹 ESCENARIO 2: El SKU origen YA es la categoría padre (Tiene puntos ej: '04.05')
                  (
                      INSTR(?, '.') > 0 
                      AND tm.padre_id = (
                          SELECT sub_tmc.id 
                          FROM treematerialescategoria sub_tmc 
                          WHERE sub_tmc.SKU COLLATE utf8mb4_unicode_ci = ? COLLATE utf8mb4_unicode_ci 
                            AND sub_tmc.fkTienda = ? 
                          LIMIT 1
                      )
                  )
                  OR
                  -- 🔹 ESCENARIO 3: Es un material individual normal estándar (Suma solo el SKU origen)
                  (
                      NOT (? = 10000.0 AND ? = 10000.0 AND INSTR(?, '.') = 0)
                      AND INSTR(?, '.') = 0 
                      AND tm.SKU COLLATE utf8mb4_unicode_ci = ? COLLATE utf8mb4_unicode_ci
                  )
              )
        ", [
            $orden, 
            session('user_fkTienda'), 
            session('user_fkTienda'),
            
            // 📝 Parámetros del Escenario 1:
            $maximo, $minimo, $skuOrigen, $skuOrigen, session('user_fkTienda'),
            
            // 📝 Parámetros del Escenario 2:
            $skuOrigen, $skuOrigen, session('user_fkTienda'),
            
            // 📝 Parámetros del Escenario 3:
            $maximo, $minimo, $skuOrigen, $skuOrigen, $skuOrigen
        ]);

        // Guardamos el número en la caché interna de la RAM para esta vuelta
        $cacheTotalesRecursivo[$llaveValorRAM] = $totalValor->total_valor_calculado ?? 0;
    }
    
    // Asignamos el valor final de forma limpia
    $valor = $cacheTotalesRecursivo[$llaveValorRAM];



    // =========================================================================
    // 🌳 2. DETERMINACIÓN DEL PIVOTE Y CÁLCULO DE "USADO" (Sobre SKU Actual)
    // =========================================================================
    // 🚀 AHORRO COMPLETO: Eliminamos el bloque IF y la consulta SQL duplicada.
    // Como tu código original siempre terminaba sobreescribiendo el pivote con el SKU actual,
    // simplemente lo asignamos de forma directa. El comportamiento es 100% idéntico.
    $skuActualPivote = $skuActual; 

    $cantidadActual = substr_count($skuActualPivote, ".");

    if ($cantidadActual >= 1) {
        $totalUsado = DB::selectOne("
            SELECT COALESCE(SUM(e.Cantidad), 0) AS total
            FROM ETA e
            INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU
            INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
            WHERE e.Orden = ? 
              AND e.fkTienda = ?
              AND tmc.SKU = ? 
              AND tmc.fkTienda = ?
        ", [$orden, session('user_fkTienda'), $skuActualPivote, session('user_fkTienda')]);
    } else {
        $totalUsado = DB::selectOne("
            SELECT COALESCE(SUM(e.Cantidad), 0) AS total
            FROM ETA e
            WHERE e.Orden = ? 
              AND e.SKU = ? 
              AND e.fkTienda = ?
        ", [$orden, $skuActualPivote, session('user_fkTienda')]);
    }

    $usado = $totalUsado->total ?? 0;



$variables = [
    'minimo' => $minimo ?? 0,
    'maximo' => $maximo ?? 0,
    'valor'  => $valor ?? 0,
    'usado'  => $usado ?? 0,
    'total'  => $nivel ?? 0,
    'valant' => $val ?? 0,
];

$resultado=0;
    $resultado = $this->evaluarFormulaexp($formula, $variables);

        if(substr_count($clave, "'G888401823834028673")>0){
    $a="25188580_34028673_34028677_102113334028679";
}


if($resultado==20000){
    if($nivel==0){
        $val=$valor ==null ? 0 : $valor;
        }else {
            $valor=$val == null ? 0 : $val;
        }    
}

$resultadoMostrar = $resultado;
$resultado= $resultado == 20000 ? 0 : $resultado;

 
$cambiarvalant=$this->contarDecimales($resultado);

if ($cambiarvalant === 6) {
    $resultadoMostrar = $resultado * 1000000;
    $valor=$resultadoMostrar;
    $val=$resultadoMostrar;
} 


     if($resultado > 0|| $resultado < 0) {
        if ($tipoRelacion == 'requiere'  || $tipoRelacion == 'incompatible' ) {

                $padrerel = DB::selectOne("
            SELECT distinct e.SKU FROM ETA e
            inner join material_relaciones mr on e.SKU=mr.SKU where e.Orden=? and e.SKU=? and mr.fkTienda=? ;
    ", [$orden, $skuActual, session('user_fkTienda')]);

        if($resultado <> 0){
            $nodo = (object)[
                'Orden'         => $orden,
                'SKU_Origen'    => $skuActual,
                'SKU_Destino'   => $skuOrigen,
                'msj'           => $mensaj,
                'ValorEntrada'  => $valor,
                'Resultado'     => $resultado == 10000 ? $val : $resultado,
                'TipoRelacion'  => $resultado < 0 ? $tipoRelacion . " - Exceso" : $tipoRelacion,
                'formula'       => $formula,
                'Nivel'         => $nivel,
                'children'      => [],
                'skuOrigenraiz' => $skufinal,
                'CENTRO'        => $Centro,
                'claveunica'     => $clave,
                'NOMBRE_Destino' => $padre->nombre ?? "SKU materia analizado ".$tipoRelacion." ".$skuActual,
            ];

            $procesados[$clave] = $nodo;
        }
        elseif($padrerel){
            $nodo = (object)[
                'Orden'         => $orden,
                'SKU_Origen'    => $skuActual,
                'SKU_Destino'   => $skuOrigen,
                'msj'           => $mensaj,
                'ValorEntrada'  => $valor,
                'Resultado'     => $resultado == 10000 ? $val : $resultado,
                'TipoRelacion'  => $resultado < 0 ? $tipoRelacion . " - Exceso" : $tipoRelacion,
                'formula'       => $formula,
                'Nivel'         => $nivel,
                'children'      => [],
                'skuOrigenraiz' => $skufinal,
                'CENTRO'        => $Centro,
                'claveunica'     => $clave,
                'NOMBRE_Destino' => $padre->nombre ?? "SKU materia analizado ".$tipoRelacion." ".$skuActual,
            ];

            $procesados[$clave] = $nodo;
        }


        }
    }

        //$relaciones = Material_relaciones::where('skufinal', $skufinal)
    //->orderBy('id', 'ASC')
    //->get();

    //$conteo = Material_relaciones::where('skufinal', $skufinal)
    //->count();

        $llaveNodoFinal = "nodo_" . $skufinal;
        if (!isset($cacheNodosRecursivo[$llaveNodoFinal])) {
            $cacheNodosRecursivo[$llaveNodoFinal] = (object)[
                'relaciones' => Material_relaciones::where('skufinal', $skufinal)->orderBy('id', 'ASC')->get(),
                'conteo'     => Material_relaciones::where('skufinal', $skufinal)->count()
            ];
        }

        $relaciones = $cacheNodosRecursivo[$llaveNodoFinal]->relaciones;
        $conteo     = $cacheNodosRecursivo[$llaveNodoFinal]->conteo;
        
    foreach ($relaciones as $relacion) {

    if (in_array($orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion, $rastro)) {
    continue; // 🔁 ciclo detectado
    }

        $aSKUFILTRO=$clave;
        $tipoRelacion=$relacion->tipo_relacion;

      if($aSKUFILTRO==$orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion){        
        $clave=$orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal;
        $rastro[] = $clave;        
        continue; // 🚫 Evitar procesar el mismo nodo en esta ram

    }

    if($nivel>150){
        return false; // 🔒 Protección contra recursión excesiva
    }

if ($tipoRelacion == "calculo") {
    $padre = DB::selectOne("
        SELECT DISTINCT e.SKU FROM ETA e
        INNER JOIN material_relaciones mr ON e.SKU = mr.depende_SKU 
        WHERE e.Orden = ? AND mr.depende_SKU = ? AND mr.fkTienda = ?;
    ", [$orden, $relacion->SKU, session('user_fkTienda')]);

    if (!$padre) {
        // ⚡ NUEVA VALIDACIÓN ADICIONAL: Si es maximo = 10000 y nivel > 0, buscamos si el SKU existe en los hijos de treearbol antes de continuar
        if ($relacion->maximo == 10000 && $nivel > 0) {
            $existeEnHijos = DB::selectOne("
                SELECT 1 FROM ETA e 
                WHERE e.Orden = ? 
                  AND e.fkTienda = ?
                  AND e.SKU IN (
                      SELECT tm.SKU 
                      FROM treematerialescategoria tm 
                      WHERE tm.padre_id = (
                          SELECT padre_id 
                          FROM treematerialescategoria 
                          WHERE SKU = ? 
                            AND fkTienda = ? 
                          LIMIT 1
                      )
                  )
                LIMIT 1
            ", [$orden, session('user_fkTienda'), $relacion->SKU, session('user_fkTienda')]);

            // Si NO existe en los hijos del árbol, procedemos con tu lógica original de puntos
            if (!$existeEnHijos) {
                $cantidad = substr_count($relacion->depende_SKU, ".");
                if ($cantidad == 0) {
                    $cantidad = substr_count($relacion->SKU, ".");
                    if ($cantidad == 0) {
                        continue;
                    }
                }
            }
        } else {
            // Comportamiento normal si no cumple maximo=10000 y nivel>0
            $cantidad = substr_count($relacion->depende_SKU, ".");
            if ($cantidad == 0) {
                $cantidad = substr_count($relacion->SKU, ".");
                if ($cantidad == 0) {
                    continue;
                }
            }
        }
    }
}

    // 1️⃣ Obtener SKU padre
    $padre = DB::selectOne("
        SELECT
            tmc.SKU,
            tmc.nombre,
            tmc.minimo,
            tmc.limite,
            tmc.tipo,
            tmc.valor
        FROM treematerialescategoria tm
        INNER JOIN treematerialescategoria tmc
            ON tm.padre_id = tmc.id
        WHERE tm.SKU = ? AND tmc.fkTienda = ?
        LIMIT 1
    ", [$relacion->SKU, session('user_fkTienda')]);

    // 🛑 CASO BASE 2
    if (!$padre) {
        continue;
    }

        // 🔁 llamada recursiva
        $this->AutomataRecursivo(
            $relacion->SKU,
            $orden,
            $conteo,
            $resultadoMostrar = is_null($resultadoMostrar) ? 0 : (($resultadoMostrar == 20000) ? $val : $resultadoMostrar),
            $relacion->maximo   ?? 0,
            $relacion->minimo   ?? 0,
            $relacion->formula,
            $procesados,
            $relacion->tipo_relacion,
            $resultadoMostrar = is_null($resultadoMostrar) ? 0 : (($resultadoMostrar == 20000) ? $val : $resultadoMostrar),
    $rastro,
    $relacion->depende_SKU,
    $skuOrigenraiz,
    $nivel + 1,
    $relacion->skufinal,

    $padre,
                     $Centro,
                     $relacion->nombre
        );
    }  } catch (Exception $e) {

            Log::error('Error al registrar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el cliente.');
        }

}

private function contarDecimales($numero): int
{
    // Convertimos a string con formato fijo para evitar notación científica como 2E-6
    // Usamos 14 decimales como máximo de inspección estándar en PHP
    $numStr = sprintf('%.14f', $arg = (float)$numero);
    
    // Eliminamos los ceros sobrantes a la derecha del decimal
    $numStr = rtrim($numStr, '0');
    
    // Si contiene un punto decimal, contamos cuántos caracteres hay después de él
    if (strpos($numStr, '.') !== false) {
        return strlen(explode('.', $numStr)[1]);
    }
    
    return 0; // Si es un número entero
}

private function evaluarFormulaexp(string $formula, array $variables)
{
    try {
        // 🛠️ 1. Normalizar la fórmula: Convertir a minúsculas por si viene 'FLOOR' o 'ROUND'
        $formula = strtolower($formula);

        // 🛠️ 2. 🚀 OPTIMIZACIÓN SUPREMA: Retorno rápido sin lanzar excepciones ni escribir Logs
        if (isset($variables['usado']) && $variables['usado'] == 1 && str_contains($formula, 'usado - 1')) {
            // Retornamos 0 de inmediato de forma limpia. PHP no sufre ni se detiene.
            return 0;
        }

        // 🛠️ 3. Reparar paréntesis faltantes en la expresión
        $parentesisApertura = substr_count($formula, '(');
        $parentesisCierre = substr_count($formula, ')');
        if ($parentesisApertura > $parentesisCierre) {
            $formula .= str_repeat(')', $parentesisApertura - $parentesisCierre);
        }

        // 💡 4. Instanciar usando el adaptador de caché nulo compatible con Laravel 10 para PHP 8.2
        $cache = new \Symfony\Component\Cache\Adapter\NullAdapter();
        $exp = new \Symfony\Component\ExpressionLanguage\ExpressionLanguage($cache);

 // 🌟 REEDICIÓN CORRECTA DE TODAS LAS FUNCIONES REGISTRADAS:

// round
$exp->register(
    'round',
    fn ($value, $precision = 0) => sprintf('round(%s, %s)', $value, $precision),
    fn ($variables, $value, $precision = 0) => round((float)$value, (int)$precision)
);

// floor
$exp->register(
    'floor',
    fn ($value) => sprintf('floor(%s)', $value),
    fn ($variables, $value) => floor((float)$value)
);

// ceil
$exp->register(
    'ceil',
    fn ($value) => sprintf('ceil(%s)', $value),
    fn ($variables, $value) => ceil((float)$value)
);

// abs
$exp->register(
    'abs',
    fn ($value) => sprintf('abs(%s)', $value),
    fn ($variables, $value) => abs((float)$value)
);

        // max
        $exp->register(
            'max',
            fn (...$args) => sprintf('max(%s)', implode(', ', $args)),
            fn ($variables, ...$args) => max(...$args)
        );

        // min
        $exp->register(
            'min',
            fn (...$args) => sprintf('min(%s)', implode(', ', $args)),
            fn ($variables, ...$args) => min(...$args)
        );

        return $exp->evaluate($formula, $variables);

    } catch (\Throwable $e) {
        // Solo se registrarán en el log errores reales e inesperados de sintaxis
        Log::error('Error crítico al evaluar fórmula: ' . $e->getMessage(), [
            'formula' => $formula,
            'variables' => $variables
        ]);
        
        return 0;
    }
}




private function construirArbol(array $nodosPlano)
{
    $arbol = [];
    $referencias = [];

    // 1️⃣ Clonar nodos para evitar referencias compartidas
    foreach ($nodosPlano as $nodo) {

        $nuevoNodo = clone $nodo; // CLAVE 🔥
        $nuevoNodo->children = [];

        $referencias[$nuevoNodo->SKU_Destino] = $nuevoNodo;
    }

    // 2️⃣ Construir jerarquía
    foreach ($referencias as $nodo) {

        if ($nodo->Nivel == 0) {
            $arbol[] = $nodo;
        } else {
            if (isset($referencias[$nodo->SKU_Origen])) {

                $padre = $referencias[$nodo->SKU_Origen];

                // ⚠️ Evitar auto-referencia directa
                if ($padre->SKU_Destino !== $nodo->SKU_Destino) {
                    $padre->children[] = $nodo;
                }
            }
        }
    }

    return array_values($arbol);
}

function evaluarFormula(string $formula, array $variables)
{
    $parser = new StdMathParser();
    $ast = $parser->parse($formula);

    $evaluator = new Evaluator();

    // ✅ AQUÍ está la corrección
    $evaluator->setVariables(
        array_map('floatval', $variables)
    );

    return $ast->accept($evaluator);
}

function JoboCommand(){
    try {
        Eta::orderBy('Orden')->where('fkTienda', session('user_fkTienda'))->where('Status', 'Pe')

   ->chunk(500, function ($rows) {

      // Agrupar por orden dentro del lote
      $ordenes = $rows->where('Status', 'Pe')
      ->groupBy('Orden');

      foreach ($ordenes as $orden => $items) {

         $tipo = $items->first()->TIPO_DE_SERVICIO.$items->first()->TIPO_DE_ORDEN;

         // 1️⃣ Familias usadas en la orden
         $familias = $items->map(function ($item) {
            return DB::table('treematerialescategoria')
                     ->where('SKU', $item->SKU)
                     ->where('fkTienda', session('user_fkTienda'))
                     ->value('padre_id');
         })->filter()->unique()->values();

         // 2️⃣ Guardar aprendizaje por familia
         foreach ($familias as $familia) {
            DB::table('aprendizaje_familia')->updateOrInsert(
               ['tipo_servicio' => $tipo, 'familia_id' => $familia],
               ['fkTienda' => session('user_fkTienda'), 'veces_usado' => DB::raw('veces_usado + 1')]
            );
         }

         // 3️⃣ Guardar combinaciones
         $count = count($familias);
         for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
               DB::table('combinacion_familia')->updateOrInsert(
                  [
                    'tipo_servicio' => $tipo,
                    'familia_a' => $familias[$i],
                    'familia_b' => $familias[$j]
                  ],
                  ['fkTienda' => session('user_fkTienda'), 'veces_juntos' => DB::raw('veces_juntos + 1')]
               );
            }
         }

DB::table('ETA')
    ->where('Orden', $orden)
    ->update(['Status' => 'Ok']);

         DB::table('aprendizaje_ordenes')->updateOrInsert(
   ['tipo_servicio' => $tipo, 'fkTienda' => session('user_fkTienda')],
   ['total_ordenes' => DB::raw('total_ordenes + 1')]
);

      }
   });


    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error al importar: ' . $e->getMessage());
    }
}
/**
 * Método para inserción/actualización masiva
 */
private function insertOrUpdateBatch(array $batchData)
{
    // Opción 1: INSERT IGNORE (si no necesitas actualizar)
    // DB::table('ETA')->insertOrIgnore($batchData);

    // Opción 2: UPSERT (Laravel 8.10+)
    DB::table('ETA')->upsert(
        $batchData,
        ['Orden', 'SKU', 'Cantidad', 'Serie'], // Claves únicas
        ['Descripcion', 'MAC1', 'MAC2', 'MAC3', 'TIPO_DE_SERVICIO',
         'TIPO_DE_ORDEN', 'CENTRO', 'EMPLEADO', 'fkTienda', 'updated_at']
    );
}
    public function store(StorePersonaRequest $request)
    {
        try {
                            if(!Auth::check()){
            return redirect()->route('login');
        }

            DB::beginTransaction();
            $persona = Persona::create($request->validated());
            $persona->cliente()->create([
                'persona_id' => $persona->id
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el cliente.');
        }

        return redirect()->route('clientes.index')->with('success', 'Cliente registrado');
    }

    public function descargarFormeta()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=Formato ETA.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columnas = ['Orden','SKU','Descripcion','Cantidad','Serie','MAC1','MAC2','MAC3','TIPO_DE_SERVICIO','TIPO_DE_ORDEN','CENTRO','EMPLEADO','created_at'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        $fkTienda = session('user_fkTienda') ?? 0;
        // Línea de ejemplo opcional:
        fputcsv($file, [23450285,1005749,'SMART CARD DE NAGRAVISI¿¿N',1,"'142878214761",'','',"'NAGRAVISI¿¿N",'DF','DA',"'G817",'D087018','2/12/2024']);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

public function exportar(Request $request)
{

            $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

            $request->validate([
        'fechaincio' => 'required|date',
        'fechafin' => 'required|date|after_or_equal:fechaincio',
        'fkTienda' => 'required|exists:tienda,idTienda',
    ]);

    $inicio = Carbon::parse($request->fechaincio)->startOfDay();
$fin = Carbon::parse($request->fechafin)->endOfDay();

$datos = Eta::where('fkTienda', $request->fkTienda)
    ->whereBetween('created_at', [$inicio, $fin])
    ->get();

    // Encabezado del CSV
    $csv = "Orden,SKU,Descripcion,Cantidad,Serie,MAC1,MAC2,MAC3,TIPO_DE_SERVICIO,TIPO_DE_ORDEN,CENTRO,EMPLEADO,created_at\n";

    // Agregar datos
    foreach ($datos as $item) {

        $csv .= implode(",", [
            $item->Orden,
            $item->SKU,
            '"' . str_replace('"', '""', $item->Descripcion) . '"', // Escapar comillas dobles
            $item->Cantidad,
            $item->Serie,
            $item->MAC1,
            $item->MAC2,
            $item->MAC3,
            $item->TIPO_DE_SERVICIO,
            $item->TIPO_DE_ORDEN,
            $item->CENTRO,
            $item->EMPLEADO,
            $item->created_at
        ]) . "\n";
    }

    // Retornar respuesta para descarga
    $nombreArchivo = 'etadirect_export_' . now()->format('Ymd_His') . '.csv';

    return Response::make($csv, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$nombreArchivo\"",
    ]);
}

    public function destroy(string $id)
    {
        try {

                        if(!Auth::check()){
            return redirect()->route('login');
        }

            Eta::destroy('id',$id);

            return redirect()->route('materialmanoobra.index')->with('success', 'Eliminado Exitosamente');
        } catch (Exception $e) {
            Log::error('Error al cambiar estado del MAMO - ID: ' . $id . ' - Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar el estado del cliente.');
        }
    }
}
