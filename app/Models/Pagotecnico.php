<?php

namespace App\Models;

use Fureev\Trees\NestedSetTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\NestedRules;


class Pagotecnico extends Model
{
    use NestedSetTrait;

    protected $table = 'pagotecnico'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'id'; // Especifica la clave primaria

    public $incrementing = true; // Si es autoincremental


    protected $fillable = ['id','Orden','SKU','Descripcion','OBS','Cantidad','COSTOPAGO','created_at','updated_at', 'fkTienda', 'fkTecnico','Natura','Status'];

    public $timestamps = true;

}
