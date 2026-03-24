<?php

namespace App\Models;
use Fureev\Trees\NestedSetTrait;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoMateriales extends Model
{
      use NestedSetTrait;

    protected $table = 'movimiento_materiales'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'id'; // Especifica la clave primaria

    public $incrementing = true; // Si es autoincremental

public function treematerialcategoria()
{
    return $this->belongsTo(Treematerialescategoria::class, 'SKU', 'SKU')
    //->where('activo', 1)
                ->withDefault(); // Para evitar null si no existe relación
}
public function producto()
{
    return $this->belongsTo(Producto::class, 'fkMateriales');
}

// Relación con el Lote específico
public function lote()
{
    return $this->belongsTo(Lotesalarma::class, 'fkLotes');
}

// Relación con la Tienda (Corregida: un movimiento pertenece a una tienda)
public function tienda()
{
    return $this->belongsTo(Tienda::class, 'fkTienda');
}
public $timestamps = true;
protected $fillable = [
    'fkTienda', 'fkMateriales', 'fkLotes', 'centro', 'contrata', 'almacen',
    'clase_movimiento', 'tipo_movimiento', 'texto_clase_movimiento',
    'hora_entrada', 'fecha_contabilizacion', 'unidad_medida_base', 'cantidad',
    'documento_material', 'posicion_documento', 'referencia', 'documento_material_sap',
    'referencia_sap', 'texto_clase_movimiento_sap', 'fecha_contabilizacion_sap',
    'unidad_medida_base_sap', 'cantidad_sap', 'clase_de_movimiento_sap',
    'centro_sap', 'origen_uso'
];






}
