<?php

namespace App\Http\Controllers;

use App\Exports\GenericExport;
use App\Models\Lotesalarma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function index()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }
$vencimientosCriticos =  Lotesalarma::where('fecha_vencimiento', '<=', now()->addDays(15))
    ->where('cantidad', '>', 0)
    ->count();

        $data = DB::table('ventas')
            ->select('fecha_hora', 'total')
            ->orderBy('fecha_hora')
            ->get();

        // Para Chart.js
        $labels = $data->pluck('fecha');
        $values = $data->pluck('total');

        return view('dashboard.index', compact('labels', 'values', 'data','vencimientosCriticos'));
    }

public function exportExcel(Request $request)
{
                    if(!Auth::check()){
            return redirect()->route('login');
        }

$fkTienda = session('user_fkTienda');
$productosSeleccionados = (array) $request->input('producto', []);


$query = DB::table('ventas as v')
    ->join('producto_venta as pv', 'v.id', '=', 'pv.venta_id')
    ->join('productos as p', 'pv.producto_id', '=', 'p.id')
    ->select(
        'v.total', 'v.impuesto', 'v.numero_comprobante', 'v.user_id',
        'pv.cantidad', 'pv.precio_venta', 'pv.descuento', 'p.nombre',
        'p.codigo', 'p.stock', 'p.perecedero', 'p.estado', 'p.fecha_vencimiento',
        'v.fkTienda', 'v.fecha_hora as fecha'
    )
    ->where('v.fkTienda', $fkTienda)
    ->where('v.estado', 2)
    ->orderBy('v.fecha_hora');

if ($request->inicio) $query->whereDate('v.fecha_hora', '>=', $request->inicio);
if ($request->fin) $query->whereDate('v.fecha_hora', '<=', $request->fin);
if (!empty($productosSeleccionados) && !in_array(0, $productosSeleccionados)) {
    $query->whereIn('p.id', $productosSeleccionados);
}

$data = $query->get();

return Excel::download(new GenericExport($data), 'reporte_dashboard.xlsx');


}

public function DevexportExcel(Request $request)
{
                    if(!Auth::check()){
            return redirect()->route('login');
        }

$fkTienda = session('user_fkTienda');
$productosSeleccionados = (array) $request->input('producto', []);


$query = DB::table('ventas as v')
->join('devoluciones_venta as d', 'v.id', '=', 'd.venta_id')
    ->join('producto_venta as pv', 'd.producto_id', '=', 'pv.venta_id')
    ->join('productos as p', 'pv.producto_id', '=', 'p.id')
    ->join('users as u', 'v.user_id', '=', 'u.id')
    ->select(
        'v.id as id_venta','u.name as nombre_usuario', 'v.numero_comprobante',
        DB::raw("CONCAT("."'\' '"." , p.codigo) as codigo_producto"),'p.nombre as nombre_producto', 'd.cantidad_devuelta',
         'v.user_id',
        'pv.cantidad', 'pv.precio_venta', 'pv.descuento','v.total',
        'p.stock as stock_actual',  'v.estado as Estado_Venta',
        'v.fkTienda', 'v.fecha_hora as fecha_venta', 'd.created_at as fecha_devolucion'
    )
    ->where('v.fkTienda', $fkTienda)
    ->where('v.estado', 2)
    ->orderBy('v.fecha_hora');

if ($request->inicio) $query->whereDate('d.created_at', '>=', $request->inicio);
if ($request->fin) $query->whereDate('d.created_at', '<=', $request->fin);
if (!empty($productosSeleccionados) && !in_array(0, $productosSeleccionados)) {
    $query->whereIn('v.user_id', $productosSeleccionados);
}

$data = $query->get();

return Excel::download(new GenericExport($data), 'reporte_devolucionmobil_'.$request->inicio.'_'.$request->fin.'.xlsx');


}
public function exportcompraExcel(Request $request)
{
                    if(!Auth::check()){
            return redirect()->route('login');
        }

$fkTienda = session('user_fkTienda');
$productosSeleccionados = (array) $request->input('producto', []);


$query = DB::table('compras as v')
    ->join('compra_producto as pv', 'v.id', '=', 'pv.compra_id')
    ->join('productos as p', 'pv.producto_id', '=', 'p.id')
    ->select(
        'v.total', 'v.impuesto', 'v.numero_comprobante',
        'pv.cantidad', 'pv.precio_venta','pv.precio_compra',  'p.nombre',
        'p.codigo', 'p.stock', 'p.perecedero', 'p.estado', 'p.fecha_vencimiento',
        'v.fkTienda', 'v.fecha_hora as fecha'
    )
    ->where('v.fkTienda', $fkTienda)
    ->orderBy('v.fecha_hora');

if ($request->inicio) $query->whereDate('v.fecha_hora', '>=', $request->inicio);
if ($request->fin) $query->whereDate('v.fecha_hora', '<=', $request->fin);
if (!empty($productosSeleccionados) && !in_array(0, $productosSeleccionados)) {
    $query->whereIn('p.id', $productosSeleccionados);
}

$data = $query->get();

return Excel::download(new GenericExport($data), 'Compra_reporte_dashboard.xlsx');


}


}
