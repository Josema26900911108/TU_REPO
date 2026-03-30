<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReglaPrecio extends Model
{
    use HasFactory;

    protected $table = 'reglas_precios';

    protected $fillable = [
        'nombre',
        'tipo_regla',      // escala_cantidad, bonificacion, combo_mixto, descuento_fijo
        'cantidad_minima',
        'cantidad_paso',   // útil para 3x2, 6x4, etc.
        'tipo_beneficio',  // precio_fijo, porcentaje, unidad_gratis
        'valor_beneficio', // El monto, el % o la cantidad de unidades gratis
        'fecha_inicio',
        'fecha_fin',
        'prioritaria',     // boolean para desempatar reglas
        'activo'
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin'    => 'datetime',
        'prioritaria'  => 'boolean',
        'activo'       => 'boolean',
    ];

    /**
     * Relación con los productos que tienen esta regla aplicada.
     */
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_regla_precio');
    }

    /**
     * Scope para filtrar solo reglas que están en fecha vigente.
     */
    public function scopeVigentes($query)
    {
        return $query->where('activo', true)
            ->where(function ($q) {
                $q->whereNull('fecha_inicio')->orWhere('fecha_inicio', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', now());
            });
    }
}
