<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    use HasFactory;

    public function documento(){
        return $this->belongsTo(Documento::class);
    }

    public function proveedore(){
        return $this->hasOne(Proveedore::class);
    }

    public function cliente(){
        return $this->hasOne(Cliente::class);
    }

    public function tecnico(){
            return $this->hasMany(Tecnico::class, 'fkpersona');

    }

    protected $table = 'personas'; // Cambia 'clientes' por el nombre correcto de tu tabla
    protected $primaryKey = 'id'; // Especifica la clave primaria
    protected $fillable = ['id', 'razon_social','direccion','tipo_persona','estado','documento_id','numero_documento'];
}
