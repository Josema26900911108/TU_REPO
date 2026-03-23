<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lotesalarma extends Model
{
    use HasFactory;

    protected $fillable = ['producto_id', 'numero_lote', 'cantidad', 'fecha_vencimiento', 'notificado','compra_id','fkTienda'];
    protected $table = 'lotesalarma';

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
        public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }
}
