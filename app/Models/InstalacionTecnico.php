<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstalacionTecnico extends Model
{
    use HasFactory;

        protected $fillable = [
        'tecnico_id', 'material_id', 'cantidad_usada',
        'fecha', 'ubicacion', 'referencia_instalacion', 'observaciones'
    ];

    public function tecnico() {
        return $this->belongsTo(Tecnico::class);
    }

    public function material() {
        return $this->belongsTo(Producto::class);
    }
        public function tienda() {
        return $this->hasMany(Tienda::class);
    }
}
