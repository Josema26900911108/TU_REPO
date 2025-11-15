<?php

namespace App\Models;

use Fureev\Trees\NestedSetTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\NestedRules;


class Materialmanoobra extends Model
{
    use HasFactory;

    protected $table = 'materialmanoobra'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'id'; // Especifica la clave primaria

    public $incrementing = true; // Si es autoincremental


    protected $fillable = ['id', 'SKU', 'Descripcion', 'TIPO', 'unidadmedida','CATEGORIA', 'COSTOPAGO', 'CATEGORIACOBRO','created_at', 'update_at','fkTienda'];

    public $timestamps = true;

    public function tienda()
{
    return $this->belongsTo(Tienda::class, 'fkTienda','idTienda');
}


}
