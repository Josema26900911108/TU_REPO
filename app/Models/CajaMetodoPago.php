<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CajaMetodoPago extends Model
{
    use HasFactory;

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'fkTienda', 'idTienda');
    }
    protected $table = 'cajametodopago'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'idCajaMetodoPago'; // Especifica la clave primaria
    public $incrementing = true; // Si es autoincremental
    protected $keyType = 'int'; // Tipo de la clave primaria
    protected $fillable = ['idCajaMetodoPago','fkCaja', 'fkMetodoPago', 'Monto','created_at', 'update_at', 'clavemetodo','fkTienda']; // Agrega aquí todos los campos que deseas que sean "fillables"
}
