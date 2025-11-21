<?php

namespace App\Http\Controllers;
use App\Models\ArqueoCaja;
use App\Models\Cash_registers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Tienda;
use App\Models\Comprobante;
use App\Models\CuentaContable;
use Barryvdh\DomPDF\Facade\Pdf;

class ArqueoCajaController extends Controller
{
    public function show($arqueoqueja)
    {
        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        $arqueocaja = [];
        $caja=[];

        $id=$arqueoqueja;
                // Si el estatus es 'ER', cargar todas las compras
                if ($Estatus == 'ER') {
                    $arqueocaja = ArqueoCaja::where('fkCaja',$id)
                    ->latest()
                    ->get();

                    $caja=Cash_registers::latest()
                    ->where('id',$id)
                    ->first()
                    ->get();


                } else {
                    $caja=Cash_registers::latest()
                    ->where('id',$id)
                    ->first()
                    ->get();

                    $arqueocaja = ArqueoCaja::latest()
                    ->where('fkCaja',$id)
                    ->latest()
                    ->get();
                }


        return view('arqueocaja.index',compact('arqueocaja','caja'));
    }
    public function compras($arqueoqueja)
    {
        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        $arqueocaja = [];
        $caja=[];

        $id=$arqueoqueja;
                // Si el estatus es 'ER', cargar todas las compras
                if ($Estatus == 'ER') {
                    $arqueocaja = ArqueoCaja::where('fkCaja',$id)
                    ->latest()
                    ->get();

                    $caja=Cash_registers::latest()
                    ->where('id',$id)
                    ->first()
                    ->get();


                } else {
                    $caja=Cash_registers::latest()
                    ->where('id',$id)
                    ->first()
                    ->get();

                    $arqueocaja = ArqueoCaja::latest()
                    ->where('fkCaja',$id)
                    ->latest()
                    ->get();
                }


        return view('arqueocaja.compras',compact('arqueocaja','caja'));
    }
    public function panel($arqueoqueja)
    {
        if(!Auth::check()){
            return redirect()->route('login');
        }
        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        $arqueocaja = [];
        $caja=[];

        $id=$arqueoqueja;
                // Si el estatus es 'ER', cargar todas las compras
                if ($Estatus == 'ER') {
                    $arqueocaja = ArqueoCaja::where('fkCaja',$id)
                    ->latest()
                    ->first();

                    $caja=Cash_registers::latest()
                    ->where('id',$id)
                    ->first();


                } else {
                    $caja=Cash_registers::latest()
                    ->where('id',$id)
                    ->first();

                    $arqueocaja = ArqueoCaja::latest()
                    ->where('fkCaja',$id)
                    ->latest()
                    ->first();
                }


        return view('arqueocaja.panel',compact('arqueocaja','caja'));
    }
    public function pagos($arqueoqueja)
    {
        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        $arqueocaja = [];
        $caja=[];

        $id=$arqueoqueja;
                // Si el estatus es 'ER', cargar todas las compras
                if ($Estatus == 'ER') {
                    $arqueocaja = ArqueoCaja::where('fkCaja',$id)
                    ->latest()
                    ->get();

                    $caja=Cash_registers::latest()
                    ->where('id',$id)
                    ->first()
                    ->get();


                } else {
                    $caja=Cash_registers::latest()
                    ->where('id',$id)
                    ->first()
                    ->get();

                    $arqueocaja = ArqueoCaja::latest()
                    ->where('fkCaja',$id)
                    ->latest()
                    ->get();
                }


        return view('arqueocaja.index',compact('arqueocaja','caja'));
    }
    public function ingresos($arqueoqueja)
    {
        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        $arqueocaja = [];
        $caja=[];

        $id=$arqueoqueja;
                // Si el estatus es 'ER', cargar todas las compras
                if ($Estatus == 'ER') {
                    $arqueocaja = ArqueoCaja::where('fkCaja',$id)
                    ->latest()
                    ->get();

                    $caja=Cash_registers::latest()
                    ->where('id',$id)
                    ->first()
                    ->get();


                } else {
                    $caja=Cash_registers::latest()
                    ->where('id',$id)
                    ->first()
                    ->get();

                    $arqueocaja = ArqueoCaja::latest()
                    ->where('fkCaja',$id)
                    ->latest()
                    ->get();
                }


        return view('arqueocaja.index',compact('arqueocaja','caja'));
    }
    public function retiros($arqueoqueja)
    {
        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        $arqueocaja = [];
        $caja=[];

        $id=$arqueoqueja;
                // Si el estatus es 'ER', cargar todas las compras
                if ($Estatus == 'ER') {
                    $arqueocaja = ArqueoCaja::where('fkCaja',$id)
                    ->latest()
                    ->get();

                    $caja=Cash_registers::latest()
                    ->where('id',$id)
                    ->first()
                    ->get();


                } else {
                    $caja=Cash_registers::latest()
                    ->where('id',$id)
                    ->first()
                    ->get();

                    $arqueocaja = ArqueoCaja::latest()
                    ->where('fkCaja',$id)
                    ->latest()
                    ->get();
                }


        return view('arqueocaja.index',compact('arqueocaja','caja'));
    }
    public function bancos($arqueoqueja)
    {
        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        $arqueocaja = [];
        $caja=[];

        $id=$arqueoqueja;
                // Si el estatus es 'ER', cargar todas las compras
                if ($Estatus == 'ER') {
                    $arqueocaja = ArqueoCaja::where('fkCaja',$id)
                    ->latest()
                    ->get();

                    $caja=Cash_registers::latest()
                    ->where('id',$id)
                    ->first()
                    ->get();


                } else {
                    $caja=Cash_registers::latest()
                    ->where('id',$id)
                    ->first()
                    ->get();

                    $arqueocaja = ArqueoCaja::latest()
                    ->where('fkCaja',$id)
                    ->latest()
                    ->get();
                }


        return view('arqueocaja.index',compact('arqueocaja','caja'));
    }
    public function valsession(){
        if(!Auth::check()){
            return redirect()->route('login');

        }
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
        return view('arqueocaja.ventas', compact('productos', 'clientes', 'comprobantes','ventacabecera','selectedItemId','selectedItemIdcomp','comprobantenumero','cuentasContables', 'idventa'));

    } else{
        return redirect()->route('login');
    }
    }

    public function generarRecibo($arqueocaja)
{
    //$pdf = Pdf::loadView('pdf.ticket')->setPaper([0, 0, 226.77, 600], 'portrait'); // térmica
    $fkTienda = session('user_fkTienda');
    $Tienda = Tienda::where('idTienda', $fkTienda)->first();

    $data = base64_decode($Tienda->logo);
$image = imagecreatefromstring($data);
// Redimensionar o comprimir aquí si necesitas
ob_start();
imagejpeg($image, null, 60); // calidad 60%
$compressed = base64_encode(ob_get_clean());



                            $ventas=DB::table('ventas as v')
                            ->join('producto_venta as pv', 'pv.venta_id', '=', 'v.id')
                            ->join('clientes as cl', 'cl.id', '=', 'v.cliente_id')
                            ->join('personas as pr', 'pr.id', '=', 'cl.persona_id')
                            ->join('tienda as t', 't.idTienda', '=', 'v.fkTienda')
                            ->join('comprobantes as cm', 'cm.id', '=', 'v.comprobante_id')
                            ->join('users as u', 'u.id', '=', 'v.user_id')
                            ->join('productos as pd','pd.id','=','producto_id')
                            ->select('TO_BASE64(t.logo) as logo', 'v.id', 'v.fecha_hora', 'v.numero_comprobante', 'v.total', 'pr.razon_social', 'pr.numero_documento', 't.Nombre', 'u.name', 'v.estado','cm.tipo_comprobante','pr.tipo_persona','pv.producto_id','pv.cantidad','pd.nombre as nombreproducto','pv.precio_venta as precioventa')
                            ->where('v.id',$arqueocaja)
                            ->where('t.idTienda', $fkTienda)
                            ->orderByDesc('v.id')
                            ->get();

                            $plantilla=DB::table('plantillahtml')
                            ->select('cabecera','detalle','pie','consulta')
                            ->where('fkTienda',$fkTienda)
                            ->where('Titulo','TicketVenta80mm')
                            ->orderBy('id','DESC')
                            ->limit(1)
                            ->first();

                            $cabecera=$plantilla->cabecera;
                            $detalle=$plantilla->detalle;
                            $pie=$plantilla->pie;
                            $consulta=$plantilla->consulta;

                            $tokens = [
                                'idventa' => $ventas->first()->id,
                                'idtienda' => $fkTienda
                            ];
                            $numFilas = $ventas->count();
                            $altura = 335 + ($numFilas * 15);

                        $cons=$this->procesarConsulta($consulta,$tokens);
                        $tokenss=$this->ejecutarconsulta($cons);

                $htmlFinal=$this->procesarPlantilla($cabecera,$detalle,$pie,$tokenss['columnas'],$tokenss['filas']);
     $pdf = Pdf::loadHTML($htmlFinal)->setPaper([0, 0, 226.77, $altura], 'portrait');

    return $pdf->stream('PDF.ticket'); // o ->download('recibo.pdf');
    //phpinfo();
}
/**
 * Renderiza una plantilla reemplazando variables {{var}}
 * y generando el detalle repetitivo.
 */
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

