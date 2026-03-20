<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVentaRequest;
use App\Http\Requests\UpdateVentaRequest;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\CuentaContable;
use App\Models\DetalleFolio;
use App\Models\Folio;
use App\Models\Producto;
use App\Models\Tienda;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Exports\UniversalExport;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use MathParser\StdMathParser;
use MathParser\Interpreting\Evaluator;
use Illuminate\Support\Facades\Log;

class ventaController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:reporte-venta|cobrar-ventadirecta|ver-venta|crear-venta|mostrar-venta|eliminar-venta', ['only' => ['index']]);
        $this->middleware('permission:crear-venta', ['only' => ['create', 'store']]);
        $this->middleware('permission:mostrar-venta', ['only' => ['show']]);
        $this->middleware('permission:eliminar-venta', ['only' => ['destroy']]);
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

        $ventas = [];
        $productos = [];

                // Si el estatus es 'ER', cargar todas las compras
                if ($Estatus == 'ER') {

                    $ventas = Venta::with(['comprobante','cliente.persona','user', 'tienda'])
                    ->whereIn('estado',[1,2,3])
                    ->latest()
                    ->get();

                    // Filtrar los productos solo por la tienda del usuario
                    $productos = Producto::with('comprobante','tienda')
                    ->whereIn('estado', [1,2,3])
                    ->latest()
                    ->get();

                } else {
                    $ventas = Venta::with(['comprobante','cliente.persona','user', 'tienda'])
                    ->where('fkTienda', $fkTienda)
                    ->where('estado',1)
                    ->latest()
                    ->get();

                                // Filtrar los productos solo por la tienda del usuario
                $productos = Producto::with('comprobante','tienda')
                ->where('fkTienda', $fkTienda)
                ->whereIn('estado', [1,2,3])
                ->latest()
                ->get();
                }

        return view('venta.index',compact('ventas'));
    }

    /**
     * Show the form for creating a new resource.
     */
public function ventasReporte(Request $request)
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
    $query = Venta::select(
            DB::raw("DATE_FORMAT(fecha_hora, '%d/%m/%Y') as fecha"),
            DB::raw("SUM(total) as total")
        )
        ->where('fkTienda', $fkTienda)
        ->whereIn('estado', [2])
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

    return view('dashboard.index', compact('scatterValues','bubbleData','labels', 'values', 'productos', 'totalgeneral'));
}

