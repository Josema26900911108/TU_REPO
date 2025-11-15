<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleComprobante extends Model
{
    use HasFactory;
    public function cuentaContable()
    {
        return $this->belongsTo(CuentaContable::class, 'fkCuentaContable', 'id');
    }

    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class, 'fkComprobante', 'id');
    }
    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'fkTienda', 'idTienda');
    }

    protected $table = 'detalle_comprobantes'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'id'; // Especifica la clave primaria
    public $incrementing = true; // Si es autoincremental
    protected $keyType = 'int'; // Tipo de la clave primaria
    protected $fillable = ['id','nombre', 'formula','valorminimo','fkComprobante','fkCuentaContable','Naturaleza','created_at', 'update_at', 'formula','fkTienda']; // Agrega aquí todos los campos que deseas que sean "fillables"
}
