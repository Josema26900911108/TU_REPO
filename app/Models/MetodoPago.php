<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    use HasFactory;

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'fkTienda', 'idTienda');
    }
    protected $table = 'MetodoPago'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'idMetodoPago'; // Especifica la clave primaria
    public $incrementing = true; // Si es autoincremental
    protected $keyType = 'int'; // Tipo de la clave primaria
    protected $fillable = ['Nombre','MetodoPago', 'created_at', 'update_at','fkTienda']; // Agrega aquí todos los campos que deseas que sean "fillables"
}
