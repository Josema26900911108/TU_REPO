<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Centro extends Model
{
    use HasFactory;
        protected $fillable = ['codigo', 'nombre', 'fkTienda'];
        protected $table = 'centro';

// En app/Models/Centro.php
public function tienda()
{
    // foreign_key = la columna en la tabla 'centros' (ej. fkTienda)
    // local_key = la columna en la tabla 'tiendas' (ej. idTienda)
    return $this->belongsTo(Tienda::class, 'fkTienda', 'idTienda');
}


    public function movimientos() {
        return $this->hasMany(MovimientoMaterial::class);
    }
}