public function devolucionventasReporte(Request $request)
{
                    if(!Auth::check()){
            return redirect()->route('login');
        }

    $fkTienda = session('user_fkTienda');

    // Filtro de productos (array)
    $productosSeleccionados = (array) $request->input('producto', []);



$productos = User::select('users.id', 'users.name')
    ->join('usuario_tienda', 'usuario_tienda.fkUsuario', '=', 'users.id')
    ->where('usuario_tienda.fkTienda', $fkTienda)
    ->get();


    // Consulta base agrupada por fecha
$query = Venta::select(
        DB::raw("DATE_FORMAT(ventas.created_at, '%d/%m/%Y') as fecha"),
        DB::raw("SUM(ventas.total) as total")
    )
    // Hacemos el join con devoluciones_venta
    ->join('devoluciones_venta', 'ventas.id', '=', 'devoluciones_venta.venta_id')
    ->where('ventas.fkTienda', $fkTienda)
    ->groupBy(DB::raw("DATE_FORMAT(ventas.created_at, '%d/%m/%Y')"))
    ->orderBy(DB::raw("DATE(ventas.created_at)"));

// Filtro fecha inicio (especificando tabla ventas)
if ($request->inicio) {
    $query->whereDate('ventas.created_at', '>=', $request->inicio);
}

// Filtro fecha fin (especificando tabla ventas)
if ($request->fin) {
    $query->whereDate('ventas.created_at', '<=', $request->fin);
}


if (!empty($productosSeleccionados) && !in_array(0, $productosSeleccionados)) {
    $query->whereIn('user_id', $productosSeleccionados);
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

    return view('dashboard.reportedevolucionventa', compact('scatterValues','bubbleData','labels', 'values', 'productos', 'totalgeneral'));
}
public function exportVentas()
{
                    if(!Auth::check()){
            return redirect()->route('login');
        }
    $ventas = Venta::all();

    return Excel::download(new UniversalExport($ventas), 'ventas.xlsx');
}

    public function create()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }
        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        $subquery = DB::table('compra_producto')
            ->select('producto_id', DB::raw('MAX(created_at) as max_created_at'))
            ->where('fkTienda',$fkTienda)
            ->groupBy('producto_id');

        $productos = Producto::join('compra_producto as cpr', function ($join) use ($subquery) {
            $join->on('cpr.producto_id', '=', 'productos.id')
                ->whereIn('cpr.created_at', function ($query) use ($subquery) {
                    $query->select('max_created_at')
                        ->fromSub($subquery, 'subquery')
                        ->whereRaw('subquery.producto_id = cpr.producto_id');
                });
        })
            ->select('productos.nombre', 'productos.img_path', 'descripcion', 'productos.id', 'productos.stock', 'cpr.precio_venta')
            ->where('productos.fkTienda',$fkTienda)
            ->where('productos.estado', 1)
            ->where('productos.stock', '>', 0)
            ->get();

        $clientes = Cliente::whereHas('persona', function ($query) {
            $query->where('estado', 1);
        })->get();

        $comprobantes = Comprobante::with('tienda')
        ->where('fkTienda', $fkTienda)
        ->where('ClaveVista','DV')
        ->get();


        return view('venta.create', compact('productos', 'clientes', 'comprobantes'));
    }

        public function posmobile($cliente_id)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }
        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        $subquery = DB::table('compra_producto')
            ->select('producto_id', DB::raw('MAX(created_at) as max_created_at'))
            ->where('fkTienda',$fkTienda)
            ->groupBy('producto_id');

        $productos = Producto::join('compra_producto as cpr', function ($join) use ($subquery) {
            $join->on('cpr.producto_id', '=', 'productos.id')
                ->whereIn('cpr.created_at', function ($query) use ($subquery) {
                    $query->select('max_created_at')
                        ->fromSub($subquery, 'subquery')
                        ->whereRaw('subquery.producto_id = cpr.producto_id');
                });
        })
            ->select('productos.nombre', 'productos.img_path', 'descripcion', 'productos.id', 'productos.stock', 'cpr.precio_venta', 'productos.codigo')
            ->where('productos.fkTienda',$fkTienda)
            ->where('productos.estado', 1)
            ->where('productos.stock', '>', 0)
            ->get();

        //return view('venta.posmobil', compact('productos', 'clientes', 'comprobantes'));
        return view('venta.posmobil', compact('productos','cliente_id'));
    }

            public function posmobileCierre($cliente_id)
    {

  if (!Auth::check()) {
        return redirect()->route('login');
    }

    $fkTienda = session('user_fkTienda');
    $Estatus = session('user_estatus');



    // 🔥 SUBQUERY ÚLTIMA COMPRA POR PRODUCTO
    $subquery = DB::table('producto_venta')
        ->select('producto_id', DB::raw('MAX(created_at) as max_fecha'))
        ->where('fkTienda', $fkTienda)
        ->where('venta_id', $cliente_id) // 🔥 FILTRAR POR VENTA ACTUAL
        ->groupBy('producto_id')
        ->get();

    // 🔥 PRODUCTOS CON ÚLTIMO PRECIO
    $productos = Producto::join('compra_producto as cpr', function ($join) {
            $join->on('cpr.producto_id', '=', 'productos.id');
        })
        ->select(
            'productos.id',
            'productos.nombre',
            'productos.stock',
            'productos.codigo',
            'productos.descripcion',
            DB::raw('COALESCE(cpr.precio_venta) as precio_venta')
        )
        ->where('productos.fkTienda', $fkTienda)
        ->where('productos.estado', 1)
        ->where('productos.stock', '>', 0)
        ->whereNotIn('productos.id', $subquery->pluck('producto_id')) // Excluir productos ya en la venta
        ->get();

    // 🔥 CABECERA + DETALLE DE VENTA
    $ventacabecera = DB::table('ventas as v')
        ->join('producto_venta as pv', 'pv.venta_id', '=', 'v.id')
        ->join('clientes as cl', 'cl.id', '=', 'v.cliente_id')
        ->join('personas as pr', 'pr.id', '=', 'cl.persona_id')
        ->join('tienda as t', 't.idTienda', '=', 'v.fkTienda')
        ->join('users as u', 'u.id', '=', 'v.user_id')
        ->join('productos as pro', 'pro.id', '=', 'pv.producto_id')
        ->select(
            'v.id as idventa',
            'pro.nombre',
            'pro.descripcion',
            'pro.codigo',
            'pv.precio_venta',
            DB::raw('((pv.precio_venta * pv.cantidad) - pv.descuento) as subtotal'),
            'pro.stock',
            'pv.producto_id as id',
            'pv.cantidad',
            'pv.descuento',
            'v.cliente_id',
            'v.comprobante_id',
            't.idTienda',
            'v.fecha_hora',
            'v.numero_comprobante',
            'v.total',
            'pr.razon_social',
            'pr.numero_documento',
            't.Nombre',
            'u.name',
            'v.estado',
            'pr.tipo_persona'
        )
        ->where('v.fkTienda', $fkTienda)
        ->where('v.id', $cliente_id)
        ->where('v.estado', 1) // 🔥 SOLO SI NO ESTÁ FACTURADA
        ->get();

                $comprobantes = Comprobante::with('tienda')
        ->where('fkTienda', $fkTienda)
        ->where('ClaveVista','DV')
        ->get();

    $idventa = $cliente_id;

    $cliente_id=$ventacabecera->first()->cliente_id;

    return view('venta.posmobilCC', compact(
        'productos',
        'ventacabecera',
        'idventa',
        'cliente_id',
        'comprobantes'
    ));

        //return view('venta.posmobil', compact('productos', 'clientes', 'comprobantes'));
        return view('venta.posmobil', compact('productos','cliente_id'));
    }
            public function storemobile(Request $request)
    {
        try{
            DB::beginTransaction();

            $fkTienda = session('user_fkTienda');
            $Estatus = session('user_estatus');

            //Llenar mi tabla venta
            //$venta = Venta::create($request->validated());

            $cliente_id=$request->cliente_id;
            $user_id=$request->user_id;




            //Llenar mi tabla venta_producto
            //1. Recuperar los arrays
            $arrayProducto_id = $request->get('arrayidproducto');
            $arrayCantidad = $request->get('arraycantidad');
            $arrayPrecioVenta = $request->get('arrayPrecioVenta');
            $arrayDescuento = $request->get('arrayDescuento');
            $arraySubtotal = $request->get('Subtotal');
            $arrayTotalGeneral = $request->get('TotalGeneral');
            $total=0;
            $cont = 0;

            $siseArray = count($arrayProducto_id);

              $venta = Venta::create([
                'fecha_hora'=>now(),
                'total'=>floatval($arrayTotalGeneral),
                'estado'=>1,
                'cliente_id'=>$cliente_id,
                'numero_comprobante'=>'N/A',
                'impuesto'=>0,
                'create_at'=>now(),
                'update_at'=>now(),
                'user_id'=>$user_id,
                'fkTienda'=>$fkTienda,
                'TipoFolio'=>'A',
                'fkFolio' => 0,
                'fkUserCreate' => $user_id,
                'fkfactura' => '',
            ]);




            //2.Realizar el llenado
            $siseArray = count($arrayProducto_id);
            $cont = 0;

            while($cont < $siseArray){
                    if($arrayCantidad[$cont] > 0){


                $venta->productos()->attach([
                    $arrayProducto_id[$cont] => [
                        'cantidad' => $arrayCantidad[$cont],
                        'precio_venta' => $arrayPrecioVenta[$cont],
                        'fkTienda'=>$fkTienda,
                        'descuento' => $arrayDescuento[$cont],
                    ]
                ]);

/*
                $venta->productos()->syncWithoutDetaching([
                    $arrayProducto_id[$cont] => [
                        'cantidad' => $arrayCantidad[$cont],
                        'precio_venta' => $arrayPrecioVenta[$cont],
                        'descuento' => $arrayDescuento[$cont],
                        'fkTienda',$fkTienda
                    ]
                ]);
*/
                //Actualizar stock
                $producto = Producto::find($arrayProducto_id[$cont]);
                $stockActual = $producto->stock;
                $cantidad = intval($arrayCantidad[$cont]);

                DB::table('productos')
                ->where('id',$producto->id)
                ->update([
                    'stock' => $stockActual - $cantidad
                ]);
}
                $cont++;
            }

            DB::commit();
        }catch(Exception $e){
            DB::rollBack();
            return back()->withErrors($e->getMessage());
        }

        return redirect()->route('ventas.index')->with('success','Venta exitosa');
    }
