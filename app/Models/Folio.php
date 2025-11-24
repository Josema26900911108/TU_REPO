<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folio extends Model
{
    use HasFactory;
    protected $primaryKey = 'idFolio';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = ['idFolio'];
    protected $table = 'Folio'; // 👈 aquí fuerzas a Laravel a usar la tabla singular


    public function comprobante(){
        return $this->belongsTo(Comprobante::class);
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'fkTienda', 'idTienda');
    }

public function detallefolio()
{
    return $this->belongsToMany(CuentaContable::class, 'detallefolio')
        ->withPivot('Monto', 'Naturaleza', 'fkCuentaContable', 'fkUsuario', 'fkTienda')
        ->withTimestamps();
}

}
