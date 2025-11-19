<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cash_registers extends Model
{
    use HasFactory;

    public function Cash_registers(){
        return $this->belongsTo(Cash_registers::class);
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'fkTienda', 'idTienda'); // Relación con la tabla tienda
    }
    protected $table = 'Cash_registers'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'id'; // Especifica la clave primaria
    public $incrementing = true; // Si es autoincremental
    protected $keyType = 'int'; // Tipo de la clave primaria
    protected $fillable = ['id','Nombre', 'fkTienda', 'Estatus', 'initial_amount', 'closing_amount', 'opened_at', 'closed_at', 'created_at', 'updated_at']; // Agrega aquí todos los campos que deseas que sean "fillables"
}
