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
        ->whereIn('clase_movimiento', ['251', '252', '221']) // Salida, Devolución, Consumo
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
    if(!Auth::check()){
        return redirect()->route('login');
    }

    $fkTienda = session('user_fkTienda');
    
    // Configuraciones de límite
    set_time_limit(0); // Intentar remover límite de PHP
    ini_set('memory_limit', '512M');
    
    // Desactivar logs de consultas (Crucial en Laravel para no agotar la RAM)
    DB::connection()->disableQueryLog();

    $request->validate([
        'archivo' => 'required|file|mimes:csv,txt',
    ]);

    $file = fopen($request->file('archivo')->getRealPath(), 'r');
    $encabezado = fgetcsv($file);

    $insertados = 0;
    $omitidos = 0;
    
    $batchSize = 500; // Reducido a 500 para evitar payloads gigantes en Cloud SQL
    $batchData = [];
    $now = now();

    try {
        while (($linea = fgetcsv($file)) !== false) {
            // Validar que la línea coincida con el número de columnas del encabezado
            if (count($encabezado) !== count($linea)) {
                $omitidos++;
                continue;
            }

            $data = array_combine($encabezado, $linea);

            if (empty($data['Cantidad']) || empty($data['Orden']) || empty($data['SKU'])) {
                $omitidos++;
                continue;
            }

            // Validación de fecha optimizada sin capturar excepciones pesadas
            $fechaRaw = $data['created_at'] ?? null;
            $fecha = ($fechaRaw && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaRaw)) 
                ? Carbon::createFromFormat('d/m/Y', $fechaRaw)->format('Y-m-d') 
                : $now->format('Y-m-d');

            $batchData[] = [
                'Orden'            => $data['Orden'],
                'SKU'              => $data['SKU'],
                'Descripcion'      => mb_convert_encoding($data['Descripcion'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'Cantidad'         => $data['Cantidad'],
                'Serie'            => mb_convert_encoding($data['Serie'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'MAC1'             => mb_convert_encoding($data['MAC1'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'MAC2'             => mb_convert_encoding($data['MAC2'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'MAC3'             => mb_convert_encoding($data['MAC3'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'TIPO_DE_SERVICIO' => mb_convert_encoding($data['TIPO_DE_SERVICIO'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'TIPO_DE_ORDEN'    => mb_convert_encoding($data['TIPO_DE_ORDEN'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'CENTRO'           => mb_convert_encoding($data['CENTRO'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'EMPLEADO'         => mb_convert_encoding($data['EMPLEADO'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'Naturaleza'       => 'S',
                'Status'           => 'Pe',
                'fkTienda'         => $fkTienda,
                'created_at'       => $fecha,
                'updated_at'       => $now,
            ];

            $insertados++;

            if (count($batchData) >= $batchSize) {
                // Transacciones atómicas SOLO por lote, no globales
                DB::transaction(function () use ($batchData) {
                    $this->insertOrUpdateBatch($batchData);
                });
                $batchData = [];
                gc_collect_cycles(); // Forzar limpieza de basura de PHP inmediatamente
            }
        }

        if (!empty($batchData)) {
            DB::transaction(function () use ($batchData) {
                $this->insertOrUpdateBatch($batchData);
            });
        }

        fclose($file);

        return back()->with('success', "Importación completada: {$insertados} filas procesadas, {$omitidos} omitidas.");

    } catch (\Exception $e) {
        if (is_resource($file)) {
            fclose($file);
        }
        return back()->with('error', 'Error crítico en Cloud: ' . $e->getMessage());
    }
}


private function ejecutarLogicaInterna($orden, $item, &$procesados, &$rastro)
{
$centrosEspeciales = ["'G845", "'G830", "'G888", "'G840"];
$patronG8 = "'G888";

$esEspecial = false;
// Verificamos si es especial (por lista o por patrón 'G8)
if (str_contains($item->CENTRO, $patronG8)) {
    $esEspecial = true;
}

// 1. Aseguramos que el centro se limpie o valide bien
$centroLimpio = $item->CENTRO;
$centrosEspeciales = ["'G845", "'G830", "'G888", "'G840"];
$patronG8 = "'G8";

// Forzamos la detección de especial
$esEspecial = false;
if (str_contains($centroLimpio, $patronG8)) {
    $esEspecial = true;
}

if ($esEspecial) {
    // CONSULTA ESPECÍFICA (Prioridad 1)
    // Usamos selectRaw para añadir una columna 'prioridad'
    $especifica = Material_relaciones::selectRaw("*, 1 as prioridad")
        ->where('skufinal', 'like','%'.trim($centroLimpio) . trim($item->SKU).'%')
        ->where('fkTienda', session('user_fkTienda'))
        ->where('minimo', '>=', 1);

    // CONSULTA GENERAL (Prioridad 2)
    $general = Material_relaciones::selectRaw("*, 2 as prioridad")
        ->where('depende_SKU', $item->SKU)
        ->where('minimo', '>=', 1)
        ->where('fkTienda', session('user_fkTienda'))
        ->where(function($q) use ($centrosEspeciales, $patronG8) {
            foreach ($centrosEspeciales as $ce) {
                $q->where('skufinal', 'like', '%' . $ce . '%');
            }
            $q->where('skufinal', 'like', '%' . $patronG8 . '%');
        });

$generalTOTAL = Material_relaciones::selectRaw("*, 2 as prioridad")
    ->where('depende_SKU', $item->SKU)
    ->where('fkTienda', session('user_fkTienda'))
    ->where('minimo', '>=', 1)
    ->where(function($q) use ($patronG8, $item) {
        $q->where('skufinal', 'not like', $patronG8 . '%');
        $q->Where('skufinal', 'not like', $item->SKU.'%');
    });

    // Unimos y GENERAL FINAL
    $relaciones = $especifica->unionAll($general)->unionAll($generalTOTAL)
        ->orderBy('prioridad', 'ASC')
        ->orderBy('id', 'ASC')
        ->get();

} else {
    // Centros normales
    $relaciones = Material_relaciones::where('depende_SKU', $item->SKU)
        ->where('minimo', '>=', 1)
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


if ($relaciones->isEmpty()) {
    return;
}


    foreach ($relaciones as $relacion) {
        // 3. Conteo de precisión: Solo cuenta lo que existe en la Orden actual
        $conteo = Material_relaciones::where('depende_SKU', $relacion->SKU)
            ->whereExists(function ($q) use ($orden) {
                $q->select(DB::raw(1))
                  ->from('ETA')
                  ->whereColumn('ETA.SKU', 'material_relaciones.SKU')
                  ->where('ETA.Orden', $orden);
            })->count();

        // 4. Caso especial: Acumulado de categoría (Máximo 10000)
        if ($relacion->maximo == 10000) {
            $monto = DB::selectOne("
                SELECT SUM(e.Cantidad) AS total 
                FROM Eta e 
                INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU 
                INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id 
                INNER JOIN (
                    SELECT tmc.SKU FROM treematerialescategoria tm 
                    INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id 
                    WHERE tm.SKU = ?
                ) as tmcp on tmc.SKU = tmcp.SKU 
                WHERE e.Orden = ?", [$item->SKU, $orden]);
            
            $item->Cantidad = $monto->total ?? 0;
        }

        // 5. Obtener información jerárquica del padre (Treematerialescategoria)
        $padre = DB::table('treematerialescategoria as tm')
            ->join('treematerialescategoria as tmc', 'tm.padre_id', '=', 'tmc.id')
            ->where('tm.SKU', $relacion->SKU)
            ->where('tmc.fkTienda', session('user_fkTienda'))
            ->select('tmc.SKU', 'tmc.nombre', 'tmc.minimo', 'tmc.limite', 'tmc.tipo', 'tmc.valor')
            ->first();

        // 6. Iniciar Autómata Recursivo si encontramos la información del padre
        if ($padre) {
            $this->AutomataRecursivo(
                $relacion->SKU,      // skuActual
                $orden,               // orden
                $conteo,              // recuento
                $item->Cantidad,      // valor
                $relacion->maximo ?? 0,
                $relacion->minimo ?? 0,
                $relacion->formula ?? '',
                $procesados,
                $relacion->tipo_relacion,
                0,                    // val (inicial)
                $rastro,
                $item->SKU,           // skuOrigen
                $relacion->SKU,       // skuOrigenraiz
                0,                    // nivel
                $relacion->skufinal,
                $padre,
                $item->CENTRO,
                $relacion->nombre
            );
        }
    }
}

private function ejecutarLogicaInternaVista($orden, $item, &$procesados, &$rastro, array $itemsSimulados)
{
    $centrosEspeciales = ["'G845", "'G830", "'G888", "'G840"];
    $patronG8 = "'G8";

    // 💡 CORRECCIÓN DE SINTAXIS: Accedemos como array usando las llaves del Front-end
    $centroLimpio = $item['centro'] ?? $item['CENTRO'] ?? "'G888";
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



public function AutomataRecursivoVista(
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
    string $mensaj = '',
    array $itemsSimulados = [],
    object $itemActual = null
) {
    try {
       $clave = $orden . '_' . $skuActual . '_' . $skuOrigen . '_' . $skufinal . '_' . $tipoRelacion;

        if (isset($procesados[$clave])) {
            return; 
        }

        if (in_array($clave, $rastro)) {
            return; 
        }

        $rastro[] = $clave;

        if ($nivel > ($recuento + 1) && $tipoRelacion == 'calculo') {
            return;
        }
        // Convertimos el arreglo dinámico de la vista en una colección de Laravel
        // Agrupamos por SKU para emular el "SELECT DISTINCT e.id, e.Cantidad" de tu query original
        $colVirtual = collect($itemsSimulados)->groupBy(function($item) {
            $obj = (array)$item;
            return trim($obj['SKU'] ?? $obj['sku'] ?? $obj['arraysku'] ?? '');
        })->map(function($grupo) {
            // Tomamos el primer registro de cada SKU o sumamos según la lógica de tu negocio
            return (object)[
                'SKU'      => trim($grupo->first()->SKU ?? $grupo->first()->sku ?? $grupo->first()->arraysku ?? ''),
                'Cantidad' => (float)($grupo->first()->Cantidad ?? $grupo->first()->cantidad ?? $grupo->first()->arraycantidad ?? 0)
            ];
        });

        // =========================================================================
        // 🔄 TRADUCCIÓN EXACTA DE $valor (COPIA FIEL DE TU QUERY ORIGINAL)
        // =========================================================================
        $cantidadOrigen = substr_count($skuOrigen, ".");

        if ($cantidadOrigen >= 1) {
            // Tu query original pasaba obligatoriamente [$skuActual, $orden]
            $skusJerarquiaValor = DB::table('treematerialescategoria as tm')
                ->join('treematerialescategoria as tmc', 'tm.padre_id', '=', 'tmc.id')
                ->where('tmc.fkTienda', session('user_fkTienda'))
                ->whereIn('tmc.SKU', function($q) use ($skuActual) {
                    $q->select('tmc2.SKU')
                      ->from('treematerialescategoria as tm2')
                      ->join('treematerialescategoria as tmc2', 'tm2.padre_id', '=', 'tmc2.id')
                      ->where('tmc2.SKU', trim($skuActual)) // Mantiene tmc2.SKU original
                      ->where('tmc2.fkTienda', session('user_fkTienda'));   
                })->pluck('tm.SKU')->map(function($sku) {
                    return trim($sku);
                })->toArray();

            $valor = $colVirtual->filter(function($item) use ($skusJerarquiaValor) {
                return in_array($item->SKU, $skusJerarquiaValor);
            })->sum('Cantidad');
        } else {
            // Tu query original filtraba estrictamente por [$orden, $skuOrigen]
            $valor = $colVirtual->filter(function($item) use ($skuOrigen) {
                return $item->SKU === trim($skuOrigen);
            })->sum('Cantidad');
        }

        // =========================================================================
        // 🔄 TRADUCCIÓN EXACTA DE $usado (COPIA FIEL Y CORREGIDA DE TU QUERY ORIGINAL)
        // =========================================================================
        $cantidadActual = substr_count($skuActual, ".");

        if ($cantidadActual >= 1) {
            // Tu query original pasaba obligatoriamente [$skuActual, $orden]
            $skusJerarquiaUsado = DB::table('treematerialescategoria as tm')
                ->join('treematerialescategoria as tmc', 'tm.padre_id', '=', 'tmc.id')
                ->where('tmc.fkTienda', session('user_fkTienda'))
                ->whereIn('tmc.SKU', function($q) use ($skuActual) {
                    $q->select('tmc2.SKU')
                      ->from('treematerialescategoria as tm2')
                      ->join('treematerialescategoria as tmc2', 'tm2.padre_id', '=', 'tmc2.id')
                      ->where('tmc2.fkTienda', session('user_fkTienda'))
                      // 💡 CORRECCIÓN CRÍTICA: Cambiado de tmc2.SKU a tm2.SKU para calzar con tu query
                      ->where('tmc2.SKU', trim($skuActual))
                      ->where('tmc2.fkTienda', session('user_fkTienda'));
                })->pluck('tm.SKU')->map(function($sku) {
                    return trim($sku);
                })->toArray();

            $usado = $colVirtual->filter(function($item) use ($skusJerarquiaUsado) {
                return in_array($item->SKU, $skusJerarquiaUsado);
            })->sum('Cantidad');
        } else {
            // Tu query original filtraba estrictamente por [$orden, $skuActual]
            $usado = $colVirtual->filter(function($item) use ($skuActual) {
                return $item->SKU === trim($skuActual);
            })->sum('Cantidad');
        }
if($skuActual=="34006334")  {
    logger()->info("SKU Actual: $skuActual, SKU Origen: $skuOrigen, Valor Calculado: $valor, Usado Calculado: $usado");
}
        // Inyección de variables intacta al motor de Symfony ExpressionLanguage
        $variables = [
            'minimo' => $minimo ?? 0,
            'maximo' => $maximo ?? 0,
            'valor'  => $valor ?? 0,
            'usado'  => $usado ?? 0,
            'total'  => $nivel ?? 0,
            'valant' => $val ?? 0,
        ];


        $resultado = $this->evaluarFormulaexp($formula, $variables);
        $resultadoMostrar = $resultado;
        $resultado = $resultado == 20000 ? 0 : $resultado;

        if ($resultado > 0 || $resultado < 0) {
            if ($tipoRelacion == 'requiere' || $tipoRelacion == 'incompatible') {
                $existePadreRelacion = $colVirtual->filter(function($item) use ($skuActual) {
                    return trim($item->SKU ?? $item->sku ?? '') === trim($skuActual);
                })->isNotEmpty();
                
                if ($resultado <> 0 || $existePadreRelacion) {
                    $procesados[$clave] = (object)[
                        'Orden'          => $orden,
                        'SKU_Origen'     => $skuActual,
                        'SKU_Destino'    => $skuOrigen,
                        'msj'            => $mensaj,
                        'ValorEntrada'   => $valor,
                        'Resultado'      => $resultado == 10000 ? $val : $resultado,
                        'TipoRelacion'   => $resultado < 0 ? $tipoRelacion . " - Exceso" : $tipoRelacion,
                        'formula'        => $formula,
                        'Nivel'          => $nivel,
                        'children'       => [],
                        'skuOrigenraiz'  => $skufinal,
                        'CENTRO'         => $Centro,
                        'claveunica'     => $clave,
                        'NOMBRE_Destino' => $padre->nombre ?? "SKU materia analizado " . $tipoRelacion . " " . $skuActual,
                    ];
                }
            }
        }

        $relaciones = Material_relaciones::where('skufinal', $skufinal)
        ->where('fkTienda', session('user_fkTienda'))
        ->orderBy('id', 'ASC')->get();
        foreach ($relaciones as $relacion) {
            if (in_array($orden . '_' . $relacion->SKU . '_' . $relacion->depende_SKU . '_' . $relacion->skufinal . '_' . $relacion->tipo_relacion, $rastro)) { 
                continue; 
            }

            if ($relacion->tipo_relacion == "calculo") {
                $existePadreCalculo = $colVirtual->filter(function($item) use ($relacion) {
                    return trim($item->SKU ?? $item->sku ?? '') === trim($relacion->SKU);
                })->isNotEmpty();

                if (!$existePadreCalculo && substr_count($relacion->depende_SKU, ".") == 0 && substr_count($relacion->SKU, ".") == 0) { 
                    continue; 
                }
            }

            $padreCat = DB::table('treematerialescategoria as tm')->join('treematerialescategoria as tmc', 'tm.padre_id', '=', 'tmc.id')
                ->where('tm.SKU', $relacion->SKU)
                ->where('tmc.fkTienda', session('user_fkTienda'))
                ->select('tmc.SKU', 'tmc.nombre', 'tmc.minimo', 'tmc.limite', 'tmc.tipo', 'tmc.valor')->first();
            
            if (!$padreCat) { 
                continue; 
            }

            // 🔁 RECURSIÓN CASCADA: Mantenemos las referencias dinámicas de memoria puras
            $this->AutomataRecursivoVista(
                $relacion->SKU, $orden, $recuento, $resultadoMostrar == 20000 ? $val : $resultadoMostrar, $relacion->maximo ?? 0, $relacion->minimo ?? 0, $relacion->formula ?? '',
                $procesados, $relacion->tipo_relacion, $resultadoMostrar == 20000 ? $val : $resultadoMostrar, $rastro, $skuOrigen, $skuOrigen, $nivel + 1, $relacion->skufinal, $padreCat, $Centro, $relacion->nombre, 
                $itemsSimulados,
                $relacion // ← Inyectamos el objeto actual de la relación de forma segura
            );
        }
    } catch (\Exception $e) { 
        logger()->error('Error en Autómata Recursivo Vista Puro: ' . $e->getMessage()); 
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
            $this->ejecutarLogicaInternaVista($orden, $item, $procesados, $rastro, $itemsMemoria);
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
        $this->ejecutarLogicaInternaVista($orden, $itemFormateado, $procesados, $rastro, $tablaVirtual);
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
    $procesados = []; $rastro = [];
    $limite = $request->input('Orden', 10);
    
    $mamoorden = Eta::whereBetween('created_at', [
            Carbon::parse($request->fechaincio)->startOfDay(),
            Carbon::parse($request->fechafin)->endOfDay()
        ])
        ->where('fkTienda', session('user_fkTienda'))->select('Orden')->groupBy('Orden')->limit($limite)->get();

    foreach($mamoorden as $ordenitem) {
        $items = DB::table('ETA')->select('CENTRO', 'SKU', DB::raw('SUM(cantidad) as Cantidad'))
                 ->where('fkTienda', session('user_fkTienda'))
                 ->where('Orden', $ordenitem->Orden)->groupBy('SKU', 'CENTRO')->get();

        foreach ($items as $item) {
            $this->ejecutarLogicaInterna($ordenitem->Orden, $item, $procesados, $rastro);
        }
    }

    return $this->descargarCSV($this->quitarDuplicadosPorOrdenYSKU($procesados), 'validaciones_lote.csv');
}

public function AutomataValidarMamoOrden(Request $request)
{
    if(!Auth::check()) return response()->json(['error' => 'No autorizado'], 401);
    $procesados = []; $rastro = [];
    $orden = $request->input('Orden');

    $items = DB::table('ETA')->select('CENTRO', 'SKU', DB::raw('SUM(cantidad) as Cantidad'))
             ->where('Orden', $orden)
             ->where('fkTienda', session('user_fkTienda'))
             ->groupBy('SKU', 'CENTRO')->get();

    foreach ($items as $item) {
        if($item->SKU=="1021571"){
            $A="1021571";
        }
        $this->ejecutarLogicaInterna($orden, $item, $procesados, $rastro);
    }

    $validaciones = $this->quitarDuplicadosPorOrdenYSKU($procesados);
    return response()->json(['validaciones' => $validaciones], 200, [], JSON_PRETTY_PRINT);
}

private function descargarCSV($validaciones, $nombreArchivo)
{
    if (ob_get_level()) ob_end_clean();
    return response()->streamDownload(function () use ($validaciones) {
        $handle = fopen('php://output', 'w');
        fputcsv($handle, ['Orden','SKU_Origen','SKU_Destino','msj','ValorEntrada','Resultado','TipoRelacion','formula','Nivel','skuOrigenraiz','CENTRO','NOMBRE_Destino']);
        foreach ($validaciones as $f) {
            fputcsv($handle, [
                $f->Orden, $f->SKU_Origen, $f->SKU_Destino, $f->msj, 
                $f->ValorEntrada, $f->Resultado, $f->TipoRelacion, $f->formula, 
                $f->Nivel, $f->skuOrigenraiz, $f->CENTRO, $f->NOMBRE_Destino
            ]);
        }
        fclose($handle);
    }, $nombreArchivo);
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

    // 🛑 CASO BASE 1
    if ($valor <= 0 && $nivel==0) {
        return;
    }
    $cantidad = substr_count($skuOrigen, ".");

  $clave = $orden . '_' . $skuActual. '_' .$skuOrigen . '_' . $skufinal.'_'.$tipoRelacion;
$CANT = substr_count($clave, "01.011007881TRAMO");
      if($CANT>0){
        $a="25188580_34028679_1021133_102113334028679";
    }

if (isset($procesados[$clave])) {
    return; // 🚫 Ya fue procesado este Orden + SKU
}

if (in_array($clave, $rastro)) {
    return; // 🔁 ciclo detectado
}

$rastro[] = $clave;

    if($nivel>($recuento+1) && $tipoRelacion=='calculo' ){
        return;
    }

        $cantidad=0;
    $cantidad = substr_count($skuOrigen, ".");

     if($cantidad>=1){


    $total = DB::selectOne("
SELECT SUM(total_cantidad) AS total
FROM (
    SELECT DISTINCT e.id, e.Cantidad AS total_cantidad
    FROM Eta e
    INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU
    INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
    INNER JOIN (
        SELECT DISTINCT tmc.SKU
        FROM treematerialescategoria tm
        INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
        WHERE tmc.SKU = ?
    ) AS tmcp ON tmc.SKU = tmcp.SKU
    WHERE e.Orden = ? and tmc.fkTienda = ?
) AS subconsulta
", [$skuActual,$orden, session('user_fkTienda')]);
} else{
    $total = DB::selectOne("
SELECT SUM(total_cantidad) AS total
FROM (
    SELECT DISTINCT e.id, e.Cantidad AS total_cantidad
    FROM Eta e
    INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU
    WHERE e.Orden = ? 
      AND tm.SKU = ?
      AND tm.fkTienda = ?
) AS subconsulta
", [$orden, $skuOrigen, session('user_fkTienda')]);
}

    $valor = $total->total ?? 0;

    $cantidad=0;
    $cantidad = substr_count($skuActual, ".");


    if($cantidad>=1){


    $total = DB::selectOne("
SELECT SUM(total_cantidad) AS total
FROM (
    SELECT DISTINCT e.id, e.Cantidad AS total_cantidad
    FROM Eta e
    INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU
    INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
    INNER JOIN (
        SELECT DISTINCT tmc.SKU
        FROM treematerialescategoria tm
        INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
        WHERE tmc.SKU = ?
    ) AS tmcp ON tmc.SKU = tmcp.SKU
    WHERE e.Orden = ? and tmc.fkTienda = ?
) AS subconsulta
", [$skuActual,$orden, session('user_fkTienda')]);
} else{
    $total = DB::selectOne("
SELECT SUM(total_cantidad) AS total
FROM (
    SELECT DISTINCT e.id, e.Cantidad AS total_cantidad
    FROM Eta e
    INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU
    WHERE e.Orden = ? 
      AND tm.SKU = ?
      AND tm.fkTienda = ?
) AS subconsulta
", [$orden, $skuActual, session('user_fkTienda')]);
}



    $usado = $total->total ?? 0;

$variables = [
    'minimo' => $minimo ?? 0,
    'maximo' => $maximo ?? 0,
    'valor'  => $valor ?? 0,
    'usado'  => $usado ?? 0,
    'total'  => $nivel ?? 0,
    'valant' => $val ?? 0,
];

if($clave=="25542286_02.15_34033641_02.1534033641UNIDAD_requiere"){
    $a="25188580_34028673_34028677_102113334028679";
}

$resultado=0;
    $resultado = $this->evaluarFormulaexp($formula, $variables);

$resultadoMostrar = $resultado;
$resultado= $resultado == 20000 ? 0 : $resultado;

     if($resultado > 0|| $resultado < 0) {
        if ($tipoRelacion == 'requiere'  || $tipoRelacion == 'incompatible' ) {

                $padrerel = DB::selectOne("
            SELECT distinct e.SKU FROM Eta e
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

        $relaciones = Material_relaciones::where('skufinal', $skufinal)
    ->orderBy('id', 'ASC')
    ->get();

    $conteo = Material_relaciones::where('skufinal', $skufinal)
    ->count();


    foreach ($relaciones as $relacion) {
$a=$orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion;
            if($orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion == "25658138_34028704_34028710_40144661001856_calculo"){
        $a=$orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion;
    }

    if (in_array($orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion, $rastro)) {
    continue; // 🔁 ciclo detectado
}



    $aSKUFILTRO=$clave;
        $tipoRelacion=$relacion->tipo_relacion;

      if($aSKUFILTRO==$orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion){
        $a="25188580_34028673_34028677_102113334028679";
  $clave=$orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal;
  $rastro[] = $clave;
    continue; // 🚫 Evitar procesar el mismo nodo en esta ram
    }
$SKUSSS=$relacion->SKU;
    if($tipoRelacion=="calculo" ){
          $padre = DB::selectOne("
            SELECT distinct e.SKU FROM Eta e
            inner join material_relaciones mr on e.SKU=mr.depende_SKU where e.Orden=? and mr.depende_SKU=? and mr.fkTienda=?;
    ", [$orden, $relacion->SKU, session('user_fkTienda')]);

        if (!$padre) {
             $cantidad = substr_count($relacion->depende_SKU, ".");
        if($cantidad==0){
            $cantidad = substr_count($relacion->SKU, ".");
        if($cantidad==0){
             continue;
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

    if($aSKUFILTRO=="25298449_34025712_4018238_401823834028673_calculo"){
        $a="25188580_34028673_34028677_102113334028679";
    }

        // 🔁 llamada recursiva
        $this->AutomataRecursivo(
            $relacion->SKU,
            $orden,
            $conteo,
            $resultadoMostrar == 20000 ? $val : $resultadoMostrar,
            $relacion->maximo   ?? 0,
            $relacion->minimo   ?? 0,
            $relacion->formula,
            $procesados,
            $relacion->tipo_relacion,
            $resultadoMostrar == 20000 ? $val : $resultadoMostrar,
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




private function evaluarFormulaexp(string $formula, array $variables)
{
    try {

        $exp = new \Symfony\Component\ExpressionLanguage\ExpressionLanguage();

        // round
        $exp->register(
            'round',
            fn ($value, $precision = 0) => sprintf('round(%s, %s)', $value, $precision),
            fn ($variables, $value, $precision = 0) => round($value, $precision)
        );

        // floor (ENTERO en Excel)
        $exp->register(
            'floor',
            fn ($value) => sprintf('floor(%s)', $value),
            fn ($variables, $value) => floor($value)
        );

        // ceil
        $exp->register(
            'ceil',
            fn ($value) => sprintf('ceil(%s)', $value),
            fn ($variables, $value) => ceil($value)
        );

        // max
        $exp->register(
            'max',
            fn (...$args) => sprintf('max(%s)', implode(',', $args)),
            fn ($variables, ...$args) => max(...$args)
        );

        // min
        $exp->register(
            'min',
            fn (...$args) => sprintf('min(%s)', implode(',', $args)),
            fn ($variables, ...$args) => min(...$args)
        );

        return $exp->evaluate($formula, $variables);

    } catch (\Throwable $e) {
        Log::error('Error al evaluar fórmula: ' . $e->getMessage());
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