function procesarConsulta($consulta, $tokens)
{
    $consultaprocesada = $consulta;

    foreach ($tokens as $token => $valor) {
        $pattern = '/@{{\s*' . preg_quote($token, '/') . '\s*}}/';
        $consultaprocesada = preg_replace($pattern, $valor, $consultaprocesada);
    }

    return $consultaprocesada;
}



    public function obtenerDatos()
    {
        $cuentasContables = CuentaContable::all(); // Obtén los datos de la base de datos
        return response()->json($cuentasContables); // Devuelve los datos en formato JSON
    }
    public function ventas($ventas)
    {
    /*dd([
        'usuario' => auth()->user()->name,
        'tiene_permiso' => auth()->user()->can('caja-anular-venta'),
    ]);
*/
  //  dd(request()->method());
        $id=$ventas;
                // Si el estatus es 'ER', cargar todas las compras

                $fkTienda = session('user_fkTienda');
                $Estatus = session('user_estatus');

                $ventas = [];
                $productos = [];

                        // Si el estatus es 'ER', cargar todas las compras
                        if ($Estatus == 'ER') {

if ($Estatus == 'ER') {
    $ventas = DB::table('ventas as v')
        ->join('producto_venta as pv', 'pv.venta_id', '=', 'v.id')
        ->join('clientes as cl', 'cl.id', '=', 'v.cliente_id')
        ->join('personas as pr', 'pr.id', '=', 'cl.persona_id')
        ->join('tienda as t', 't.idTienda', '=', 'v.fkTienda')
        ->join('comprobantes as cm', 'cm.id', '=', 'v.comprobante_id')
        ->join('users as u', 'u.id', '=', 'v.user_id')
        ->select('v.id', 'v.fecha_hora', 'v.numero_comprobante', 'v.total', 'pr.razon_social', 'pr.numero_documento', 't.Nombre', 'u.name', 'v.estado','cm.tipo_comprobante','pr.tipo_persona')
        ->whereIn('v.estado', [1, 2, 3,4])
        ->distinct()
        ->orderByDesc('v.id')
        ->get();
}


                        } else {
                            $ventas=DB::table('ventas as v')
                            ->join('producto_venta as pv', 'pv.venta_id', '=', 'v.id')
                            ->join('clientes as cl', 'cl.id', '=', 'v.cliente_id')
                            ->join('personas as pr', 'pr.id', '=', 'cl.persona_id')
                            ->join('tienda as t', 't.idTienda', '=', 'v.fkTienda')
                            ->join('comprobantes as cm', 'cm.id', '=', 'v.comprobante_id')
                            ->join('users as u', 'u.id', '=', 'v.user_id')
                            ->select('v.id', 'v.fecha_hora', 'v.numero_comprobante', 'v.total', 'pr.razon_social', 'pr.numero_documento', 't.Nombre', 'u.name', 'v.estado','cm.tipo_comprobante','pr.tipo_persona')
                            ->whereIn('v.estado',[1,2,3,4])
                            ->where('t.idTienda', $fkTienda)
                            ->distinct()
                            ->orderByDesc('v.id')
                            ->get();

                                        // Filtrar los productos solo por la tienda del usuario
                        $productos = Producto::with('comprobante','tienda')
                        ->where('fkTienda', $fkTienda)
                        ->whereIn('estado', [1,2,3,4])
                        ->distinct()
                        ->latest()
                        ->get();
                        }

                return view('arqueocaja.listaventas',compact('ventas'));

            }

             public function destroy(Request $request, $id)
    {
   //dd(request()->method());

   $idventa=$request->input('idventa');
   $idcaja=$request->input('idcaja');

        Venta::where('id',$idventa)
        ->update([
            'estado' => 0
        ]);

        return redirect()->back()->with('success', 'Venta anulada correctamente');

    }

    public function store(Request $request, $id)
    {
        try{
            DB::beginTransaction();
            $fkTienda = session('user_fkTienda');

            //Llenar tabla compras
  $arqueo = ArqueoCaja::create([
    'CEF' => $request->input("CEF-{$id}"),
    'VD' => $request->input("VD-{$id}"),
    'VO' => $request->input("VO-{$id}"),
    'D' => $request->input("D-{$id}"),
    'CC' => $request->input("CC-{$id}"),
    'OG' => $request->input("OG-{$id}"),
    'CEI' => $request->input("CEI-{$id}"),
    'ChCo' => 0,
    'vales' => 0,
    'created_at' => now(),
    'updated_at' => now(),
    'fkTienda' => $fkTienda,
    'fkCaja' => $id,
    'Estatus' => 'O'
]);

            $caja = Cash_registers::findOrFail($id);

            // Actualiza el registro
            $caja->update([
                'initial_amount' => $request->input("CEI-{$id}"),
                'opened_at' => now(),
                'updated_at'=>now(),
                'Estatus'=>'O'
            ]);

            DB::commit();
        }catch(Exception $e){
            DB::rollBack();
            dd($e->getMessage());
        }
        return redirect()->route('cash.index')->with('success','compra exitosa');

    }
    public function CierreCaja(Request $request, $id){
        $msj="";
        try{
    $actualizararqueo=ArqueoCaja::findOrFail($id)
    ->where('Estatus','O')->first();

    if (!$actualizararqueo) {
        return back()->withErrors('Record not found');
    }

    // Actualiza el registro
// Update the record
$actualizararqueo->update([
    'CEF' => $request->input("CEFC-{$id}"),
    'VD' => $request->input("VDC-{$id}"),
    'VO' => $request->input("VOC-{$id}"),
    'D' => $request->input("DC-{$id}"),
    'CC' => $request->input("CCC-{$id}"),
    'OG' => $request->input("OGC-{$id}"),
    'CEI' => $request->input("CEIC-{$id}"),
    'ChCo' => 0,
    'vales' => 0,
    'opened_at' => now(),
    'updated_at' => now(),
    'Estatus' => 'C'
]);


    $caja = Cash_registers::findOrFail($id);

    // Actualiza el registro
    $caja->update([
        'CEF' => $request->CEFC,
        'closed_at' => now(),
        'updated_at'=>now(),
        'Estatus'=>'C'
    ]);

    DB::commit();
    $msj="Se cierra de forma exitosa";
    }catch(Exception $e){
        DB::rollBack();
        dd($e->getMessage());
        $msj="Se ha detectado un error: "+$e;
    }
    return redirect()->route('cash.index')->with('success',$msj);
    }
}
