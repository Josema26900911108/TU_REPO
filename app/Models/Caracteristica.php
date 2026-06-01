<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caracteristica extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion'];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function presentacion()
    {
        return $this->belongsTo(Presentacione::class, 'presentacione_id');
    }

    public function caja()
    {
        return $this->belongsTo(Cash_registers::class, 'cash_registers_id');
    }

    public function cajaRegistradora()
    {
        return $this->belongsTo(CajaRegistradora::class, 'cajaregistradora_id');
    }
}
