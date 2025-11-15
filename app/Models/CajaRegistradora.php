<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CajaRegistradora extends Model
{

    protected $table = 'caja';

    protected $fillable = [
        'id',
        'tipo_movimiento',
        'monto',
        'saldo',
        'descripcion',
        'EstatusContable',
        'idVenta',
        'idCompra',
        'idArqueoCaja',
        'EstatusArqueo'
    ];
    use HasFactory;
}
