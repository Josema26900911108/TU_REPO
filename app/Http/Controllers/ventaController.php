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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ventaController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:cobrar-ventadirecta|ver-venta|crear-venta|mostrar-venta|eliminar-venta', ['only' => ['index']]);
        $this->middleware('permission:crear-venta', ['only' => ['create', 'store']]);
        $this->middleware('permission:mostrar-venta', ['only' => ['show']]);
        $this->middleware('permission:eliminar-venta', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
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
    public function create()
    {
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

       public function cobrarventas($idventa)
    {
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
        ->join('clientes as cl', 'pv.venta_id', '=', 'v.id')
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

    public function storeCC(UpdateVentaRequest $request)
{
    try {

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
        $arrayCantidad = $request->get('arraycantidad');
        $arrayPrecioVenta = $request->get('arrayprecioventa');
        $arrayDescuento = $request->get('arraydescuento');
        $arrayidcuenta = $request->get('arrayidcuenta');
        $arraymonto = $request->get('arraymonto');
        $arraytipomovimiento = $request->get('arraytipomovimiento');

            $cuentaArray = count($arrayidcuenta);
            $cont = 0;




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
                'idOrigen'=>$idventa,
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
        DB::table('ventas')->where('id', $idventa)->update([
            'fecha_hora' => $fecha_hora,
            'impuesto' => $impuestotal,
            'numero_comprobante' => $numero_comprobante2,
            'total' => $total,
            'estado' => 2,
            'comprobante_id' => $comprobante_id,
            'cliente_id' => $cliente_id,
            'created_at' => now(),
            'updated_at' => now(),
            'fkTienda' => $fkTienda,
            'TipoFolio' => $tipofolio,
            'fkFolio' => $folio->idFolio
        ]);

        $venta = Venta::findOrFail($idventa);

        // Eliminar productos anteriores
        DB::table('producto_venta')->where('venta_id', $idventa)->delete();

        $sizeArray = count($arrayProducto_id);
        for ($cont = 0; $cont < $sizeArray; $cont++) {
            $venta->productos()->attach([
                $arrayProducto_id[$cont] => [
                    'cantidad' => $arrayCantidad[$cont],
                    'precio_venta' => $arrayPrecioVenta[$cont],
                    'fkTienda' => $fkTienda,
                    'descuento' => $arrayDescuento[$cont],
                ]
            ]);

            // Actualizar stock
            $producto = Producto::find($arrayProducto_id[$cont]);
            $cantidad = intval($arrayCantidad[$cont]);

            if ($producto) {
                $producto->stock -= $cantidad;
                $producto->save();
            }

        }

        DB::commit();

      $rutaPdf =  $this->generarRecibo($idventa);
      $rutaPdfPublica = asset('storage/recibos/recibo_'.$idventa.'.pdf');




return redirect()->route('ventas.show', ['venta' => $idventa])
                 ->with('success', 'Venta exitosa')
                 ->with('pdf', $rutaPdfPublica);


//return response()->file($rutaPdf);


    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Error en storeCC: ' . $e->getMessage());
        return redirect()->route('arqueocaja.cobrarventas', ['ventas' => $idventa])
                 ->with('error', 'Error al guardar la venta: '. $e->getMessage());


    }
}

public function generarRecibo($arqueocaja)
{
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
     * Store a newly created resource in storage.
     */
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
   //dd(request()->method());
        Venta::where('id',$id)
        ->update([
            'estado' => 0
        ]);

        return redirect()->route('ventas.index')->with('success','Venta eliminada');
    }
}
