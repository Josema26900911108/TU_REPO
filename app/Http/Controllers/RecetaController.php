<?php

namespace App\Http\Controllers;

use App\Models\Receta;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class RecetaController extends Controller
{
    // Mostrar todos los platos que tienen recetas configuradas
    public function index()
    {
        $platosConReceta = Producto::has('receta')->with('receta.ingrediente')->get();
        return view('recetas.index', compact('platosConReceta'));
    }

    // Formulario para crear una receta nueva
    public function create()
    {
        // Solo productos marcados como "Producto Terminado" pueden tener receta
        $productosTerminados = Producto::where('origen_uso', 'producto_terminado')->get();
        // Solo productos marcados como "Materia Prima" o "Insumo" pueden ser ingredientes
        $ingredientes = Producto::whereIn('origen_uso', ['materia_prima', 'insumo_servicio'])->get();

        return view('recetas.create', compact('productosTerminados', 'ingredientes'));
    }

    // Guardar la receta (Múltiples ingredientes a la vez)
    public function store(Request $request)
    {
        $request->validate([
            'producto_padre_id' => 'required|exists:productos,id',
            'ingrediente_id'    => 'required|array',
            'ingrediente_id.*'  => 'exists:productos,id',
            'cantidad'          => 'required|array',
            'cantidad.*'        => 'numeric|min:0.001',
        ]);

        try {
            DB::beginTransaction();

            // Borramos receta anterior si existe para actualizarla por completo
            Receta::where('producto_padre_id', $request->producto_padre_id)->delete();

            foreach ($request->ingrediente_id as $key => $id) {
                Receta::create([
                    'producto_padre_id' => $request->producto_padre_id,
                    'ingrediente_id'    => $id,
                    'cantidad'          => $request->cantidad[$key],
                    'unidad_medida'     => $request->unidad_medida[$key] ?? 'PZA'
                ]);
            }

            // Actualizar el costo del plato automáticamente
            $this->actualizarCostoPlato($request->producto_padre_id);

            DB::commit();
            return redirect()->route('recetas.index')->with('success', 'Receta guardada y costo actualizado.');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // Función privada para calcular el costo del plato basado en ingredientes
    private function actualizarCostoPlato($productoId)
    {
        $receta = Receta::where('producto_padre_id', $productoId)->get();
        $costoTotal = 0;

        foreach ($receta as $item) {
            // Buscamos el precio de compra del ingrediente
            $ingrediente = Producto::find($item->ingrediente_id);
            $costoTotal += ($ingrediente->precio_compra * $item->cantidad);
        }

        // Guardamos el nuevo costo en la tabla productos
        Producto::where('id', $productoId)->update(['precio_compra' => $costoTotal]);
    }

    public function destroy($id)
    {
        Receta::where('producto_padre_id', $id)->delete();
        return back()->with('success', 'Receta eliminada.');
    }
}
