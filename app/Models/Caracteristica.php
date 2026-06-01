<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caracteristica extends Model
{
    use HasFactory;

    protected $table = 'caracteristicas';

    protected $fillable = ['nombre', 'descripcion', 'estado'];

    // CORREGIDO: Una característica TIENE MUCHAS categorías asociadas (La llave está en categorias)
    public function categorias()
    {
        return $this->hasMany(Categoria::class, 'caracteristica_id');
    }

    // CORREGIDO: Si marcas, presentaciones y cajas siguen la misma lógica, deben ser hasMany o hasOne
    public function marcas()
    {
        return $this->hasMany(Marca::class, 'caracteristica_id');
    }

    public function presentaciones()
    {
        return $this->hasMany(Presentacione::class, 'caracteristica_id');
    }

    public function cajas()
    {
        return $this->hasMany(Cash_registers::class, 'caracteristica_id');
    }

    public function cajasRegistradoras()
    {
        return $this->hasMany(CajaRegistradora::class, 'caracteristica_id');
    }
}
