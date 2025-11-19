<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cash_registers;
use App\Http\Requests\UpdateCashRegisterRequest;
use App\Http\Requests\StoreCashRequest;
use App\Models\Tienda;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\Comprobante;

class CashRegisterController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-caja|crear-caja|editar-caja|eliminar-caja', ['only' => ['index']]);
        $this->middleware('permission:crear-caja', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-caja', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-caja', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');
            if($Estatus=='ER'){
                $cashRegister = Cash_registers::with('tienda')//se cambia
                ->latest()->get();
            }else{
                $cashRegister = Cash_registers::with('tienda')
                ->where('fkTienda',$fkTienda)
                ->latest()->get();
            }
        return view('cash_register.index', compact('cashRegister'));
    }
    public function open(Request $request)
    {
        $cashRegister = new Cash_registers();
         $cashRegister->initial_amount = $request->input('initial_amount');
        $cashRegister->opened_at = now();
        $cashRegister->save();
        return redirect()->route('cash_register.index')->with('success', 'Caja abierta exitosamente.');
    }
    public function mostrarDetalles($idComprobante)
    {
        // Obtener el comprobante seleccionado
        $comprobante = Comprobante::with(['detalles' => function ($query) {
            $query->with('cuentaContable'); // Cargar cuentas contables
        }])->findOrFail($idComprobante);

        // Sumar valores según la naturaleza
        $totales = $comprobante->detalles->groupBy('Naturaleza')->map(function ($items) {
            return $items->sum('valorminimo');
        });

        return view('compras.create', compact('comprobante', 'totales'));
    }

    public function create()
    {
        $tiendas=Tienda::all();
        return view('cash_register.create',compact('tiendas'));
    }
    public function update(UpdateCashRegisterRequest $request, Cash_registers $cash)
    {
        $ver=$cash->id;

        Cash_registers::where('id', $cash->id)
            ->update($request->validated());

        return redirect()->route('cash.index')->with('success', 'Caja editada');
    }
    public function close(Request $request, $id)
    {
        $cashRegister = Cash_registers::findOrFail($id);
        $cashRegister->closing_amount = $request->input('closing_amount');
        $cashRegister->closed_at = now();
        $cashRegister->save();

        return redirect()->back()->with('success', 'Caja cerrada exitosamente.');
    }

    public function edit(Cash_registers $cash)
    {
      //  $Tienda=Cash_registers::join('tienda as t', 'Cash_registers.fkTienda', '=', 't.idTienda')
       // ->select('t.idTienda as idTienda', 't.nombre as Nombre')
       // ->where('Cash_registers.id', $cash->id)
       // ->get();
       $Tienda=Tienda::all();
        return view('cash_register.edit',compact('cash','Tienda'));
    }

    public function destroy(string $id)
    {
        Cash_registers::where('id', $id)->delete();

        return redirect()->route('cash.index')->with('success', 'Caja Eliminada correctamente.');
    }

    public function store(StoreCashRequest $request)
    {

        try {
            DB::beginTransaction();

            $ver=$request->validated();
            $verid=$request->fkTienda;

            // Crear el nuevo permiso
            Cash_registers::create(array_merge(
                $request->validated(),
                ['fkTienda' => $request->fkTienda],
                ['initial_amount' => 0],
                ['closing_amount' => 0],
                ['Estatus' => 'A']
            ));
            // Asignar los permisos asociados


            DB::commit();
            return redirect()->route('cash.index')->with('success', 'Caja registrado correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('cash.create')->with('error', 'Hubo un error al registrar el Caja.');
        }
    }



    // Otros métodos para procesos adicionales

}
