<?php

namespace App\Models;
use Fureev\Trees\NestedSetTrait;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoMaterial extends Model
{
      use NestedSetTrait;

    protected $table = 'movimientomateriales'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'id'; // Especifica la clave primaria

    public $incrementing = true; // Si es autoincremental

public function treematerialcategoria()
{
    return $this->belongsTo(Treematerialescategoria::class, 'SKU', 'SKU')
    //->where('activo', 1)
                ->withDefault(); // Para evitar null si no existe relación
}


    protected $fillable = ['id','serie','SKU','almacen',
    'Lote','MAC1','MAC2','MAC3','ESTATUS','COSTO','CENTRO','Modificado_el',
    'Modificado_por','Creado_el','Creado_por','TIPO','unidadmedida','TIPOMOVIMIENTO',
    'fkTienda','created_at','updated_at','cantidad','fkExpediente']; // Agrega aquí todos los campos que deseas que sean "fillables"

    public $timestamps = true;

        public function tienda() {
        return $this->hasMany(Tienda::class);
    }
}
