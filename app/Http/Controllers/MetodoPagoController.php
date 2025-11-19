<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class MetodoPagoController extends Controller
{
    public function detalle($id)
{


    $result = DB::table('caja as c')
    ->rightJoin('cajametodopago as cm', 'c.idCaja', '=', 'cm.fkCaja')
    ->rightJoin('metodopago as m', 'm.idMetodoPago', '=', 'cm.fkMetodoPago')
    ->rightJoin('arqueocaja as ac', 'ac.idArqueoCaja', '=', 'c.idArqueoCaja')
    ->selectRaw('IFNULL(SUM(c.Monto), ac.cei) as Monto, IFNULL(m.MetodoPago, "CEI") AS MetodoPago, ac.Estatus, ac.fkCaja, ac.cei')
    ->where('ac.fkCaja', $id)
    ->where('ac.Estatus', '=', DB::raw("'O'")) // <-- Esto evita que agregue comillas extra
    ->groupBy('m.MetodoPago', 'ac.Estatus', 'ac.fkCaja', 'ac.cei')
    ->get();

    return response()->json($result ); // Retorna los datos como JSON
}

}
