<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    use HasFactory;

    protected $fillable = ['codigo', 'fecha_vencimiento', 'fkProductos'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
        public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }
}
