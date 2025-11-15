<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class usuariotienda extends Model
{
    use HasFactory;

    public function usuariotienda(){
        return $this->belongsTo(usuariotienda::class);
    }

    public function Tienda()
    {
        return $this->belongsTo(Tienda::class, 'fkTienda', 'idTienda'); // Relación con la tabla tienda
    }
    public function User()
    {
        return $this->belongsTo(User::class, 'fkUsuario', 'id'); // Relación con la tabla usuario
    }
    protected $table = 'usuario_tienda'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'idUsuarioTienda'; // Especifica la clave primaria
    public $incrementing = true; // Si es autoincremental
    protected $keyType = 'int'; // Tipo de la clave primaria
    protected $fillable = ['idUsuarioTienda','fkUsuario', 'fkTienda', 'Estatus', 'FechaIngreso', 'FechaEgreso', 'FechaBaja', 'FechaActualizacion', 'created_at', 'updated_at'];
}
