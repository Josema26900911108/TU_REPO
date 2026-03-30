<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receta extends Model
{
    use HasFactory;

    protected $table = 'recetas';

    protected $fillable = [
        'producto_padre_id', // El plato final (Ej: Hamburguesa)
        'ingrediente_id',    // El insumo (Ej: Carne)
        'cantidad',          // Cantidad necesaria (Ej: 0.250)
        'unidad_medida'      // KG, PZA, LT, etc.
    ];

    // Relación con el Producto terminado
    public function productoPadre()
    {
        return $this->belongsTo(Producto::class, 'producto_padre_id');
    }

    // Relación con el Ingrediente/Insumo
    public function ingrediente()
    {
        return $this->belongsTo(Producto::class, 'ingrediente_id');
    }
}
