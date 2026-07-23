<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompraRequest;
use App\Http\Requests\UpdateCompraRequest;
use App\Models\Compra;
use App\Models\Comprobante;
use App\Models\CuentaContable;
use App\Models\DetalleFolio;
use App\Models\Folio;
use App\Models\Producto;
use App\Models\Proveedore;
use App\Models\Tienda;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use ZipArchive;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Models\Categoria;
use App\Models\Centro;
use App\Models\Marca;
use App\Models\Presentacione;
use App\Models\Lote;
use App\Models\CompraProducto;
use App\Models\DetalleComprobante;
use App\Models\Lotesalarma;
use App\Models\MovimientoMateriales;
use Illuminate\Support\Facades\Auth;
use MathParser\StdMathParser;
use MathParser\Interpreting\Evaluator;


class compraController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:reporte-compra|ver-compra|crear-compra|mostrar-compra|eliminar-compra', ['only' => ['index']]);
        $this->middleware('permission:crear-compra', ['only' => ['create', 'store']]);
        $this->middleware('permission:mostrar-compra', ['only' => ['show']]);
        $this->middleware('permission:eliminar-compra', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

                    if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        // Inicializa las variables que se pasarán a la vista
    // Inicializa las variables como arrays vacíos
    $compras = [];
    $productos = [];

        // Si el estatus es 'ER', cargar todas las compras
        if ($Estatus == 'ER') {
            $compras = Compra::with('comprobante', 'proveedore.persona', 'tienda')
                ->where('estado', 2)
                ->where('ClaveVista','DC')
                ->whereNotNull('proveedore_id')
                ->latest()
                ->get();

                            // Filtrar los productos solo por la tienda del usuario
            $productos = Producto::with('comprobante','proveedore.persona','tienda')
            ->where('fkTienda', $fkTienda)
            ->where('ClaveVista','DC')
            ->whereIn('estado', [1,2,3])
            ->latest()
            ->get();
        } else {
            $compras = Compra::with('comprobante', 'proveedore.persona', 'tienda')
            ->where('estado', 2)
            ->where('fkTienda', $fkTienda)
            ->whereNotNull('proveedore_id')
            ->latest()
            ->get();
            // Filtrar los productos solo por la tienda del usuario
            $productos = Producto::with('comprobante','proveedore.persona','tienda')
            ->where('fkTienda', $fkTienda)
            ->whereIn('estado', [1,2,3])
            ->latest()
            ->get();
        }

        // Pasar tanto compras como productos a la vista (si ambos son necesarios)
        return view('compra.index', compact('compras', 'productos'));
    }

    private function evaluarFormula($formula, $A)
{
    try {
        if (empty($formula)) return 0;

        // 1. Convertir porcentajes (12% → 0.12)
        $formula = preg_replace_callback('/(\d+(\.\d+)?)%/', function ($match) {
            return $match[1] / 100;
        }, $formula);

        // 2. Reemplazar la variable A por el valor numérico
        $formula = str_replace('A', $A, $formula);

        // 3. Parsear expresión usando la librería del proyecto
        $parser = new StdMathParser();
        $AST = $parser->parse($formula);

        // 4. Evaluar el árbol de sintaxis abstracta
        $evaluator = new Evaluator();
        $resultado = $AST->accept($evaluator);

        return round($resultado, 2);

    } catch (\Exception $e) {
        return 0;
    }
}

    public function create()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');
        $cuentasContables = CuentaContable::where('fkTienda', $fkTienda)->get();

        $proveedores = Proveedore::whereHas('persona',function($query){
            $query->where('estado',1);
        })->get();
        $comprobantes = Comprobante::whereHas('tienda',function($query) use ($fkTienda){
            $query->where('fkTienda',$fkTienda);
        })->where('estado',1)
        ->where('ClaveVista','DC')->get();
            // Si el estatus es 'ER', cargar todos los productos
    if ($Estatus == 'ER') {
        $productos = Producto::where('estado',1)->get();
    } else {
        // Filtrar los productos solo por la tienda del usuario
        $productos = Producto::whereHas('tienda', function($query) use ($fkTienda) {
            $query->where('fkTienda', $fkTienda);
        })->where('estado', 1)->get();
    }
        return view('compra.create',compact('cuentasContables','proveedores','comprobantes','productos'));
    }

