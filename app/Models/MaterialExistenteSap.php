<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialExistenteSap extends Model
{
    use HasFactory;

    // Vincula explícitamente este modelo con la tabla indicada
    protected $table = 'MaterialExistenteSap';

    // Declaración de columnas aptas para llenado masivo / asignación masiva
    protected $fillable = [
        'fkTienda',
        'serie',
        'SKU',
        'almacen',
        'Lote',
        'MAC1',
        'MAC2',
        'MAC3',
        'ESTATUS',
        'COSTO',
        'cantidad',
        'CENTRO',
        'Modificado_el',
        'Modificado_por',
        'Creado_el',
        'Creado_por',
        'TIPO',
        'unidadmedida',
        'TIPOMOVIMIENTO'
    ];

public function tienda()
{
    // El tercer parámetro le indica a Laravel que la llave primaria es idTienda y no 'id'
    return $this->belongsTo(Tienda::class, 'fkTienda', 'idTienda');
}

}
