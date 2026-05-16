<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReglaPrecioAplicacion extends Model
{
    // Forzamos el nombre de la tabla que definimos en la migración
    protected $table = 'regla_precio_aplicacion';

    protected $fillable = [
        'regla_id', 
        'producto_id', 
        'lote_id', 
        'fkTienda'
    ];

    // Relación inversa hacia la regla
    public function regla()
    {
        return $this->belongsTo(ReglaPrecio::class, 'regla_id');
    }

    // Relación hacia el producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
