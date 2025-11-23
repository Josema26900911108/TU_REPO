<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentDesings extends Model
{
    use HasFactory;

    protected $table = 'documentdesigns';

    protected $fillable = [
        'nombre',
        'ancho_mm',
        'ancho_pt',
        'alto_pt',
        'alto_mm',
        'tipo',
        'orientacion_vertical',
        'config',
    ];

    protected $casts = [
        'config' => 'array', // JSON automatico
    ];
}