public function CCstoremobile(Request $request)

{
    try {

        if (!Auth::check()) {
            return redirect()->route('login');
        }

            $request->validate([
        'comprobante_id' => 'required'
    ]);

        DB::beginTransaction();

        $fkTienda = session('user_fkTienda');
        $user_id = $request->user_id;

        $cliente_id = $request->cliente_id;
        $idventa = $request->idventa;
        $comprobante_id = $request->comprobante_id;

        $fecha_hora = now();
        $total = $request->TotalGeneral;
        $tipofolio = "A";

        // ARRAYS PRODUCTOS
        $arrayProducto_id = $request->get('arrayidproducto');
        $arrayCantidad = $request->get('arraycantidad');
        $arrayPrecioVenta = $request->get('arrayPrecioVenta');
        $arrayDescuento = $request->get('arrayDescuento');

        // 🔥 GENERAR NÚMERO DE COMPROBANTE
        $comprobantenomenclatura = Comprobante::findOrFail($comprobante_id);

        $ultimoNumero = Venta::where('fkTienda', $fkTienda)
        ->whereNot('numero_comprobante', 'N/A')
            ->lockForUpdate()
            ->count('numero_comprobante');



        $numero_comprobante = ($ultimoNumero ? $ultimoNumero + 1 : 1)
            . $tipofolio
            . $comprobantenomenclatura->ClaveVista
            . $comprobantenomenclatura->id;

                  $ultimoNumero = Venta::where('fkTienda', $fkTienda)
        ->where('numero_comprobante', $numero_comprobante)
            ->lockForUpdate()
            ->count('numero_comprobante');

            if($ultimoNumero!=0){
            $numero_comprobante = ($ultimoNumero ? $ultimoNumero + 1 : 1)
            . $tipofolio
            . $comprobantenomenclatura->ClaveVista
            . $comprobantenomenclatura->id;
            }
            $impuestotal = $this->evaluarFormula($comprobantenomenclatura->formula, $total);

        // 🔥 CREAR O ACTUALIZAR VENTA
        if ($idventa == null) {

            $venta = Venta::create([
                'fecha_hora' => now(),
                'impuesto' => $impuestotal,
                'numero_comprobante' => $numero_comprobante,
                'total' => $total,
                'estado' => 1,
                'comprobante_id' => $comprobante_id,
                'cliente_id' => $cliente_id,
                'fkUserCreate' => $user_id,
                'fkUserCC' => $user_id,
                'user_id' => $user_id,
                'fkTienda' => $fkTienda,
                'TipoFolio' => $tipofolio,
                'fkFolio' => 0,
                'fkfactura' => '',
                'created_at' => now(),
                'updated_at' => now()
            ]);

        } else {

            $venta = Venta::findOrFail($idventa);
        }

        // 🔥 CREAR FOLIO CONTABLE
        $folio = Folio::create([
            'descripcion' => 'Venta n.' . $venta->id . ', total Q. ' . $total,
            'cabecera' => 'Venta cerrada por caja',
            'EstatusContable' => 'C',
            'TipoFolio' => $tipofolio,
            'FechaContabilizacion' => now(),
            'fkComprobante' => $comprobante_id,
            'fkUsuario' => $user_id,
            'fkTienda' => $fkTienda,
            'idOrigen' => $venta->id,
            'TipoMovimiento' => 'V',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 🔥 GENERAR DETALLE CONTABLE AUTOMÁTICO
        $detallesComprobante = DB::table('detalle_comprobantes')
            ->where('fkComprobante', $comprobante_id)
            ->get();

        foreach ($detallesComprobante as $detalle) {

            $formula = $detalle->formula; // ej: A*0.12
            $cuenta_id = $detalle->fkCuentaContable;
            $tipo = $detalle->Naturaleza;

            $monto = $this->evaluarFormula($formula, $total);

            DetalleFolio::create([
                'Monto' => $monto,
                'Naturaleza' => $tipo,
                'fkCuenetaContable' => $cuenta_id,
                'fkUsuario' => $user_id,
                'fkTienda' => $fkTienda,
                'fkFolio' => $folio->idFolio,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // 🔥 ACTUALIZAR VENTA

                DB::table('ventas')->where('id', $venta->id)->update([
            'impuesto' => $impuestotal,
            'numero_comprobante' =>$numero_comprobante,
            'total' => $total,
            'estado' => 2,
            'comprobante_id' => $comprobante_id,
            'cliente_id' => $cliente_id,
            'fkUserEdit' => $user_id,
            'fkUserCC' => $user_id,

            'created_at' => now(),
            'updated_at' => now(),
            'fkTienda' => $fkTienda,
            'TipoFolio' => $tipofolio,
            'fkFolio' => $folio->idFolio
        ]);

        // 🔥 MANEJO DE PRODUCTOS Y STOCK
        $sizeArray = count($arrayProducto_id);

        $productosOriginales = DB::table('producto_venta')
            ->where('venta_id', $venta->id)
            ->get()
            ->keyBy('producto_id');

        $productosProcesados = [];

        for ($i = 0; $i < $sizeArray; $i++) {

        if($arrayCantidad[$i]>0){

            $productoId = $arrayProducto_id[$i];
            $cantidadNueva = intval($arrayCantidad[$i]);
            $precioVenta = $arrayPrecioVenta[$i];
            $descuento = $arrayDescuento[$i];

            $productosProcesados[] = $productoId;

            if (isset($productosOriginales[$productoId])) {

                $cantidadOriginal = intval($productosOriginales[$productoId]->cantidad);

                if ($cantidadNueva < $cantidadOriginal) {

                    $cantidadDevuelta = $cantidadOriginal - $cantidadNueva;

                    DB::table('devoluciones_venta')->insert([
                        'venta_id' => $venta->id,
                        'producto_id' => $productoId,
                        'cantidad_devuelta' => $cantidadDevuelta,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    DB::table('productos')
                        ->where('id', $productoId)
                        ->increment('stock', $cantidadDevuelta);
                }

                DB::table('producto_venta')
                    ->where('venta_id', $venta->id)
                    ->where('producto_id', $productoId)
                    ->update([
                        'cantidad' => $cantidadNueva,
                        'precio_venta' => $precioVenta,
                        'descuento' => $descuento,
                        'fkTienda' => $fkTienda
                    ]);

            } else {

                $venta->productos()->attach([
                    $productoId => [
                        'cantidad' => $cantidadNueva,
                        'precio_venta' => $precioVenta,
                        'fkTienda' => $fkTienda,
                        'descuento' => $descuento,
                    ]
                ]);

                DB::table('productos')
                    ->where('id', $productoId)
                    ->decrement('stock', $cantidadNueva);
            }
        }
        }

        // 🔥 PRODUCTOS ELIMINADOS
        foreach ($productosOriginales as $productoId => $productoOriginal) {

            if (!in_array($productoId, $productosProcesados)) {

                $cantidadDevuelta = $productoOriginal->cantidad;

                DB::table('devoluciones_venta')->insert([
                    'venta_id' => $venta->id,
                    'producto_id' => $productoId,
                    'cantidad_devuelta' => $cantidadDevuelta,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                DB::table('productos')
                    ->where('id', $productoId)
                    ->increment('stock', $cantidadDevuelta);

                DB::table('producto_venta')
                    ->where('venta_id', $venta->id)
                    ->where('producto_id', $productoId)
                    ->delete();
            }
        }

        DB::commit();

        // 🔥 GENERAR PDF
        $rutaPdfPublica = asset('storage/recibos/recibo_' . $venta->id . '.pdf');

        return redirect()->route('ventas.show', ['venta' => $venta])
            ->with('success', 'Venta exitosa')
            ->with('pdf', $rutaPdfPublica);

    } catch (\Exception $e) {

        DB::rollBack();

        Log::error('Error en storeCC: ' . $e->getMessage());

        return response()->json(['error' => 'Error al al realizar la venta.'.$e->getMessage()], 500);


    }
}
        public function store(StoreVentaRequest $request)
    {
        try{
            DB::beginTransaction();

            $fkTienda = session('user_fkTienda');
            $Estatus = session('user_estatus');

            //Llenar mi tabla venta
            //$venta = Venta::create($request->validated());

            $cliente_id=$request->cliente_id;
            $comprobante_id=$request->comprobante_id;
            $impuestotal=$request->impuesto;
            $fecha=$request->fecha;
            $fecha_hora=$request->fecha_hora;
            $total=$request->total;
            $user_id=$request->user_id;
            $tipofolio = $request->TipoFolio;

            $comprobantenomenclatura=Comprobante::where('id',$comprobante_id)->first();

                    $ultimoNumero = Venta::where('fkTienda', $fkTienda)
                            ->lockForUpdate()
                            ->count('numero_comprobante');

        $numero_comprobante = $ultimoNumero ? $ultimoNumero + 1 : 1;
        $numero_comprobante=$numero_comprobante.$tipofolio.$comprobantenomenclatura->ClaveVista.$comprobantenomenclatura->id;


            $venta = Venta::create([
                'fecha_hora'=>$fecha_hora,
                'impuesto'=>$impuestotal,
                'numero_comprobante'=>$numero_comprobante,
                'total'=>$total,
                'estado'=>1,
                'comprobante_id'=>$comprobante_id,
                'cliente_id'=>$cliente_id,
                'create_at'=>now(),
                'update_at'=>now(),
                'user_id'=>$user_id,
                'fkTienda'=>$fkTienda,
                'TipoFolio'=>$tipofolio,
                'fkFolio' => 0,
                'fkfactura' => '',
            ]);




            //Llenar mi tabla venta_producto
            //1. Recuperar los arrays
            $arrayProducto_id = $request->get('arrayidproducto');
            $arrayCantidad = $request->get('arraycantidad');
            $arrayPrecioVenta = $request->get('arrayprecioventa');
            $arrayDescuento = $request->get('arraydescuento');


            //2.Realizar el llenado
            $siseArray = count($arrayProducto_id);
            $cont = 0;

            while($cont < $siseArray){

                $venta->productos()->attach([
                    $arrayProducto_id[$cont] => [
                        'cantidad' => $arrayCantidad[$cont],
                        'precio_venta' => $arrayPrecioVenta[$cont],
                        'fkTienda'=>$fkTienda,
                        'descuento' => $arrayDescuento[$cont],
                    ]
                ]);
/*
                $venta->productos()->syncWithoutDetaching([
                    $arrayProducto_id[$cont] => [
                        'cantidad' => $arrayCantidad[$cont],
                        'precio_venta' => $arrayPrecioVenta[$cont],
                        'descuento' => $arrayDescuento[$cont],
                        'fkTienda',$fkTienda
                    ]
                ]);
*/
                //Actualizar stock
                $producto = Producto::find($arrayProducto_id[$cont]);
                $stockActual = $producto->stock;
                $cantidad = intval($arrayCantidad[$cont]);

                DB::table('productos')
                ->where('id',$producto->id)
                ->update([
                    'stock' => $stockActual - $cantidad
                ]);

                $cont++;
            }

            DB::commit();
        }catch(Exception $e){
            DB::rollBack();
            return back()->withErrors($e->getMessage());
        }

        return redirect()->route('ventas.index')->with('success','Venta exitosa');
    }

    private function evaluarFormula($formula, $A)
{
    try {

        // 🔥 1. convertir porcentajes (12% → 0.12)
        $formula = preg_replace_callback('/(\d+(\.\d+)?)%/', function ($match) {
            return $match[1] / 100;
        }, $formula);

        // 🔥 2. reemplazar variable A
        $formula = str_replace('A', $A, $formula);

        // 🔥 3. parsear expresión
        $parser = new StdMathParser();
        $AST = $parser->parse($formula);

        // 🔥 4. evaluar
        $evaluator = new Evaluator();
        $resultado = $AST->accept($evaluator);

        return round($resultado, 2);

    } catch (\Exception $e) {

        return 0;
    }
}

           public function storeCC(UpdateVentaRequest $request)
{
    try {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        DB::beginTransaction();

        $fkTienda = session('user_fkTienda');

        $cliente_id = $request->cliente_id;
        $idventa = $request->idventa;
        $comprobante_id = $request->comprobante_id;
        $numero_comprobante = $request->numero_comprobante;
        $impuestotal = $request->impuesto;
        $fecha = $request->fecha;
        $fecha_hora = $request->fecha_hora;
        $total = $request->total;
        $user_id = $request->user_id;
        $tipofolio = $request->input('TipoFolio');

        // Recuperar arrays
        $arrayProducto_id = $request->get('arrayidproducto');
        $idventa = $request->get('idventa');
        $arrayCantidad = $request->get('arraycantidad');
        $arrayPrecioVenta = $request->get('arrayprecioventa');
        $arrayDescuento = $request->get('arraydescuento');
        $arrayidcuenta = $request->get('arrayidcuenta');
        $arraymonto = $request->get('arraymonto');
        $arraytipomovimiento = $request->get('arraytipomovimiento');

            $cuentaArray = count($arrayidcuenta);
            $cont = 0;

                        $comprobantenomenclatura=Comprobante::where('id',$comprobante_id)->first();

                    $ultimoNumero = Venta::where('fkTienda', $fkTienda)
                            ->lockForUpdate()
                            ->count('numero_comprobante');

        $numero_comprobante = $ultimoNumero ? $ultimoNumero + 1 : 1;
        $numero_comprobante=$numero_comprobante.$tipofolio.$comprobantenomenclatura->ClaveVista.$comprobantenomenclatura->id;


        if($idventa==null){
            $venta = Venta::create([
                'fecha_hora'=>$fecha_hora,
                'impuesto'=>$impuestotal,
                'numero_comprobante'=>$numero_comprobante,
                'total'=>$total,
                'estado'=>1,
                'comprobante_id'=>$comprobante_id,
                'cliente_id'=>$cliente_id,
                'fkUserCreate' => $user_id,
                'fkUserCC' => $user_id,
                'create_at'=>now(),
                'update_at'=>now(),
                'user_id'=>$user_id,
                'fkTienda'=>$fkTienda,
                'TipoFolio'=>$tipofolio,
                'fkFolio' => 0,
                'fkfactura' => '',
            ]);
}else{
            $venta = Venta::findOrFail($idventa);
            $detalleVenta = DB::table('producto_venta')
            ->where('venta_id', $idventa)
            ->get();
}



                $folio = Folio::create([
                'descripcion'=>'Venta n.'.$idventa.', por un total de Q. '.$total.', numero de comprobante: '.$numero_comprobante.'.',
                'cabecera'=>'Venta cerrada por caja.',
                'EstatusContable'=>'C',
                'TipoFolio'=>$tipofolio,
                'FechaContabilizacion'=>now(),
                'fkComprobante'=>$comprobante_id,
                'created_at'=>now(),
                'updated_at'=>now(),
                'fkUsuario'=>$user_id,
                'fkTienda'=>$fkTienda,
                'idOrigen'=>$venta->id,
                'TipoMovimiento'=>'V'
            ]);

                        // Extraer todos los números de la cadena
            preg_match_all('/\d+/', $numero_comprobante, $coincidencias);

            // Obtener primer y último número si existen
            if (!empty($coincidencias[0])) {
                $primerNumero = $coincidencias[0][0];
                $ultimoNumero = $coincidencias[0][count($coincidencias[0]) - 1];

            }else {
                $primerNumero = '';
                $ultimoNumero = '';
            };

        if($tipofolio=="F"){
        $numero_comprobante2=$primerNumero.$tipofolio.$folio->idFolio.$folio->TipoMovimiento;

    }else{
        $comprobantenomenclatura=Comprobante::where('id',$comprobante_id)->first();
        $numero_comprobante2=$primerNumero.$tipofolio.$folio->idFolio.$comprobantenomenclatura->ClaveVista.$comprobantenomenclatura->id;

        };



while($cont < $cuentaArray) {


    DetalleFolio::create([
        'Monto' => $arraymonto[$cont],
        'Naturaleza' => $arraytipomovimiento[$cont],
        'fkCuenetaContable' => $arrayidcuenta[$cont],
        'fkUsuario' => $user_id,
        'fkTienda' => $fkTienda,
        'fkFolio' => $folio->idFolio,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    $cont++;
}


        // Actualizar datos de la venta
        DB::table('ventas')->where('id', $venta->id)->update([
            'fecha_hora' => $fecha_hora,
            'impuesto' => $impuestotal,
            'numero_comprobante' => $numero_comprobante2,
            'total' => $total,
            'estado' => 2,
            'comprobante_id' => $comprobante_id,
            'cliente_id' => $cliente_id,
            'fkUserEdit' => $user_id,
            'fkUserCC' => $user_id,
            'created_at' => now(),
            'updated_at' => now(),
            'fkTienda' => $fkTienda,
            'TipoFolio' => $tipofolio,
            'fkFolio' => $folio->idFolio
        ]);

        $venta = Venta::findOrFail($venta->id);


$sizeArray = count($arrayProducto_id);

$productosOriginales = DB::table('producto_venta')
    ->where('venta_id', $venta->id)
    ->get()
    ->keyBy('producto_id');

$productosProcesados = [];

for ($cont = 0; $cont < $sizeArray; $cont++) {

    $productoId = $arrayProducto_id[$cont];
    $cantidadNueva = intval($arrayCantidad[$cont]);
    $precioVenta = $arrayPrecioVenta[$cont];
    $descuento = $arrayDescuento[$cont];

    $productosProcesados[] = $productoId;

    if(isset($productosOriginales[$productoId])){

        $cantidadOriginal = intval($productosOriginales[$productoId]->cantidad);

        // ✔ detectar devolución
        if($cantidadNueva < $cantidadOriginal){

            $cantidadDevuelta = $cantidadOriginal - $cantidadNueva;

            DB::table('devoluciones_venta')->insert([
                'venta_id' => $venta->id,
                'producto_id' => $productoId,
                'cantidad_devuelta' => $cantidadDevuelta,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // devolver al stock
            DB::table('productos')
            ->where('id',$productoId)
            ->increment('stock',$cantidadDevuelta);
        }

        // ✔ actualizar pivote
        DB::table('producto_venta')
            ->where('venta_id', $venta->id)
            ->where('producto_id', $productoId)
            ->update([
                'cantidad' => $cantidadNueva,
                'precio_venta' => $precioVenta,
                'descuento' => $descuento,
                'fkTienda' => $fkTienda
            ]);

    } else {

        // ✔ producto nuevo en la venta
        $venta->productos()->attach([
            $productoId => [
                'cantidad' => $cantidadNueva,
                'precio_venta' => $precioVenta,
                'fkTienda' => $fkTienda,
                'descuento' => $descuento,
            ]
        ]);

        // descontar stock
        DB::table('productos')
            ->where('id',$productoId)
            ->decrement('stock',$cantidadNueva);
    }
}

# detectar productos eliminados completamente de la venta

foreach($productosOriginales as $productoId => $productoOriginal){

    if(!in_array($productoId,$productosProcesados)){

        $cantidadDevuelta = $productoOriginal->cantidad;

        DB::table('devoluciones_venta')->insert([
            'venta_id' => $venta->id,
            'producto_id' => $productoId,
            'cantidad_devuelta' => $cantidadDevuelta,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // devolver todo al stock
        DB::table('productos')
            ->where('id',$productoId)
            ->increment('stock',$cantidadDevuelta);

        // eliminar del pivote
        DB::table('producto_venta')
            ->where('venta_id',$venta->id)
            ->where('producto_id',$productoId)
            ->delete();
    }
}

        DB::commit();

      $rutaPdf =  $this->generarRecibo($venta->id);
      $rutaPdfPublica = asset('storage/recibos/recibo_'.$venta->id.'.pdf');




return redirect()->route('ventas.show', ['venta' => $venta->id])
                 ->with('success', 'Venta exitosa')
                 ->with('pdf', $rutaPdfPublica);


//return response()->file($rutaPdf);


    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Error en storeCC: ' . $e->getMessage());
        return redirect()->route('arqueocaja.cobrarventas', ['ventas' => $venta->id])
                 ->with('error', 'Error al guardar la venta: '. $e->getMessage());


    }
}

public function buscar(Request $request)
{
    $nombre = $request->texto;
$fkTienda = session('user_fkTienda');

                $subquery = DB::table('compra_producto')
            ->select('producto_id', DB::raw('MAX(created_at) as max_created_at'))
            ->where('fkTienda',$fkTienda)
            ->groupBy('producto_id');

        $productos = Producto::join('compra_producto as cpr', function ($join) use ($subquery) {
            $join->on('cpr.producto_id', '=', 'productos.id')
                ->whereIn('cpr.created_at', function ($query) use ($subquery) {
                    $query->select('max_created_at')
                        ->fromSub($subquery, 'subquery')
                        ->whereRaw('subquery.producto_id = cpr.producto_id');
                });
        })
            ->select('productos.nombre', 'productos.id', 'productos.stock', 'cpr.precio_venta')
            ->where('productos.fkTienda',$fkTienda)
            ->where('productos.estado', 1)
            ->where('nombre', 'like', '%'.$nombre.'%')
            ->where('productos.stock', '>', 0)
            ->get();

            return $productos;
}

       public function cobrarventas($idventa)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        if(Auth::check()){
        $id=$idventa;
        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        $cuentasContables = CuentaContable::where('fkTienda', $fkTienda)->get();

        $subquery = DB::table('compra_producto')
            ->select('producto_id', DB::raw('MAX(created_at) as max_created_at'))
            ->where('fkTienda',$fkTienda)
            ->groupBy('producto_id');

        $productos = Producto::join('compra_producto as cpr', function ($join) use ($subquery) {
            $join->on('cpr.producto_id', '=', 'productos.id')
                ->whereIn('cpr.created_at', function ($query) use ($subquery) {
                    $query->select('max_created_at')
                        ->fromSub($subquery, 'subquery')
                        ->whereRaw('subquery.producto_id = cpr.producto_id');
                });
        })
            ->select('productos.nombre', 'productos.id', 'productos.stock', 'cpr.precio_venta')
            ->where('productos.fkTienda',$fkTienda)
            ->where('productos.estado', 1)
            ->where('productos.stock', '>', 0)
            ->get();

        $clientes = Cliente::whereHas('persona', function ($query) {
            $query->where('estado', 1);
        })->get();

        $comprobantes = Comprobante::with('tienda')
        ->where('fkTienda', $fkTienda)
        ->where('ClaveVista','DV')
        ->get();

        $ventacabecera = DB::table('ventas as v')
        ->join('producto_venta as pv', 'pv.venta_id', '=', 'v.id')
        ->join('clientes as cl', 'cl.id', '=', 'v.cliente_id')
        ->join('personas as pr', 'pr.id', '=', 'cl.persona_id')
        ->join('tienda as t', 't.idTienda', '=', 'v.fkTienda')
        ->join('comprobantes as cm', 'cm.id', '=', 'v.comprobante_id')
        ->join('users as u', 'u.id', '=', 'v.user_id')
        ->join('productos as pro', 'pro.id', '=', 'pv.producto_id')
        ->select('v.id as idventa','pro.nombre as nameProducto','pv.precio_venta','pro.stock','pv.producto_id','pv.cantidad','pv.descuento','cm.formula', 'v.cliente_id', 'v.comprobante_id','t.idTienda', 'v.id', 'v.fecha_hora', 'v.numero_comprobante', 'v.total', 'pr.razon_social', 'pr.numero_documento', 't.Nombre', 'u.name', 'v.estado',
        'cm.tipo_comprobante', 'pr.tipo_persona')
        ->where('v.fkTienda',$fkTienda)
        ->where('v.id',$idventa)
        ->where('v.estado',1)
        ->distinct()
        ->get();



        $selectedItemId=$ventacabecera->first()->cliente_id ?? null;
        $selectedItemIdcomp=$ventacabecera->first()->comprobante_id ?? null;
        $comprobantenumero=$ventacabecera->first()->numero_comprobante ?? null;
        $idventa=$ventacabecera->first()->idventa ?? null;
        return view('arqueocaja.ventasdirecta', compact('productos', 'clientes', 'comprobantes','ventacabecera','selectedItemId','selectedItemIdcomp','comprobantenumero','cuentasContables', 'idventa'));

    } else{
        return redirect()->route('login');
    }
    }



public function generarRecibo($arqueocaja)
{
                    if(!Auth::check()){
            return redirect()->route('login');
        }

    $fkTienda = session('user_fkTienda');
    $Tienda = Tienda::where('idTienda', $fkTienda)->first();

    $ventas = DB::table('ventas as v')
        ->join('producto_venta as pv', 'pv.venta_id', '=', 'v.id')
        ->join('clientes as cl', 'cl.id', '=', 'v.cliente_id')
        ->join('personas as pr', 'pr.id', '=', 'cl.persona_id')
        ->join('tienda as t', 't.idTienda', '=', 'v.fkTienda')
        ->join('comprobantes as cm', 'cm.id', '=', 'v.comprobante_id')
        ->join('plantillahtml as ph', 'ph.id', '=', 'cm.fkPlantillaHtml')
        ->join('users as u', 'u.id', '=', 'v.user_id')
        ->join('productos as pd','pd.id','=','producto_id')
        ->select(
            'ph.id as idPlantilla',
            't.logo', 'v.id', 'v.fecha_hora', 'v.numero_comprobante', 'v.total',
            'pr.razon_social', 'pr.numero_documento', 't.Nombre', 'u.name', 'v.estado',
            'cm.tipo_comprobante','pr.tipo_persona','pv.producto_id','pv.cantidad',
            'pd.nombre as nombreproducto','pv.precio_venta as precioventa'
        )
        ->where('v.id', $arqueocaja)
        ->where('t.idTienda', $fkTienda)
        ->orderByDesc('v.id')
        ->get();

    $plantilla = DB::table('plantillahtml')
        ->select('cabecera','detalle','pie','consulta','fkDesignDocument')
        ->where('fkTienda', $fkTienda)
        ->where('id', $ventas->first()->idPlantilla)
        ->orderBy('id','DESC')
        ->first();

    $desingDocument = DB::table('documentdesigns')
        ->where('id', $plantilla->fkDesignDocument)
        ->orderBy('id','DESC')
        ->first();

    $cabecera = $plantilla->cabecera;
    $detalle = $plantilla->detalle;
    $pie = $plantilla->pie;
    $consulta = $plantilla->consulta;

    $tokens = ['idventa' => $ventas->first()->id, 'idtienda' => $fkTienda];
    $numFilas = $ventas->count();

    // Si height_mm o width_mm es null, dar valor por defecto

    $altura = ($desingDocument->alto_pt ?? 205) + ($numFilas * 15);
    $ancho = $desingDocument->ancho_pt ?? 226.77;
    $orientacion = $desingDocument->orientation ?? 'portrait';

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
    $rutaArchivo = $rutaCarpeta.'/recibo_'.$arqueocaja.'.pdf';
    $pdf->save($rutaArchivo);

    // Finalmente, abrir en el navegador
    return $rutaArchivo;
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
                    if(!Auth::check()){
            return redirect()->route('login');
        }

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
     * Display the specified resource.
     */
    public function show(Venta $venta)
    {
        return view('venta.show',compact('venta'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
public function destroy(string $id)
{
    if(!Auth::check()){
        return redirect()->route('login');
    }

    try{

        DB::beginTransaction();

        $venta = Venta::with('productos')->findOrFail($id);

        //Recorrer productos de la venta
        foreach($venta->productos as $producto){

            $cantidad = $producto->pivot->cantidad;

            DB::table('productos')
                ->where('id', $producto->id)
                ->update([
                    'stock' => DB::raw("stock + $cantidad")
                ]);

        }

        //Cambiar estado de la venta
        $venta->update([
            'estado' => 0,
            'fkUserAnular' => Auth::id(),
        ]);

        DB::commit();

        return redirect()->route('ventas.index')->with('success','Venta eliminada');

    }catch(Exception $e){

        DB::rollBack();
        return back()->withErrors($e->getMessage());

    }
}
}
