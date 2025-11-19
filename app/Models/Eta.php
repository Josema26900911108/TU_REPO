<?php

namespace App\Models;

use Fureev\Trees\NestedSetTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\NestedRules;


class Eta extends Model
{
    use HasFactory;

    protected $table = 'eta'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'id'; // Especifica la clave primaria

    public $incrementing = true; // Si es autoincremental


    protected $fillable = ['id','Orden','SKU','Descripcion','Cantidad','Serie','MAC1','MAC2','MAC3','TIPO_DE_SERVICIO','TIPO_DE_ORDEN','CENTRO','EMPLEADO','created_at','updated_at','fkTienda'];

    public $timestamps = true;

    public function tienda()
{
    return $this->belongsTo(Tienda::class, 'fkTienda','idTienda');
}


}
