<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AprendizajeOrden extends Model
{
    use HasFactory;

    protected $table = 'aprendizaje_ordenes';

    // No es necesario $incrementing = false porque la clave primaria es string
    public $incrementing = false;

    // Especificar el tipo de clave primaria
    protected $keyType = 'string';

    // La clave primaria es 'tipo_servicio'
    protected $primaryKey = 'tipo_servicio';

    protected $fillable = [
        'tipo_servicio',
        'total_ordenes'
    ];

    protected $casts = [
        'total_ordenes' => 'integer'
    ];

    /**
     * Método helper para registrar una orden
     */
    public static function registrarOrden($tipoServicio, $cantidad = 1)
    {
        $orden = self::firstOrNew([
            'tipo_servicio' => $tipoServicio
        ]);

        $orden->total_ordenes = ($orden->total_ordenes ?? 0) + $cantidad;
        $orden->save();

        return $orden;
    }

    /**
     * Método para incrementar directamente
     */
    public static function incrementarOrden($tipoServicio, $cantidad = 1)
    {
        return self::updateOrCreate(
            ['tipo_servicio' => $tipoServicio],
            //['total_ordenes' => \DB::raw("total_ordenes + $cantidad")]
        );
    }

    /**
     * Scope para tipos de servicio específicos
     */
    public function scopeTipo($query, $tipoServicio)
    {
        return $query->where('tipo_servicio', $tipoServicio);
    }

    /**
     * Scope para ordenar por total de órdenes
     */
    public function scopeMasUsados($query, $limit = 10)
    {
        return $query->orderBy('total_ordenes', 'desc')->limit($limit);
    }

    /**
     * Obtener el porcentaje de uso de este tipo de servicio
     */
    public function porcentajeUso()
    {
        $totalGeneral = self::sum('total_ordenes');

        if ($totalGeneral == 0) {
            return 0;
        }

        return ($this->total_ordenes / $totalGeneral) * 100;
    }
}
