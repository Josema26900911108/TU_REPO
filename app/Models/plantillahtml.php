<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class plantillahtml extends Model
{
        use HasFactory;
        protected $table='plantillahtml';
        protected $fillable = [
    'Titulo',
    'cabecera',
    'detalle',
    'pie',
    'fkTienda',
    'descripcion',
    'plantillahtml',
    'consulta',
    'fkDesignDocument'
];


    public function instalaciones() {
        return $this->hasMany(InstalacionTecnico::class);
    }

        public function tienda() {
        return $this->hasMany(Tienda::class);
    }
    public function documentdesings() {
        return $this->hasMany(DocumentDesings::class, 'id', 'fkDocumentDesing');
    }
}
