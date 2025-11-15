<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Centro extends Model
{
    use HasFactory;
        protected $fillable = ['codigo', 'nombre'];

    public function tienda() {
        return $this->hasMany(Tienda::class);
    }

    public function movimientos() {
        return $this->hasMany(MovimientoMaterial::class);
    }
}
