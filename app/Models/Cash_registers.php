<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cash_registers extends Model
{
    use HasFactory;

    protected $table = 'cash_registers';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'Nombre',
        'fkTienda',
        'Estatus',
        'initial_amount',
        'closing_amount',
        'opened_at',
        'closed_at',
        'created_at',
        'updated_at'
    ];

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'fkTienda', 'idTienda');
    }
}
