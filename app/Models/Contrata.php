<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contrata extends Model
{
    use HasFactory;
        protected $fillable = ['nombre', 'codigo', 'descripcion'];

    public function movimientos() {
        return $this->hasMany(MovimientoMaterial::class);
    }
        public function tienda() {
        return $this->hasMany(Tienda::class);
    }
}
