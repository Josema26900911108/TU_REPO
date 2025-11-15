<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVentaRequest;
use App\Http\Requests\UpdateVentaRequest;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\DetalleFolio;
use App\Models\Folio;
use App\Models\Producto;
use App\Models\Venta;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ventaController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-venta|crear-venta|mostrar-venta|eliminar-venta', ['only' => ['index']]);
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


        return view('venta.create', compact('productos', 'clientes', 'comprobantes'));
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
        $tipofolio = $request->TipoFolio;

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
                'numero_comprobante'=>$numero_comprobante,
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
        return redirect()->route('ventas.index')->with('success', 'Venta exitosa');

    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Error en storeCC: ' . $e->getMessage());
        return redirect()->route('ventas.index')->with('error', 'Error al guardar la venta');
    }
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
