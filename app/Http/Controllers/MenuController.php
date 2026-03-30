<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;

class MenuController extends Controller
{
    // Vista tipo "Carta de Restaurante"
    public function index()
    {
        // Obtenemos categorías que tengan productos listos para la venta
        $menu = Categoria::with(['productos' => function($q) {
            $q->where('origen_uso', 'producto_terminado')->where('stock', '>', 0);
        }])->get();

        return view('menu.index', compact('menu'));
    }

    // Función para ver detalles de un plato y sus alérgenos (ingredientes)
    public function show($id)
    {
        $plato = Producto::with('receta.ingrediente')->findOrFail($id);
        return view('menu.detalle', compact('plato'));
    }
}
