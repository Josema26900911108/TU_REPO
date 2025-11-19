<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoMovimiento extends Model
{
    use HasFactory;
        protected $fillable = ['codigo', 'descripcion', 'abreviatura'];

    public function movimientos() {
        return $this->hasMany(MovimientoMaterial::class);
    }

        public function tienda() {
        return $this->hasMany(Tienda::class);
    }
}
