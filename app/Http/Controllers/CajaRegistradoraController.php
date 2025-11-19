<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\CajaRegistradora;
use App\Http\Requests\StoreCashRequest;

class CajaRegistradoraController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-Caja|crear-venta|mostrar-venta|eliminar-venta', ['only' => ['index']]);
        $this->middleware('permission:crear-Caja', ['only' => ['create', 'store']]);
        $this->middleware('permission:mostrar-Caja', ['only' => ['show']]);
        $this->middleware('permission:eliminar-Caja', ['only' => ['destroy']]);
    }
    public function index()
    {
        $cajaregistradora = CajaRegistradora::latest()->get();
        return view('cajaregistradora.index', compact(var_name: 'cajaregistradora'));

    }
    public function registrarVenta($venta)
{
    // Lógica para registrar la venta

    $saldoActual = CajaRegistradora::latest()->first()->saldo ?? 0;

    CajaRegistradora::create([
        'tipo_movimiento' => 'venta',
        'monto' => $venta->monto,
        'saldo' => $saldoActual + $venta->monto,
        'descripcion' => 'Venta de productos',
    ]);
}
public function registrarCompra($compra)
{
    // Lógica para registrar la compra

    $saldoActual = CajaRegistradora::latest()->first()->saldo ?? 0;

    CajaRegistradora::create([
        'tipo_movimiento' => 'compra',
        'monto' => $compra->monto,
        'saldo' => $saldoActual - $compra->monto,
        'descripcion' => 'Compra de productos',
    ]);
}
public function arqueoCaja()
{
    $caja = CajaRegistradora::orderBy('created_at', 'desc')->get();

    $saldoActual = CajaRegistradora::latest()->first()->saldo ?? 0;

    return view('arqueo_caja', compact('caja', 'saldoActual'));
}


}
