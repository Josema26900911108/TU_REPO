<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    protected $fillable = ['nombre', 'tipo_ciclo'];

    // Obtener todos los clientes asignados a esta ruta
    public function clientes()
    {
        return $this->belongsToMany(Cliente::class, 'ruta_dia_cliente')
                    ->withPivot('dia_semana', 'orden')
                    ->withTimestamps();
    }
}
