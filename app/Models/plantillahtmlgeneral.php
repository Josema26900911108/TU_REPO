<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class plantillahtmlgeneral extends Model
{
            use HasFactory;
        protected $table='plantillahtmlgeneral';
        protected $fillable = [
    'Titulo',
    'cabecera',
    'detalle',
    'pie',
    'descripcion',
    'plantillahtml',
    'consulta'
];
}
