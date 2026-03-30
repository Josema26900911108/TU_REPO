<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuModificador extends Model
{
    use HasFactory;

     protected $fillable = [
        'menu_id', 'producto_id', 'nombre_extra', 
        'precio_adicional', 'cantidad_descontar', 
        'es_obligatorio', 'limite_maximo'
    ];

        protected $table = 'menu_modificadores'; 


    public function menu() {
        return $this->belongsTo(Menu::class);
    }

public function productos()
{
    return $this->belongsToMany(Producto::class, 'menu_modificador_producto')
                ->withPivot('precio_override', 'orden_visualizacion', 'predefinido')
                ->withTimestamps();
}  
}
