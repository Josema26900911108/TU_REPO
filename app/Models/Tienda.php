<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cash_registers;

class Tienda extends Model
{
    use HasFactory;

    public function cashRegisters()
    {
        return $this->hasMany(Cash_registers::class, 'fkTienda', 'idTienda');
    }
    public function tienda()
    {
        return $this->hasMany(usuariotienda::class, 'fkTienda', 'idTienda');
    }
       public function centros()
    {
        return $this->hasMany(Centro::class, 'fkTienda', 'idTienda');
    }

           public function contrata()
    {
        return $this->hasMany(Contrata::class, 'fkTienda', 'idTienda');
    }

           public function documentos_sap()
    {
        return $this->hasMany(DocumentoSAP::class, 'fkTienda', 'idTienda');
    }

           public function instalaciones_tecnicos()
    {
        return $this->hasMany(InstalacionTecnico::class, 'fkTienda', 'idTienda');
    }

public function tecnico()
{
    return $this->hasMany(Tecnico::class, 'fkTienda', 'idTienda');
}

public function DesingDocument(){
    return $this->belongsTo(DocumentDesings::class, 'fkDesignDocument', 'id');
}

    public function movimientos_materiales()
    {
        return $this->hasMany(MovimientoMaterial::class, 'fkTienda', 'idTienda');
    }
     public function materialmanoobra()
    {
        return $this->hasMany(Materialmanoobra::class, 'fkTienda', 'idTienda');
    }
    protected $table = 'tienda'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'idTienda'; // Especifica la clave primaria
    public $incrementing = true; // Si es autoincremental
    protected $keyType = 'int'; // Tipo de la clave primaria
    protected $fillable = ['idTienda','Nombre', 'Direccion', 'telefono', 'descripcion', 'EstatusContable','fkCentro','logo', 'departamento','municipio','representante','nit','fkDesignDocument']; // Agrega aquí todos los campos que deseas que sean "fillables"

}
