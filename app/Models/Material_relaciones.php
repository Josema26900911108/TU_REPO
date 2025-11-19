<?php

declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Fureev\Trees\{NestedSetTrait,Contracts\TreeConfigurable};
use Fureev\Trees\Config\Base;

class Material_relaciones extends Model
{

    use HasFactory;
    protected $table = 'material_relaciones'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'id'; // Especifica la clave primaria

    public $incrementing = true; // Si es autoincremental
    protected $keyType = 'int'; // Para BIGINT(20)

    protected $fillable = ['id','fkTienda','nombre','SKU','depende_SKU','tipo_relacion','created_at','updated_at','idtree'];

    public $timestamps = true;
}
