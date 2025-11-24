<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleFolio extends Model
{
    use HasFactory;
    protected $guarded = ['idDetalleFolio'];

protected $fillable = [
    'Monto', 'Naturaleza', 'fkCuenetaContable', 'created_at', 'updated_at',
    'fkFolio', 'fkTienda', 'fkUsuario'
];


protected $table = 'DetalleFolio'; // 👈 aquí fuerzas a Laravel a usar la tabla singular
        public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'fkTienda', 'idTienda');
    }
public function cuentaContable()
{
    return $this->belongsTo(CuentaContable::class, 'fkCuenetaContable', 'id');
}


    public function folio()
    {
        return $this->belongsTo(Folio::class, 'fkFolio', 'idFolio');
    }
}