public function store(StoreCompraRequest $request)
{
    try {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $lockKey = 'submit_venta_' . auth()->id();

        DB::beginTransaction();
        $fkTienda = session('user_fkTienda');
        $id = auth()->id();
        


        // 1. Recuperar los arrays de la petición
        $arrayProducto_id = $request->get('arrayidproducto');
        $arrayCantidad = $request->get('arraycantidad');
        $arrayPrecioCompra = $request->get('arraypreciocompra');
        $arrayPrecioVenta = $request->get('arrayprecioventa');
        $arraysubiva = $request->get('arraysubiva');
        $arrayFechaVencimiento = $request->get('arrayfecha_vencimiento');

        $tipofolio = $request->get('TipoFolio');
        $total = $request->total ?? 0;
        $proveedor_id = $request->proveedore_id;
        $comprobante_id = $request->comprobante_id;
        $numero_comprobante = $request->numero_comprobante;
        $impuestotal = $request->impuesto ?? 0;
        $fecha = $request->fecha;
        $fecha_hora = $request->fecha_hora;
        $arrayidcuenta = $request->get('arrayidcuenta');
        $arraymonto = $request->get('arraymonto');
        $arraytipomovimiento = $request->get('arraytipomovimiento');

        
        
        if ($numero_comprobante == 0) {
            $comprobantenomenclatura = Comprobante::where('id', $comprobante_id)->first();

            $ultimoNumero = Compra::where('fkTienda', $fkTienda)
                ->lockForUpdate()
                ->count('numero_comprobante');

            $numero_comprobante = $ultimoNumero ? $ultimoNumero + 1 : 1;
            $numero_comprobante = $numero_comprobante . $tipofolio . $comprobantenomenclatura->ClaveVista . $comprobantenomenclatura->id;
        }

        // Llenar tabla compras
        $compra = Compra::create([
            'fecha_hora' => $fecha_hora,
            'impuesto' => $impuestotal,
            'numero_comprobante' => $numero_comprobante,
            'total' => $total + $impuestotal,
            'estado' => 2,
            'fkUserCreate' => $id,
            'fkUserCC' => $id,
            'comprobante_id' => $comprobante_id,
            'proveedore_id' => $proveedor_id,
            'create_at' => $fecha,
            'update_at' => $fecha,
            'fkTienda' => $fkTienda
        ]);

        $folio = Folio::create([
            'descripcion' => 'Compra n.' . $compra->id . ', por un total de Q. ' . ($total + $impuestotal) . ', numero de comprobante: ' . $numero_comprobante . '.',
            'cabecera' => 'Compra registrada por almacén.',
            'EstatusContable' => 'C',
            'TipoFolio' => $tipofolio,
            'FechaContabilizacion' => now(),
            'fkUsuario' => $id,
            'fkComprobante' => $comprobante_id,
            'created_at' => now(),
            'updated_at' => now(),
            'fkTienda' => $fkTienda,
            'idOrigen' => $compra->id,
            'TipoMovimiento' => 'C'
        ]);

        // Lógica de nomenclatura de comprobante
        preg_match_all('/\d+/', $numero_comprobante, $coincidencias);
        if (!empty($coincidencias[0])) {
            $primerNumero = $coincidencias[0][0];
            $ultimoNumero = $coincidencias[0][count($coincidencias[0]) - 1];
        } else {
            $primerNumero = ''; $ultimoNumero = '';
        }

        // Llenar DetalleFolio
        $cuentaArray = count($arrayidcuenta ?? []);
        $cont = 0;
        while ($cont < $cuentaArray) {
            DetalleFolio::create([
                'Monto' => $arraymonto[$cont],
                'Naturaleza' => $arraytipomovimiento[$cont],
                'fkCuenetaContable' => $arrayidcuenta[$cont],
                'fkUsuario' => $id,
                'fkTienda' => $fkTienda,
                'fkFolio' => $folio->idFolio,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $cont++;
        }

// ==========================================
// PASO 1: CONSOLIDACIÓN DIRECTA Y LIMPIA
// ==========================================
$productosConsolidados = [];

// Forzamos índices secuenciales limpios (0, 1, 2...) para evitar desalineaciones
$idsProductos = array_values($arrayProducto_id ?? []);
$cantidades   = array_values($arrayCantidad ?? []);
$fechas       = array_values($arrayFechaVencimiento ?? []);
$preciosComp  = array_values($arrayPrecioCompra ?? []);
$preciosVent  = array_values($arrayPrecioVenta ?? []);
$impuestos    = array_values($arraysubiva ?? []);

foreach ($idsProductos as $index => $id) {
    
    // Captura directa y conversión a entero sin alterar el formato nativo
    $cantidadNumerica = isset($cantidades[$index]) ? intval($cantidades[$index]) : 0;

    // Si el ID del producto no es válido o la cantidad es 0 o menor, saltamos la fila
    if (intval($id) <= 0 || $cantidadNumerica <= 0) {
        continue;
    }

    // Normalizar el resto de variables de forma segura
    $fecha   = !empty($fechas[$index]) ? $fechas[$index] : 'N/A';
    $pCompra = number_format(floatval($preciosComp[$index] ?? 0), 2, '.', '');
    $pVenta  = number_format(floatval($preciosVent[$index] ?? 0), 2, '.', '');
    
    // Generar la llave única combinada de agrupación
    $key = $id . '_' . $fecha . '_' . $pCompra . '_' . $pVenta;

    if (!isset($productosConsolidados[$key])) {
        $productosConsolidados[$key] = [
            'id'            => intval($id),
            'cantidad'      => 0, // Inicializador del acumulador
            'precio_compra' => floatval($pCompra),
            'precio_venta'  => floatval($pVenta),
            'Naturaleza' => 'D',
            'impuesto'      => 0,
            'fecha'         => $fecha
        ];
    }
    
    // Sumamos la cantidad numérica real de la petición
    $productosConsolidados[$key]['cantidad'] += $cantidadNumerica;
    $productosConsolidados[$key]['impuesto'] += floatval($impuestos[$index] ?? 0);
}

$productosConsolidados = array_values($productosConsolidados);


// ==========================================
// PASO 2: INSERCIÓN EN BASE DE DATOS (BLINDADO CONTRA CONFIGURACIONES DE BASE DE DATOS)
// ==========================================
$posicion = 1;
$lotesPorActualizar = []; // Arreglo temporal para guardar los IDs y las cantidades reales

foreach ($productosConsolidados as $item) {
    $idLoteGenerado = null;
    $producto = Producto::find($item['id']);
    $cantidadLote = isset($item['cantidad']) ? intval($item['cantidad']) : 0;

    if ($item['fecha'] != 'N/A' && !empty($item['fecha'])) {
        $fechaFormateada = date('Ymd', strtotime($item['fecha']));
        $numeroLote = 'L-' . $fechaFormateada . '.' . $compra->id . '.' . $posicion;

        $idLoteGenerado = DB::table('lotesalarma')->insertGetId([
            'producto_id'       => $item['id'],
            'numero_lote'       => $numeroLote,
            'cantidad'          => $cantidadLote, 
            'fecha_vencimiento' => $item['fecha'],
            'fkTienda'          => $fkTienda,
            'compra_id'         => $compra->id,
            'notificado'        => 0,
            'created_at'        => now(),
            'updated_at'        => now()
        ]);

        // Guardamos la referencia para la actualización en caliente al final del ciclo
        $lotesPorActualizar[] = [
            'id'       => $idLoteGenerado,
            'cantidad' => $cantidadLote
        ];
    }

    // Insertar en detalle de compra (Pivote) -> Esto dispara los Triggers contables
    $compra->productos()->attach([
        $item['id'] => [
            'cantidad'      => $cantidadLote,
            'precio_compra' => $item['precio_compra'],
            'precio_venta'  => $item['precio_venta'],
            'impuesto'      => $item['impuesto'],
            'fkTienda'      => $fkTienda,
            'fkLote'        => $idLoteGenerado,
            'Naturaleza'    => 'D',
            'Estado'        => 'I'
        ]
    ]);

    $centro = Centro::join('tienda', 'centro.id', '=', 'tienda.fkCentro')
        ->where('tienda.idTienda', session('user_fkTienda'))
        ->select('centro.*', 'tienda.nombre as nombre_tienda')
        ->first();

    // Registro en Kardex (Movimientos)
    MovimientoMateriales::create([
        'fkTienda'              => $fkTienda,
        'fkMateriales'          => $item['id'],
        'fkLotes'               => $idLoteGenerado,
        'clase_movimiento'      => '641',
        'tipo_movimiento'       => 'COMPRA',
        'cantidad'              => $cantidadLote,
        'documento_material'    => 'COM-' . $compra->numero_comprobante,
        'referencia'            => "Comp ID: ||{$compra->id}||",
        'fecha_contabilizacion' => now(),
        'centro'                => session('centro') ?? ($centro->codigo ?? 'N/A'),
        'almacen'               => session('centro') ?? ($centro->codigo ?? 'N/A'),
        'origen_uso'            => 'compra_nacional',
        'unidad_medida_base'    => 'PZA',
        'posicion_documento'    => $posicion
    ]);

    $posicion++; 
}

// ==========================================================
// PASO 3: SOBREESCRITURA DE SEGURIDAD (POST-TRIGGERS)
// ==========================================================
// Recorremos los lotes guardados y forzamos el valor real directo por ID primario.
// Esto se ejecuta después de que todos los triggers terminaron de calcular el stock.
foreach ($lotesPorActualizar as $loteUpdate) {
    DB::table('lotesalarma')
        ->where('id', $loteUpdate['id'])
        ->update(['cantidad' => $loteUpdate['cantidad']]);
}


        DB::commit();
        return redirect()->route('compras.index')->with('success', 'Compra registrada con éxito.');

} catch (\Exception $e) {
    DB::rollBack();
    
    // Devolvemos una respuesta JSON con código 500 para que AJAX la detecte en la sección de error
    return response()->json([
        'error' => 'Error al guardar la compra: ' . $e->getMessage()
    ], 500);
}

}

// =========================================================================
// PARTE 1: INICIALIZACIÓN, TRANSACCIÓN Y GESTIÓN DE PROVEEDOR (ÍNDICE 0)
// =========================================================================
public function storeMasivoExcel(Request $request)
{
    try {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        DB::beginTransaction();

        $fkTienda = session('user_fkTienda');
        $idUsuario = auth()->id();

        // =========================================================================
        // PARTE 1: INDEXAR O CREAR EL PROVEEDOR RAÍZ
        // =========================================================================
        $proveedor_id = $request->proveedore_id;
        $arrayNits = $request->get('array_proveedor_nit') ?? [];
        $arrayNoms = $request->get('array_proveedor_nombre') ?? [];
        
$nombreProveedor = (!empty($arrayNoms) && is_array($arrayNoms)) ? trim($arrayNoms[0]) : (is_string($arrayNoms) ? trim($arrayNoms) : 'Proveedor Masivo');
$numeroDocumento = (!empty($arrayNits) && is_array($arrayNits)) ? trim($arrayNits[0]) : (is_string($arrayNits) ? trim($arrayNits) : null);
        

        if (empty($proveedor_id) && !empty($numeroDocumento)) {
            $personaExistente = DB::table('personas')
                ->where('numero_documento', $numeroDocumento)
                ->first();

            if (!$personaExistente) {
                $personaId = DB::table('personas')->insertGetId([
                    'razon_social'     => $nombreProveedor,
                    'direccion'        => $request->get('proveedor_direccion', 'Ciudad'),
                    'tipo_persona'     => 'Juridica',
                    'estado'           => 1, 
                    'documento_id'     => $request->get('documento_id', 1), 
                    'numero_documento' => $numeroDocumento,
                    'created_at'       => now(),
                    'updated_at'       => now()
                ]);
            } else {
                $personaId = $personaExistente->id;
            }

            $proveedorExistente = DB::table('proveedores')
                ->where('persona_id', $personaId)
                ->first();

            if (!$proveedorExistente) {
                $proveedor_id = DB::table('proveedores')->insertGetId([
                    'persona_id' => $personaId,
                    'fkTienda'   => $fkTienda,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                $proveedor_id = $proveedorExistente->id;
            }
        }

        if (empty($proveedor_id)) {
            throw new \Exception("No se pudo determinar o registrar un Proveedor válido con el NIT proporcionado.");
        }
              // =========================================================================
        // PARTE 2: BUCLE ITERADOR - CONTROL MULTITIENDA Y CATÁLOGOS
        // =========================================================================
        $arrayCodigos      = array_values($request->get('array_codigo') ?? []);
        $arrayNombres      = array_values($request->get('array_nombre') ?? []);
        $arrayMarcasTxt    = array_values($request->get('array_marca_nombre') ?? []);
        $arrayPresentasTxt = array_values($request->get('array_presentacion_nombre') ?? []);
        $arrayCategoriasTxt= array_values($request->get('array_categoria_nombre') ?? []); 
        $arrayPerecedero   = array_values($request->get('array_perecedero') ?? []);

        $arrayCantidad         = array_values($request->get('arraycantidad') ?? []);
        $arrayPrecioCompra     = array_values($request->get('arraypreciocompra') ?? []);
        $arrayPrecioVenta      = array_values($request->get('arrayprecioventa') ?? []);
        $arraysubiva           = array_values($request->get('arraysubiva') ?? []);
        $arrayFechaVencimiento = array_values($request->get('arrayfecha_vencimiento') ?? []);

        $mapaIdsProductos = [];
        $subtotalAcumuladoGlobal = 0; // Guardará la suma de CANTIDAD * PRECIO_COMPRA

// 1. Crear el encabezado de la compra antes del ciclo para obtener su ID
$compra = Compra::create([
    'fkTienda'           => $fkTienda,
    'fkProveedor'        => $idProveedorFinal ?? null, 
    'numero_comprobante' => 'MASIVA-' . time(),        
    'fecha_compra'       => now()->toDateString(),
    'fecha_hora'         => now(), 
    'total'              => 0,                         
    'impuesto'           => 0, 
    'estado'             => 2, // <--- CAMBIA 'I' POR EL ENTERO 0 (O 1 SEGÚN CORRESPONDA)
    'created_at'         => now(),
    'updated_at'         => now()
]);
// Inicializamos la posición tal como lo haces en tu otra función.
$posicion = 1; 

foreach ($arrayCodigos as $index => $codigoFilaRaw) {
    // 1. Extraer los datos de forma segura (Previene error de arrays/strings)
    $codigoFila = is_array($codigoFilaRaw) ? trim(head($codigoFilaRaw)) : trim($codigoFilaRaw);
    
    $cantFilaRaw = $arrayCantidad[$index] ?? 0;
    $cantFila = is_array($cantFilaRaw) ? intval(head($cantFilaRaw)) : intval($cantFilaRaw);
    
    $precFilaRaw = $arrayPrecioCompra[$index] ?? 0;
    $precFila = is_array($precFilaRaw) ? floatval(head($precFilaRaw)) : floatval($precFilaRaw);
    
    $nombreFilaRaw = $arrayNombres[$index] ?? 'Producto Sin Nombre';
    $nombreFila = is_array($nombreFilaRaw) ? trim(head($nombreFilaRaw)) : trim($nombreFilaRaw);
    
    // Asumimos un precio de venta o impuesto por defecto si no vienen en la carga masiva
    $precioVentaFila = isset($arrayPrecioVenta[$index]) ? floatval(is_array($arrayPrecioVenta[$index]) ? head($arrayPrecioVenta[$index]) : $arrayPrecioVenta[$index]) : ($precFila * 1.30); 
    $impuestoFila = isset($arrayImpuesto[$index]) ? floatval(is_array($arrayImpuesto[$index]) ? head($arrayImpuesto[$index]) : $arrayImpuesto[$index]) : 0;

    $subtotalAcumuladoGlobal += ($cantFila * $precFila);
    
    // 2. Solución para productos sin código (SG o Vacíos)
    if (strcasecmp($codigoFila, 'SG') === 0 || empty($codigoFila)) {
        $productoExistentePorNombre = DB::table('productos')
            ->where('nombre', $nombreFila)
            ->where('fkTienda', $fkTienda)
            ->first();

        if ($productoExistentePorNombre) {
            $codigoFila = $productoExistentePorNombre->codigo;
        } else {
            $existeCodigo = true;
            $intentos = 0;
            while ($existeCodigo && $intentos < 100) {
                $intentos++;
                $seed = str_replace('.', '', microtime(true)) . $index;
                $base = str_pad("20" . substr($seed, -10), 12, "0", STR_PAD_RIGHT);
                $suma = 0;
                for ($i = 0; $i < 12; $i++) {
                    $suma += ($i % 2 === 0) ? (int)$base[$i] : (int)$base[$i] * 3;
                }
                $codigoFila = $base . ((10 - ($suma % 10)) % 10);
                $existeCodigo = DB::table('productos')->where('codigo', $codigoFila)->exists();
            }
        }
    }

    // 3. Buscar existencia en la Base de Datos
    $productoEnTiendaActual = DB::table('productos')->where('codigo', $codigoFila)->where('fkTienda', $fkTienda)->first();
    $productoEnOtraTienda   = DB::table('productos')->where('codigo', $codigoFila)->first();

    // Determinar ID del producto (Existente o Nuevo)
    if ($productoEnTiendaActual) {
        $idProductoFinal = $productoEnTiendaActual->id;
    } elseif ($productoEnOtraTienda) {
        $idProductoFinal = $productoEnOtraTienda->id;
    } else {
        // NO EXISTE: Resolver catálogos e insertar nuevo producto
        
        $marcaNombre = trim(is_array($arrayMarcasTxt[$index] ?? 'Generico') ? head($arrayMarcasTxt[$index]) : ($arrayMarcasTxt[$index] ?? 'Generico'));
        $marcaExistente = DB::table('marcas')
            ->join('caracteristicas', 'marcas.caracteristica_id', '=', 'caracteristicas.id')
            ->where('caracteristicas.nombre', $marcaNombre)->select('marcas.id')->first();
        $idMarcaFinal = $marcaExistente ? $marcaExistente->id : DB::table('marcas')->insertGetId(['caracteristica_id' => DB::table('caracteristicas')->insertGetId(['nombre' => $marcaNombre, 'created_at' => now(), 'updated_at' => now()]), 'created_at' => now(), 'updated_at' => now()]);

        $presentacionNombre = trim(is_array($arrayPresentasTxt[$index] ?? 'Generico') ? head($arrayPresentasTxt[$index]) : ($arrayPresentasTxt[$index] ?? 'Generico'));
        $presentacionExistente = DB::table('presentaciones')
            ->join('caracteristicas', 'presentaciones.caracteristica_id', '=', 'caracteristicas.id')
            ->where('caracteristicas.nombre', $presentacionNombre)->select('presentaciones.id')->first();
        $idPresentacionFinal = $presentacionExistente ? $presentacionExistente->id : DB::table('presentaciones')->insertGetId(['caracteristica_id' => DB::table('caracteristicas')->insertGetId(['nombre' => $presentacionNombre, 'created_at' => now(), 'updated_at' => now()]), 'created_at' => now(), 'updated_at' => now()]);

        $categoriaNombre = trim(is_array($arrayCategoriasTxt[$index] ?? 'General') ? head($arrayCategoriasTxt[$index]) : ($arrayCategoriasTxt[$index] ?? 'General'));
        $categoriaExistente = DB::table('categorias')
            ->join('caracteristicas', 'categorias.caracteristica_id', '=', 'caracteristicas.id')
            ->where('caracteristicas.nombre', $categoriaNombre)->select('categorias.id')->first();
        $idCategoriaFinal = $categoriaExistente ? $categoriaExistente->id : DB::table('categorias')->insertGetId(['caracteristica_id' => DB::table('caracteristicas')->insertGetId(['nombre' => $categoriaNombre, 'created_at' => now(), 'updated_at' => now()]), 'created_at' => now(), 'updated_at' => now()]);

        $fechaVencRaw = trim(is_array($arrayFechaVencimiento[$index] ?? '') ? head($arrayFechaVencimiento[$index]) : ($arrayFechaVencimiento[$index] ?? ''));
        $fechaVencimientoMySQL = (!empty($fechaVencRaw) && $fechaVencRaw !== 'N/A') 
            ? date('Y-m-d', strtotime(str_replace('/', '-', $fechaVencRaw))) 
            : null;

        $idProductoFinal = DB::table('productos')->insertGetId([
            'codigo'            => $codigoFila, 
            'nombre'            => $nombreFila, 
            'precio_base'       => $precFila,
            'stock'             => 0, // Inicia en 0, tus triggers o flujos le sumarán stock
            'descripcion'       => $nombreFila, 
            'fecha_vencimiento' => $fechaVencimientoMySQL,
            'estado'            => 1, 
            'marca_id'          => $idMarcaFinal, 
            'presentacione_id'  => $idPresentacionFinal,
            'perecedero'        => is_array($arrayPerecedero[$index] ?? 0) ? head($arrayPerecedero[$index]) : ($arrayPerecedero[$index] ?? 0), 
            'stock_minimo'      => 5, 
            'fkTienda'          => $fkTienda, 
            'created_at'        => now(), 
            'updated_at'        => now()
        ]);

        if ($idCategoriaFinal) {
            DB::table('categoria_producto')->insert([
                'producto_id'  => $idProductoFinal, 
                'categoria_id' => $idCategoriaFinal, 
                'created_at'   => now(), 
                'updated_at'   => now()
            ]);
        }
    }

    // Guardamos en tu mapa de IDs original
    $mapaIdsProductos[$index] = $idProductoFinal;

    // 4. LÓGICA DE LOTES (Copiada exactamente de tu otra función)
    $idLoteGenerado = null;
    $fechaFilaRaw = is_array($arrayFechaVencimiento[$index] ?? '') ? head($arrayFechaVencimiento[$index]) : ($arrayFechaVencimiento[$index] ?? '');
    
    if ($fechaFilaRaw != 'N/A' && !empty($fechaFilaRaw)) {
        $fechaFormateada = date('Ymd', strtotime(str_replace('/', '-', $fechaFilaRaw)));
        $numeroLote = 'L-' . $fechaFormateada . '.' . $compra->id . '.' . $posicion;

        $idLoteGenerado = DB::table('lotesalarma')->insertGetId([
            'producto_id'       => $idProductoFinal,
            'numero_lote'       => $numeroLote,
            'cantidad'          => $cantFila, 
            'fecha_vencimiento' => date('Y-m-d', strtotime(str_replace('/', '-', $fechaFilaRaw))),
            'fkTienda'          => $fkTienda,
            'compra_id'         => $compra->id,
            'notificado'        => 0,
            'created_at'        => now(),
            'updated_at'        => now()
        ]);

        $lotesPorActualizar[] = [
            'id'       => $idLoteGenerado,
            'cantidad' => $cantFila
        ];
    }

    // 5. ATTACH PIVOTE (Dispara tus triggers contables de la compra)
    $compra->productos()->attach([
        $idProductoFinal => [
            'cantidad'      => $cantFila,
            'precio_compra' => $precFila,
            'precio_venta'  => $precioVentaFila,
            'impuesto'      => $impuestoFila,
            'fkTienda'      => $fkTienda,
            'fkLote'        => $idLoteGenerado,
            'Naturaleza'    => 'D',
            'Estado'        => 'I'
        ]
    ]);

    // 6. RESOLVER CENTRO LOGÍSTICO Y ALMACÉN
    $centro = Centro::join('tienda', 'centro.id', '=', 'tienda.fkCentro')
        ->where('tienda.idTienda', session('user_fkTienda'))
        ->select('centro.*', 'tienda.nombre as nombre_tienda')
        ->first();

    // 7. REGISTRO EN KARDEX (Usando tu Eloquent MovimientoMateriales)
    MovimientoMateriales::create([
        'fkTienda'              => $fkTienda,
        'fkMateriales'          => $idProductoFinal,
        'fkLotes'               => $idLoteGenerado,
        'clase_movimiento'      => '641',
        'tipo_movimiento'       => 'COMPRA',
        'cantidad'              => $cantFila,
        'documento_material'    => 'COM-' . $compra->numero_comprobante,
        'referencia'            => "Comp ID: ||{$compra->id}||",
        'fecha_contabilizacion' => now(),
        'centro'                => session('centro') ?? ($centro->codigo ?? 'N/A'),
        'almacen'               => session('centro') ?? ($centro->codigo ?? 'N/A'),
        'origen_uso'            => 'compra_nacional',
        'unidad_medida_base'    => 'PZA',
        'posicion_documento'    => $posicion
    ]);

    $posicion++; 
}
              // =========================================================================
        // PARTE 3: CABECERA DE LA COMPRA Y FOLIO CONTABLE AUTOMÁTICO
        // =========================================================================
        // Buscar el comprobante activo por ClaveVista 'DC', si no existe, toma el primero que encuentre
        $comprobante = DB::table('comprobantes')
            ->where('estado', 1)
            ->where('ClaveVista', 'DC')
            ->first() ?? DB::table('comprobantes')->where('estado', 1)->first();

        if (!$comprobante) {
            throw new \Exception("Error Crítico: No se encontró ningún comprobante parametrizado o activo en el sistema.");
        }

        $comprobante_id = $comprobante->id;
        $tipofolio = 'C'; 
        $numero_comprobante = $request->numero_comprobante;

        if ($numero_comprobante == 0 || empty($numero_comprobante)) {
            $ultimoNumero = DB::table('compras')->where('fkTienda', $fkTienda)->lockForUpdate()->count('numero_comprobante');
            $correlativo = $ultimoNumero ? $ultimoNumero + 1 : 1;
            $numero_comprobante = $correlativo . $tipofolio . $comprobante->ClaveVista . $comprobante->id;
        }

        // INTEGRACIÓN DE FÓRMULA 1: Calcular IVA global de la cabecera usando tu motor matemático
        $formulaCabecera = $comprobante->formula ?? null;
        $impuestotal = $this->evaluarFormula($formulaCabecera, $subtotalAcumuladoGlobal);

        $compraId = DB::table('compras')->insertGetId([
            'fecha_hora'         => $request->get('fecha_hora', now()),
            'impuesto'           => $impuestotal,
            'numero_comprobante' => $numero_comprobante,
            'total'              => $subtotalAcumuladoGlobal + $impuestotal,
            'estado'             => 2, 
            'fkUserCreate'       => $idUsuario,
            'fkUserCC'           => $idUsuario,
            'comprobante_id'     => $comprobante_id,
            'proveedore_id'      => $proveedor_id,
            'fkTienda'           => $fkTienda,
            'ClaveVista'         => $comprobante->ClaveVista,
            'created_at'         => now(),
            'updated_at'         => now()
        ]);

        $folioId = DB::table('folio')->insertGetId([
            'descripcion'          => 'Compra masiva n.' . $compraId . ' mediante comprobante ' . ($comprobante->defauldoc ?? 'Asiento de Compra'),
            'cabecera'             => 'Asiento de Compra Masiva Automatizado (DC)',
            'EstatusContable'      => 'C',
            'TipoFolio'            => $tipofolio,
            'FechaContabilizacion' => now(),
            'fkUsuario'            => $idUsuario,
            'fkComprobante'        => $comprobante_id,
            'idOrigen'             => $compraId,
            'TipoMovimiento'       => 'C',
            'fkTienda'             => $fkTienda,
            'created_at'           => now(),
            'updated_at'           => now()
        ]);

        // INTEGRACIÓN DE FÓRMULA 2: Procesar los movimientos contables del detalle del comprobante
        $detallesComprobante = DB::table('detalle_comprobantes')->where('fkComprobante', $comprobante_id)->get();
        foreach ($detallesComprobante as $reglaContable) {
            
            // Evaluar la fórmula contable específica de la cuenta o recurrir a su valor mínimo de respaldo
            $montoContableFinal = $this->evaluarFormula($reglaContable->formula, $subtotalAcumuladoGlobal);
            if ($montoContableFinal <= 0) {
                $montoContableFinal = floatval($reglaContable->valorminimo ?? 0);
            }

            DB::table('detallefolio')->insert([
                'Monto'             => $montoContableFinal,
                'Naturaleza'        => $reglaContable->Naturaleza, 
                'fkCuenetaContable' => $reglaContable->fkCuentaContable,
                'fkUsuario'         => $idUsuario,
                'fkTienda'          => $fkTienda,
                'fkFolio'           => $folioId,
                'created_at'        => now(),
                'updated_at'        => now()
            ]);
        }
             // =========================================================================
        // PARTE 4: CONSOLIDACIÓN DE INVENTARIO, LOTES Y CONFIRMACIÓN FINAL
        // =========================================================================
        $productosConsolidados = [];

        foreach ($mapaIdsProductos as $index => $idRealProducto) {
            $cantidadItems = isset($arrayCantidad[$index]) ? intval($arrayCantidad[$index]) : 0;
            if ($idRealProducto <= 0 || $cantidadItems <= 0) continue;

            $fechaVenc = !empty($arrayFechaVencimiento[$index]) ? $arrayFechaVencimiento[$index] : 'N/A';
            $pCompra   = number_format(floatval($arrayPrecioCompra[$index] ?? 0), 4, '.', ''); 
            $pVenta    = number_format(floatval($arrayPrecioVenta[$index] ?? 0), 4, '.', '');

            // Solución Estricta: Se concatena el $index para evitar que filas del Excel se solapen erróneamente en el array
            $key = $idRealProducto . '_row' . $index . '_' . $fechaVenc . '_' . $pCompra . '_' . $pVenta;

            if (!isset($productosConsolidados[$key])) {
                $productosConsolidados[$key] = [
                    'id'            => $idRealProducto, 
                    'cantidad'      => 0, 
                    'precio_compra' => floatval($pCompra),
                    'precio_venta'  => floatval($pVenta), 
                    'impuesto'      => 0, 
                    'fecha'         => $fechaVenc, 
                    'perecedero'    => $arrayPerecedero[$index] ?? 0
                ];
            }
            $productosConsolidados[$key]['cantidad'] += $cantidadItems;
            $productosConsolidados[$key]['impuesto'] += floatval($arraysubiva[$index] ?? 0);
        }

        $posicionLote = 1;
        foreach ($productosConsolidados as $item) {
            $idLoteGenerado = null;

            if (($item['perecedero'] == 1 || $item['fecha'] != 'N/A') && !empty($item['fecha'])) {
                $fechaLoteFormateada = date('Y-m-d', strtotime(str_replace('/', '-', $item['fecha'])));
                $prefijoFecha = date('Ymd', strtotime($fechaLoteFormateada));
                
                $idLoteGenerado = DB::table('lotesalarma')->insertGetId([
                    'compra_id'         => $compraId,
                    'fkTienda'          => $fkTienda,
                    'producto_id'       => $item['id'],
                    'numero_lote'       => 'L-' . $prefijoFecha . '.' . $compraId . '.' . $posicionLote,
                    'cantidad'          => $item['cantidad'],
                    'fecha_vencimiento' => $fechaLoteFormateada,
                    'notificado'        => 0,
                    'created_at'        => now(),
                    'updated_at'        => now()
                ]);
                $posicionLote++;
            }

            DB::table('compra_producto')->insert([
                'compra_id'     => $compraId,
                'producto_id'   => $item['id'],
                'cantidad'      => $item['cantidad'],
                'precio_compra' => $item['precio_compra'],
                'precio_venta'  => $item['precio_venta'],
                'fkTienda'      => $fkTienda,
                'fkLote'        => $idLoteGenerado,
                'Naturaleza'    => 'D',
                'Estado'        => 'I',
                'impuesto'      => $item['impuesto'],
                'created_at'    => now(),
                'updated_at'    => now()
            ]);

            DB::table('productos')->where('id', $item['id'])->increment('stock', $item['cantidad'], [
                'precio_base' => $item['precio_compra'],
                'updated_at'  => now()
            ]);
        }

        DB::commit();
        return response()->json(['status' => 'success', 'message' => 'Lote completo de productos procesado e impuestos calculados con éxito.'], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['status' => 'error', 'message' => 'Fallo en inserción masiva: ' . $e->getMessage()], 500);
    }
}

public function obtenerCodigoUnicoAjax()
{
    $existe = true;
    $codigoUnico = '';
    $intentos = 0; // Candado de seguridad para evitar congelar el servidor

    while ($existe && $intentos < 100) {
        $intentos++;

        // 1. Tomamos los segundos del servidor (10 dígitos exactos en la época actual)
        $segundos = (string)time(); 
        
        // 2. Prefijo '20' (2 dígitos) + Segundos (10 dígitos) = 12 dígitos matemáticos exactos
        $base = "20" . $segundos;
        
        // Si por alguna razón la cadena no mide 12, la rellenamos con ceros a la derecha
        $base = str_pad($base, 12, "0", STR_PAD_RIGHT);

        // 3. Calcular el dígito verificador oficial EAN-13
        $suma = 0;
        for ($i = 0; $i < 12; $i++) {
            $numero = (int)$base[$i];
            // Posiciones impares se multiplican por 1, posiciones pares (índices 1, 3, 5...) por 3
            $suma += ($i % 2 === 0) ? $numero : $numero * 3;
        }
        $digitoVerificador = (10 - ($suma % 10)) % 10;
        
        // 4. Código final estructurado de 13 dígitos
        $codigoUnico = $base . $digitoVerificador;

        // 5. Validamos contra tu tabla real de productos
        // REVISTA ESTO: Cambia 'Producto' por tu Modelo y 'codigo_barras' por tu columna real de la BD
        $existe = Producto::where('codigo', $codigoUnico)->exists();
    }


    return response()->json(['codigo' => $codigoUnico]);
}


// =========================================================================
// SECCIÓN 1: CONFIGURACIÓN DE CABECERAS HTTP Y COLUMNAS EXACTAS DEL EXCEL
// =========================================================================
public function descargarFormatoCargaMasiva()
{
    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=Formato_Masivo_Compras.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    // Sincronización exacta con las columnas visibles de tu imagen de Excel
    $columnas = [
        'CODIGO_PRO',     // Código de barra o colocar "SG" para generación automática EAN-13
        'NOMBRE_PR',      // Nombre o descripción del producto (Varchar 80)
        'CATEGORIA_',     // Nombre en texto de la Categoría (Ej: Confites, Malvavisco)
        'PRESENTACI',     // Nombre en texto de la Presentación (Ej: Paleta c/exh, Display)
        'MARCA_ID',       // Nombre en texto de la Marca (Ej: Lollipop, Mash, varios)
        'ES_PERECED',     // 1 = Sí es perecedero (lleva vencimiento), 0 = No es perecedero
        'CANTIDAD',       // Cantidad de unidades físicas que ingresan a stock
        'PRECIO_CON',     // Precio de Costo / Compra unitario decimal (Ej: 35 o 38.12)
        'PRECIO_VEN',     // Precio de Venta sugerido al público general (Ej: 50)
        'SUBTOTAL_I',     // Monto acumulado de la línea o impuesto (Ej: 1750 o 500)
        'FECHA_VEN',      // Fecha de vencimiento formato DD/MM/YYYY (Ej: 31/12/2026)
        'PROVEEDOR_NIT',      // NIT o documento del Proveedor (Ej: 123456789)
        'PROVEEDOR_NOMBRE'      // Razón social o Nombre del Proveedor (Ej: Sophy Candy)
    ];

// =========================================================================
// SECCIÓN 2: TRANSMISIÓN EN FLUJO CONSTANTE Y FILAS GUÍA BASADAS EN TU IMAGEN
// =========================================================================
    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        
        // Forzar marcador BOM UTF-8 para que Excel reconozca tildes y caracteres en español
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); 
        fputcsv($file, $columnas);

        // Ejemplo 1: Producto perecedero con código automático "SG", marcas y categorías en texto plano
        fputcsv($file, [
            'SG',               // CODIGO_PRO
            'Lollipop frut',    // NOMBRE_PR
            'Confites',         // CATEGORIA_
            'Paleta c/exh',     // PRESENTACI
            'Lollipop',         // MARCA_ID (Texto plano según tu imagen)
            1,                  // ES_PERECED
            5,                  // CANTIDAD
            35.00,              // PRECIO_CON (Costo)
            50.00,              // PRECIO_VEN (Venta)
            1750.00,            // SUBTOTAL_I
            '31/12/2026',       // FECHA_VEN (Formato DD/MM/YYYY de tu imagen)
            '123456789',        // PROVEEDOR (NIT)
            'Sophy Candy'       // PROVEEDOR_ (Nombre)
        ]);

        // Ejemplo 2: Producto de otra categoría (Malvavisco) sin código de barras de fábrica
        fputcsv($file, [
            'SG', 
            'Stich Mash 1', 
            'Malvavisco', 
            'Sobre 2 Unid', 
            'Mash', 
            1, 
            5, 
            21.00, 
            25.00, 
            525.00, 
            '05/01/2027', 
            '123456789', 
            'Sophy Candy'
        ]);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    public function show(Compra $compra)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $lotecopra = Lotesalarma::where('compra_id',$compra->id)->get();

        return view('compra.show',compact('compra','lotecopra'));
    }

    public function cargamasiva()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }
        return view('compra.cargamasiva');
    }

      public function descargarPlantilla()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        return response()->download(public_path('plantillas/plantilla_productos.xlsx'));
    }

    public function storeMasivo(Request $request)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $request->validate([
            'zipfile' => 'required|mimes:zip'
        ]);

        $fkTienda = session('user_fkTienda');

        // Subir archivo temporal
        $zipPath = $request->file('zipfile')->store('temp');

        $zip = new ZipArchive;
        $zipRealPath = storage_path('app/' . $zipPath);

        if ($zip->open($zipRealPath) === TRUE) {

            // EXTRAER ZIP
            $extractPath = storage_path('app/temp_upload/');
            $zip->extractTo($extractPath);
            $zip->close();

        } else {
            return back()->with('error', 'No se pudo abrir ZIP');
        }

        // VALIDAR ARCHIVOS NECESARIOS
        if (!file_exists($extractPath . 'productos.xlsx')) {
            return back()->with('error', 'El ZIP no contiene productos.xlsx');
        }

        if (!is_dir($extractPath . 'imagenes')) {
            return back()->with('error', 'El ZIP no contiene la carpeta /imagenes');
        }

        // LEER EXCEL
        $rows = Excel::toArray([], $extractPath . 'productos.xlsx')[0];

        // IGNORAR CABECERA
        unset($rows[0]);

        foreach ($rows as $row) {

            if (!isset($row[0]) || empty($row[0])) continue;

            $codigo      = trim($row[0]);
            $nombreProd  = trim($row[1]);
            $categoria   = trim($row[2]);
            $marca       = trim($row[3]);
            $present     = trim($row[4]);
            $lote        = trim($row[5]);
            $proveedor   = trim($row[6]);
            $pcompra     = floatval($row[7]);
            $pventa      = floatval($row[8]);
            $piva      = floatval($row[9]);
            $stock       = intval($row[10]);
            $imagen      = trim($row[11]);
            $compNombre  = trim($row[12]);
            $cuentaNom   = trim($row[13]);
            $detalleNom  = trim($row[14]);

            // ================= CREAR SI NO EXISTE ====================
            $cat = Categoria::firstOrCreate(['nombre' => $categoria], ['fkTienda' => $fkTienda]);
            $mar = Marca::firstOrCreate(['nombre' => $marca], ['fkTienda' => $fkTienda]);
            $pre = presentacione::firstOrCreate(['nombre' => $present], ['fkTienda' => $fkTienda]);
            $lot = Lotesalarma::firstOrCreate(['codigo' => $lote], ['fkTienda' => $fkTienda]);
            $prov = Proveedore::firstOrCreate(['nombre' => $proveedor], ['fkTienda' => $fkTienda]);

            // ================= EXISTENTES ============================
            $comprobante = Comprobante::where('nombre', $compNombre)->first();
            $cuenta = CuentaContable::where('nombre', $cuentaNom)->first();
            $detalle = DetalleComprobante::where('nombre', $detalleNom)->first();

            if (!$comprobante || !$cuenta || !$detalle) {
                return back()->with('error', "Error: No existe el comprobante/cuenta/detalle para {$nombreProd}");
            }

            // ================= PRODUCTO ============================
            $producto = Producto::firstOrCreate(
                ['codigo' => $codigo],
                [
                    'nombre' => $nombreProd,
                    'fkCategoria' => $cat->id,
                    'fkMarca' => $mar->id,
                    'fkPresentacion' => $pre->id,
                    'fkLote' => $lot->id,
                    'fkTienda' => $fkTienda,
                ]
            );

            // ================= GUARDAR IMAGEN ============================
            $sourceImage = $extractPath . "imagenes/{$imagen}";

            if (file_exists($sourceImage)) {
                $newPath = "productos/" . $imagen;
                Storage::disk('public')->put($newPath, file_get_contents($sourceImage));
                $producto->img_path = $newPath;
                $producto->save();
            }

            // ================= COMPRA ============================
            $compra = Compra::create([
                'proveedor_id' => $prov->id,
                'comprobante_id' => $comprobante->id,
                'numero_comprobante' => "AUTO-" . rand(100000, 999999),
                'fecha' => now(),
                'total' => ($pcompra * $stock) + ($piva * $stock),
                'impuesto' => $piva * $stock,
                'estado' => '2',
                'fkUserCC' => auth()->id(),
                'fkUserCreate' => auth()->id(),
                'fkTienda' => $fkTienda
            ]);

                        //Llenar tabla compra_producto

            //2.Realizar el llenado
            $siseArray = count($compra->id);
            $cont = 0;
            while($cont < $siseArray){
                $compra->productos()->attach([
                    $compra->id[$cont] => [
                        'cantidad' => $stock,
                        'precio_compra' => $pcompra,
                        'precio_venta' => $pventa,
                        'impuesto'=>$piva,
                        'fkTienda'=>$fkTienda,
                        'Naturaleza'=>'D',
                        'Estado'=>'I'
                    ]
                ]);
            }

            // ================= FOLIO ============================
            $folio = Folio::create([
                'fkUsuario' => auth()->id(),
                'cabecera' => "Compra carga masiva",
                'descripcion' => "Compra del producto {$nombreProd}, código {$codigo}, cantidad {$stock}, precio compra Q. {$pcompra}, precio venta Q. {$pventa}.",
                'EstatusContable' => 'C',
                'TipoFolio' => 'A',
                'FechaContabilizacion' => now(),
                'fkComprobante' => $comprobante->id,
                'idOrigen' => $compra->id,
                'TipoMovimiento' => 'C',
                'created_at' => now(),
                'updated_at' => now(),
                'fkTienda' => $fkTienda
            ]);

            DetalleFolio::create([
                'fkFolio' => $folio->id,
                'fkCuentaContable' => $cuenta->id,
                'Naturaleza' => 'D',
                'Monto' => $pcompra * $stock,
                'fkTienda' => $fkTienda,
                'fkUsuario' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return back()->with('success', 'Carga masiva completada correctamente.');
    }



    public function generarRecibo($arqueocaja)
{
                    if(!Auth::check()){
            return redirect()->route('login');
        }

    $fkTienda = session('user_fkTienda');
    $Tienda = Tienda::where('idTienda', $fkTienda)->first();
$arqueocaja = (int) $arqueocaja;

$plantilla = DB::table('compras as c')
    ->join('comprobantes as cm', 'c.comprobante_id', '=', 'cm.id')
    ->join('plantillahtml as ph', 'ph.id', '=', 'cm.fkPlantillaHtml')
    ->join('documentdesigns as dd', 'dd.id', '=', 'ph.fkDesignDocument')
    ->join('compra_producto as cp', 'cp.compra_id', '=', 'c.id')
    ->where('c.id', $arqueocaja)
    ->where('c.fkTienda', $fkTienda)
    ->select(
        'c.id as idcompra',
        'cp.id as idproducto',
        'cp.cantidad',
        'cp.precio_compra',
        'cp.precio_venta',
        'cp.producto_id',
        'dd.alto_pt',
        'dd.ancho_pt',
        'dd.orientacion_vertical',
        'cm.fkPlantillaHtml as idPlantilla',
        'ph.fkDesignDocument as fkDesignDocument',
        'ph.cabecera',
        'ph.detalle',
        'ph.pie',
        'ph.consulta',
        'c.fkTienda'
    )
    ->orderBy('cp.id', 'DESC')
    ->get();





    $cabecera = $plantilla->first()->cabecera;
    $detalle = $plantilla->first()->detalle;
    $pie = $plantilla->first()->pie;
    $consulta = $plantilla->first()->consulta;

    $tokens = ['idventa' => $plantilla->first()->idcompra, 'idtienda' => $fkTienda];
    $numFilas = $plantilla->count();

    // Si height_mm o width_mm es null, dar valor por defecto
    $altura = ($plantilla->first()->alto_pt ?? 205) + ($numFilas * 15);
    $ancho = $plantilla->first()->ancho_pt ?? 226.77;
    $orientacion = $plantilla->first()->orientation ?? 'portrait';

    $cons = $this->procesarConsulta($consulta, $tokens);
    $tokenss = $this->ejecutarconsulta($cons);

    $htmlFinal = $this->procesarPlantilla($cabecera, $detalle, $pie, $tokenss['columnas'], $tokenss['filas']);

    $pdf = Pdf::loadHTML($htmlFinal)->setPaper([0, 0, $ancho, $altura], $orientacion);

    // Crear carpeta si no existe
    $rutaCarpeta = storage_path('app/public/recibos');
    if (!file_exists($rutaCarpeta)) {
        mkdir($rutaCarpeta, 0777, true);
    }

    // Guardar PDF
    $rutaArchivo = $rutaCarpeta.'/recibocompra_'.$arqueocaja.'.pdf';
    $pdf->save($rutaArchivo);

    // Finalmente, abrir en el navegador
    return response()->file($rutaArchivo);
}

function procesarConsulta($consulta, $tokens)
{
    $consultaprocesada = $consulta;

    foreach ($tokens as $token => $valor) {
        $pattern = '/@{{\s*' . preg_quote($token, '/') . '\s*}}/';
        $consultaprocesada = preg_replace($pattern, $valor, $consultaprocesada);
    }

    return $consultaprocesada;
}
function procesarPlantilla($cab, $htmlDetalle, $pi, $variablesGlobales, $detalle)
{
    foreach ($variablesGlobales as $token => $valor) {

        $pattern = '/\{\{\s*' . preg_quote($valor, '/') . '\s*\}\}/';

        if($valor=="logo"){
            $compressed = $detalle[0][$valor];
            $compressed = trim($compressed);
            $compressed = str_replace(['"', "'"], '', $compressed);
            $compressed = preg_replace('/\s+/', '', $compressed);

       //     dd($compressed);
            $cab = preg_replace($pattern, $compressed, $cab);
        }
        else{
        $cab = preg_replace($pattern, $detalle[0][$valor], $cab);
        }
        $pi = preg_replace($pattern, $detalle[0][$valor], $pi);
    }
   $htmlFilas = "";

foreach ($detalle as $fila) {
    $row = $htmlDetalle;

    foreach ($variablesGlobales as $key => $value) {

        // Coincidir SOLO tokens como {{idventa}} (sin $)
        $pattern = '/{{\s*' . preg_quote($value, '/') . '\s*}}/';

        if (isset($fila[$value])) {
            $row = preg_replace($pattern, $fila[$value], $row);
        }
    }

    $htmlFilas .= $row;
}

    $htmlFinal = $cab . $htmlFilas . $pi;

    return $htmlFinal;
}

public function ejecutarconsulta($consulta)
    {
   $filas = DB::select($consulta);

    if (count($filas) == 0) {
        return [
            "columnas" => [],
            "filas" => []
        ];
    }

    // convierte los objetos stdClass en arrays
    $filasArray = array_map(function ($row) {
        return (array) $row;
    }, $filas);

    // columnas = keys del primer registro
    $columnas = array_keys($filasArray[0]);

    return [
        "columnas" => $columnas,
        "filas"    => $filasArray
    ];
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    public function comprasReporte(Request $request)
{
                    if(!Auth::check()){
            return redirect()->route('login');
        }

    $fkTienda = session('user_fkTienda');

    // Filtro de productos (array)
    $productosSeleccionados = (array) $request->input('producto', []);

    // Lista de productos para el select
$productos = Producto::select('id', 'nombre')
    ->where('fkTienda', $fkTienda)
    ->whereIn('estado', [1, 2, 3])
    ->get();


    // Consulta base agrupada por fecha
    $query = Compra::select(
            DB::raw("DATE_FORMAT(fecha_hora, '%d/%m/%Y') as fecha"),
            DB::raw("SUM(total) as total")
        )
        ->where('fkTienda', $fkTienda)
        ->groupBy(DB::raw("DATE_FORMAT(fecha_hora, '%d/%m/%Y')"))
        ->orderBy(DB::raw("DATE(fecha_hora)"));

    // Filtro fecha inicio
    if ($request->inicio) {
        $query->whereDate('fecha_hora', '>=', $request->inicio);
    }

    // Filtro fecha fin
    if ($request->fin) {
        $query->whereDate('fecha_hora', '<=', $request->fin);
    }

    // Filtro por producto
    if (!empty($productosSeleccionados) && !in_array(0, $productosSeleccionados)) {
        $query->whereHas('productos', function ($q) use ($productosSeleccionados) {
            $q->whereIn('producto_id', $productosSeleccionados);
        });
    }

    // Obtener ventas
    $ventas = $query->get();

    // Preparar datos para la gráfica
    $labels = $ventas->pluck('fecha');
    $values = $ventas->pluck('total');

    // Total general
    $totalgeneral = $values->sum();

    $scatterValues = collect($values)->map(function($v, $i) {
    return ['x' => $i + 1, 'y' => $v];
    });

    $bubbleData = collect($values)->map(function($v, $i) {
    return [
        'x' => $i + 1,    // posición X (puede ser índice o fecha)
        'y' => $v,        // monto
        'r' => 5 + ($v/100) // tamaño de la burbuja proporcional al valor
    ];
});


    return view('dashboard.reportecompra', compact('bubbleData','scatterValues','labels', 'values', 'productos', 'totalgeneral'));
}



    public function Lista()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        // Aquí ejecutamos la consulta
        $clientes = Compra::join('personas as p', 'clientes.persona_id', '=', 'p.id')
                           ->select('clientes.id', 'p.razon_social as nombre')
                           ->get();

        // Verifica si obtenemos resultados
        return response()->json($clientes);
    }
    public function update(Request $request, string $id)
    {
        //
    }


    public function mostrarDetalles($idComprobante)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        // Consultar los detalles del comprobante junto con las cuentas contables
        $detalles = DB::table('detalle_comprobantes as dc')
            ->join('cuentas_contables as cc', 'dc.fkCuentaContable', '=', 'cc.id')
            ->join('comprobantes as c','c.id','=','dc.fkComprobante')
            ->where('dc.fkComprobante', $idComprobante)
            ->select(
                'cc.nombre as cuenta_contable_nombre',
                'dc.Naturaleza',
                'dc.valorminimo',
                'dc.formula',
                'c.formula as formuladoc',
                'cc.id'
            )
            ->get();

        // Devolver los detalles como respuesta JSON
        return response()->json([
            'detalles' => $detalles
        ]);
    }

public function mostrarDetallesCompraScanner($SKU)
{
    if (!Auth::check()) {
        return response()->json(['error' => 'No autenticado'], 401);
    }

    $fkTienda = session('user_fkTienda');

    // Usamos DB::table para evitar errores de PDO manual
    $producto = DB::table('productos')
        ->select(
            'nombre AS producto_nombre',
            'codigo AS producto_codigo',
            'stock AS existencia',
            'descripcion',
            'img_path AS imagen_producto',
            'id AS producto_id'
        )
        ->where('codigo', '=', $SKU)
        ->where('fkTienda', '=', $fkTienda)
        ->where('estado', '=', 1)
        ->get(); // Retorna una colección

    return response()->json($producto);
}


            public function mostrarDetallesScanner($SKU)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
                $pdo = DB::getPdo();
        $stmt = $pdo->prepare("
    SELECT
        p.nombre AS producto_nombre,
        cp.cantidad,
        cp.precio_venta,
        p.codigo AS producto_codigo,
        p.stock as existencia,
        p.descripcion,
        p.fecha_vencimiento,
        p.img_path as imagen_producto,
        p.id as producto_id
    FROM
        compras as c
    INNER JOIN
        compra_producto as cp ON c.id = cp.compra_id
    INNER JOIN
        productos as p ON cp.producto_id = p.id
    WHERE
        p.codigo = :id and c.fkTienda = $fkTienda AND c.estado = 2
");

$stmt->execute(['id' => $SKU]);


        $producto = $stmt->fetchAll(\PDO::FETCH_ASSOC);


return response()->json($producto);
    }



    public function destroy(string $id)
    {
        Compra::where('id',$id)
        ->update([
            'estado' => 0
        ]);

        return redirect()->route('compras.index')->with('success','Compra eliminada');
    }
}
