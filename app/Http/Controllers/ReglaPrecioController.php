<?php

namespace App\Http\Controllers;

use App\Models\ReglaPrecio;
use App\Models\Producto;
use Illuminate\Http\Request;

class ReglaPrecioController extends Controller
{
    public function index()
    {
        $reglas = ReglaPrecio::withCount('productos')->get();
        return response()->json($reglas);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'          => 'required|string|max:255',
            'tipo_regla'      => 'required|in:escala_cantidad,bonificacion,combo_mixto,descuento_fijo',
            'cantidad_minima' => 'required|integer|min:1',
            'tipo_beneficio'  => 'required|in:precio_fijo,porcentaje,unidad_gratis',
            'valor_beneficio' => 'required|numeric',
            'productos'       => 'array' // IDs de productos a los que aplica
        ]);

        $regla = ReglaPrecio::create($validated);

        // Si envías productos, los asociamos de inmediato
        if ($request->has('productos')) {
            $regla->productos()->sync($request->productos);
        }

        return response()->json([
            'message' => 'Regla de precio creada con éxito',
            'regla'   => $regla
        ]);
    }

    /**
     * Asignar una regla existente a un producto específico.
     */
    public function asignarAProducto(Request $request)
    {
        $producto = Producto::findOrFail($request->producto_id);
        $producto->reglasPrecios()->syncWithoutDetaching($request->regla_id);
        
        return response()->json(['message' => 'Regla vinculada al producto']);
    }

    public function destroy($id)
    {
        ReglaPrecio::destroy($id);
        return response()->json(['message' => 'Regla eliminada']);
    }
}
