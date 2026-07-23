<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\ReglaPrecio;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MotorPreciosService
{
    /**
     * FASE 1: Evalúa y extrae los combos mixtos (Promociones grupales) de todo el carrito.
     * Recibe el carrito estructurado como: [ 'items' => [ ['id' => X, 'cantidad' => Y], ... ] ]
     */
    public function evaluarCombosMixtos($itemsCarrito, $fkTienda)
    {
        $ahora = Carbon::now();
        
        // Convertimos el carrito a un formato plano y asociativo [producto_id => cantidad] para manejarlo fácil
        $inventarioCarrito = [];
        foreach ($itemsCarrito as $item) {
            $inventarioCarrito[$item['id']] = ($inventarioCarrito[$item['id']] ?? 0) + $item['cantidad'];
        }

        // Buscamos todas las reglas de combo mixto de la tienda que estén vigentes
        $combos = ReglaPrecio::where('fkTienda', $fkTienda)
            ->where('tipo_regla', 'combo_mixto')
            ->where(function ($q) use ($ahora) {
                $q->whereNull('fecha_inicio')->orWhere('fecha_inicio', '<=', $ahora);
            })
            ->where(function ($q) use ($ahora) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $ahora);
            })
            ->orderBy('prioritaria', 'desc')
            ->get();

        $combosAplicados = [];

        foreach ($combos as $combo) {
            // Buscamos los productos obligatorios del combo en tu tabla 'menu_promocion_items'
            $requisitos = DB::table('menu_promocion_items')
                ->where('regla_precio_id', $combo->id)
                ->get();

            if ($requisitos->isEmpty()) {
                continue;
            }

            // Calculamos cuántas veces se puede armar este combo con lo que hay en el carrito
            $maximosCombosPosibles = 9999; 

            foreach ($requisitos as $req) {
                if (!isset($inventarioCarrito[$req->producto_id])) {
                    $maximosCombosPosibles = 0;
                    break;
                }

                $vecesQueCumple = floor($inventarioCarrito[$req->producto_id] / $req->cantidad_requerida);
                if ($vecesQueCumple < $maximosCombosPosibles) {
                    $maximosCombosPosibles = $vecesQueCumple;
                }
            }

            // Si se puede armar el combo al menos una vez, lo aplicamos
            if ($maximosCombosPosibles > 0) {
                // Restamos los productos del carrito porque ya consumieron la oferta grupal
                foreach ($requisitos as $req) {
                    $inventarioCarrito[$req->producto_id] -= ($req->cantidad_requerida * $maximosCombosPosibles);
                }

                $combosAplicados[] = [
                    'regla_id'        => $combo->id,
                    'nombre_promo'    => $combo->nombre,
                    'cantidad_armada' => $maximosCombosPosibles,
                    'precio_combo'    => $combo->valor_beneficio, // Precio cerrado del combo
                    'subtotal'        => $combo->valor_beneficio * $maximosCombosPosibles
                ];
            }
        }

        // Reestructuramos el carrito sobrante al formato original para pasárselo al motor individual
        $carritoSobrante = [];
        foreach ($inventarioCarrito as $prodId => $cant) {
            if ($cant > 0) {
                $carritoSobrante[] = ['id' => $prodId, 'cantidad' => $cant];
            }
        }

        return [
            'combos_aplicados' => $combosAplicados,
            'carrito_sobrante' => $carritoSobrante
        ];
    }

    /**
     * FASE 2: Evalúa un producto individual para aplicar mayoreo o bonificaciones por volumen (Ej: 3x2).
     */
    public function procesarLineaCarrito($productoId, $cantidad, $fkTienda)
    {
        $producto = Producto::where('id', $productoId)->where('fkTienda', $fkTienda)->firstOrFail();
        $precioOriginal = $producto->precio_base;
        $ahora = Carbon::now();

        // Buscar regla individual (escala_cantidad, bonificacion, descuento_fijo)
        $regla = $producto->reglasPrecios()
            ->where('reglas_precios.fkTienda', $fkTienda)
            ->whereIn('tipo_regla', ['escala_cantidad', 'bonificacion', 'descuento_fijo'])
            ->where(function ($query) use ($ahora) {
                $query->whereNull('fecha_inicio')->orWhere('fecha_inicio', '<=', $ahora);
            })
            ->where(function ($query) use ($ahora) {
                $query->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $ahora);
            })
            ->orderBy('prioritaria', 'desc')
            ->orderBy('cantidad_minima', 'desc')
            ->first();

        // Respuesta limpia por defecto (Precio unitario normal)
        $respuestaBase = [
            'producto_id'      => $producto->id,
            'nombre_producto'  => $producto->nombre,
            'cantidad'         => $cantidad,
            'precio_original'  => $precioOriginal,
            'precio_final_und' => $precioOriginal,
            'subtotal'         => $precioOriginal * $cantidad,
            'descuento_total'  => 0.00,
            'regla_aplicada'   => null
        ];

        if (!$regla || $cantidad < $regla->cantidad_minima) {
            return $respuestaBase;
        }

        switch ($regla->tipo_regla) {
            case 'escala_cantidad': // PRECIO MAYORISTA O MIXTO
                if ($regla->tipo_beneficio === 'precio_fijo') {
                    $precioFinal = $regla->valor_beneficio;
                    $subtotal = $precioFinal * $cantidad;
                    return [
                        'producto_id'      => $producto->id,
                        'nombre_producto'  => $producto->nombre,
                        'cantidad'         => $cantidad,
                        'precio_original'  => $precioOriginal,
                        'precio_final_und' => $precioFinal,
                        'subtotal'         => $subtotal,
                        'descuento_total'  => ($precioOriginal * $cantidad) - $subtotal,
                        'regla_aplicada'   => $regla->nombre
                    ];
                }
                if ($regla->tipo_beneficio === 'porcentaje') {
                    $descuento = $precioOriginal * ($regla->valor_beneficio / 100);
                    $precioFinal = $precioOriginal - $descuento;
                    return [
                        'producto_id'      => $producto->id,
                        'nombre_producto'  => $producto->nombre,
                        'cantidad'         => $cantidad,
                        'precio_original'  => $precioOriginal,
                        'precio_final_und' => $precioFinal,
                        'subtotal'         => $precioFinal * $cantidad,
                        'descuento_total'  => $descuento * $cantidad,
                        'regla_aplicada'   => $regla->nombre
                    ];
                }
                break;

            case 'bonificacion': // PROMOCIONES TIPO 3x2 o 4x3
                if ($regla->tipo_beneficio === 'unidad_gratis' && $regla->cantidad_paso > 0) {
                    $ciclos = floor($cantidad / $regla->cantidad_paso);
                    $unidadesRegaladas = $ciclos * $regla->valor_beneficio;
                    
                    $subtotal = $precioOriginal * ($cantidad - $unidadesRegaladas);
                    return [
                        'producto_id'      => $producto->id,
                        'nombre_producto'  => $producto->nombre,
                        'cantidad'         => $cantidad,
                        'precio_original'  => $precioOriginal,
                        'precio_final_und' => $subtotal / $cantidad, 
                        'subtotal'         => $subtotal,
                        'descuento_total'  => $unidadesRegaladas * $precioOriginal,
                        'regla_aplicada'   => $regla->nombre
                    ];
                }
                break;

            case 'descuento_fijo': // OFERTA DIRECTA O REBAJA SIN VOLUMEN MINIMO ELEVADO
                if ($regla->tipo_beneficio === 'porcentaje') {
                    $descuento = $precioOriginal * ($regla->valor_beneficio / 100);
                    $precioFinal = $precioOriginal - $descuento;
                    return [
                        'producto_id'      => $producto->id,
                        'nombre_producto'  => $producto->nombre,
                        'cantidad'         => $cantidad,
                        'precio_original'  => $precioOriginal,
                        'precio_final_und' => $precioFinal,
                        'subtotal'         => $precioFinal * $cantidad,
                        'descuento_total'  => $descuento * $cantidad,
                        'regla_aplicada'   => $regla->nombre
                    ];
                }
                break;
        }

        return $respuestaBase;
    }
}
