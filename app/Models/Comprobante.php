<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model
{
    use HasFactory;

    public function cuentasContables()
    {
        return $this->hasManyThrough(
            CuentaContable::class,        // El modelo final al que quieres acceder
            DetalleComprobante::class,    // El modelo intermedio
            'fkComprobante',              // La clave foránea en `detalle_comprobantes` que apunta a `comprobantes`
            'id',                         // La clave foránea en `cuentas_contables` (debería ser 'id', que es la clave primaria)
            'id',                         // La clave local en `comprobantes`
            'fkCuentaContable'            // La clave foránea en `detalle_comprobantes` que apunta a `cuentas_contables`
        );
    }


    public function compras(){
        return $this->hasMany(Compra::class);
    }

    public function ventas(){
        return $this->hasMany(Venta::class);
    }
    public function detalles()
    {
        return $this->hasMany(DetalleComprobante::class, 'fkComprobante', 'id');
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'fkTienda', 'idTienda');
    }
    protected $table = 'comprobantes'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'id'; // Especifica la clave primaria
    public $incrementing = true; // Si es autoincremental
    protected $keyType = 'int'; // Tipo de la clave primaria
    protected $fillable = ['id','tipo_comprobante', 'estado', 'created_at', 'update_at', 'formula','fkTienda','ClaveVista']; // Agrega aquí todos los campos que deseas que sean "fillables"
}
