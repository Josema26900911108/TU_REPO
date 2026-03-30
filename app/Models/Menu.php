<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
    'producto_id', 'nombre_menu', 'descripcion_comercial', 'precio_venta', 
    'costo_receta', 'margen_ganancia', 'disponible', 'es_promocion', 
    'valido_desde', 'valido_hasta', 'tiempo_preparacion_minutos', 
    'prioridad', 'imagen_menu', 'estacion_preparacion'
];

public function producto() {
    return $this->belongsTo(Producto::class);
}

}
