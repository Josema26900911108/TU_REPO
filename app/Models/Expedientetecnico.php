<?php

namespace App\Models;

use Fureev\Trees\NestedSetTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\NestedRules;


class Expedientetecnico extends Model
{
    use HasFactory;

    protected $table = 'expedientetecnico'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'id'; // Especifica la clave primaria

    public $incrementing = true; // Si es autoincremental


    protected $fillable = ['id','fkTienda','Orden','virtual','Status','Tipo_servicio','Tipo_orden','NOMBRECLIENTE','DIRECCION','OBS','SIGLASCENTRAL','AREA','FECHAINSTALACION','fkTecnico','AUTORIZA','ESTATUS','TECNOLOGIA','created_at','updated_at'];

    public $timestamps = true;

    public function tienda()
{
    return $this->belongsTo(Tienda::class, 'fkTienda','idTienda');
}

    public function tecnico()
{
    return $this->belongsTo(Tecnico::class, 'fkTecnico','id');
}




}
