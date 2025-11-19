<?php

namespace App\Models;

use Fureev\Trees\NestedSetTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tecnico extends Model
{

    use HasFactory;

    protected $table = 'tecnico'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'id'; // Especifica la clave primaria

    public $incrementing = true; // Si es autoincremental


    protected $fillable = ['id','fkTienda','nombre','codigo','especialidad','created_at','updated_at','logo','fkpersona'];

    public $timestamps = true;

    public function persona() {
            return $this->belongsTo(Persona::class, 'fkPersona', 'id');
    }

public function tienda()
{
    return $this->belongsTo(Tienda::class, 'fkTienda', 'idTienda');
}
public function tecnico()
{
    return $this->belongsTo(Tecnico::class, 'fkTecnico','id'); // ajusta 'tecnico_id' si se llama diferente
}

}
