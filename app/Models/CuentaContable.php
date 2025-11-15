<?php

declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Fureev\Trees\{NestedSetTrait,Contracts\TreeConfigurable};
use Fureev\Trees\Config\Base;

class CuentaContable extends Model implements TreeConfigurable
{

    use NestedSetTrait;

    protected $table = 'cuentas_contables'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'id'; // Especifica la clave primaria

    public $incrementing = true; // Si es autoincremental
    protected $keyType = 'int'; // Para BIGINT(20)

    protected $fillable = ['id', 'nombre', 'padre_id', 'created_at', 'update_at', 'formula', 'fkTienda','parent_id', 'lft', 'rgt', 'depth'];

    public $timestamps = true;

    protected static function buildTreeConfig(): Base
    {
        $config = new Base(true);
        //$config->parent()->setType('id_padre'); // Asegúrate de que esto sea correcto
        $config->parent()->setName('id_padre');

        //$config->parent()->setType('unsignedBigInteger'); // Si 'padre_id' es unsignedBigInteger en la base de datos
        $config->parent()->setName('id');
        //$config->tree()->setType('id'); // O el tipo que estés usando

        return $config;
    }

    public function parent()
    {
        return $this->belongsTo(CuentaContable::class, 'padre_id');
    }
    public function DetalleComprobante(){
        return $this->hasMany(DetalleComprobante::class);
    }
    public function children()
    {
        return $this->hasMany(CuentaContable::class, 'padre_id');


    }
    public function padre()
    {
        return $this->belongsTo(CuentaContable::class, 'padre_id');
    }

    public function hijos()
    {
        return $this->hasMany(CuentaContable::class, 'padre_id');
    }

}
