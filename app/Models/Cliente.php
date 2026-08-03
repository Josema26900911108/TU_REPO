<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    public function persona(){
        return $this->belongsTo(Persona::class);
    }

    public function ventas(){
        return $this->hasMany(Venta::class);
    }
    public function cliente(){

    }
     
    public function rutas()
    {
        return $this->belongsToMany(Ruta::class, 'ruta_dia_cliente')
                    ->withPivot('dia_semana', 'orden')
                    ->withTimestamps();
    }
    protected $table = 'clientes'; // Cambia 'clientes' por el nombre correcto de tu tabla
    protected $primaryKey = 'id'; // Especifica la clave primaria
    protected $fillable = [
        'id', 'created_at', 'updated_at', // Ajusta los campos según tu base de datos
    ];
}
