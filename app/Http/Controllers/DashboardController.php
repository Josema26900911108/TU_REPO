<?php

namespace App\Http\Controllers;

use App\Exports\GenericExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function index()
    {
        // Obtener datos desde la BD
        $data = DB::table('ventas')
            ->select('fecha_hora', 'total')
            ->orderBy('fecha_hora')
            ->get();

        // Para Chart.js
        $labels = $data->pluck('fecha');
        $values = $data->pluck('total');

        return view('dashboard.index', compact('labels', 'values', 'data'));
    }

public function exportExcel(Request $request)
{
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
public function exportcompraExcel(Request $request)
{
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
