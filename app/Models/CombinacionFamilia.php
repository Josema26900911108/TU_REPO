<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CombinacionFamilia extends Model
{
    use HasFactory;

    protected $table = 'combinacion_familia';

    // Desactivar incremento automático (clave primaria compuesta)
    public $incrementing = false;

    // Especificar las columnas de la clave primaria compuesta
    protected $primaryKey = ['tipo_servicio', 'familia_a', 'familia_b'];

    protected $fillable = [
        'tipo_servicio',
        'familia_a',
        'familia_b',
        'veces_juntos'
    ];

    protected $casts = [
        'familia_a' => 'integer',
        'familia_b' => 'integer',
        'veces_juntos' => 'integer'
    ];

    /**
     * Set the keys for a save update query.
     * Esto es necesario para claves primarias compuestas
     */
    protected function setKeysForSaveQuery($query)
    {
        $keys = $this->getKeyName();
        if (!is_array($keys)) {
            return parent::setKeysForSaveQuery($query);
        }

        foreach ($keys as $keyName) {
            $query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
        }

        return $query;
    }

    /**
     * Get the primary key value for a save query.
     */
    protected function getKeyForSaveQuery($keyName = null)
    {
        if (is_null($keyName)) {
            $keyName = $this->getKeyName();
        }

        if (isset($this->original[$keyName])) {
            return $this->original[$keyName];
        }

        return $this->getAttribute($keyName);
    }

    /**
     * Obtener las familias A (si existe relación)
     */
    public function familiaA()
    {
        return $this->belongsTo(Treematerialescategoria::class, 'familia_a');
    }

    /**
     * Obtener las familias B (si existe relación)
     */
    public function familiaB()
    {
        return $this->belongsTo(Treematerialescategoria::class, 'familia_b');
    }

    /**
     * Scope para buscar combinaciones específicas
     */
    public function scopeCombinacion($query, $tipoServicio, $familiaA, $familiaB)
    {
        return $query->where('tipo_servicio', $tipoServicio)
            ->where('familia_a', $familiaA)
            ->where('familia_b', $familiaB);
    }

    /**
     * Scope para buscar combinaciones de una familia específica
     */
    public function scopeDeFamilia($query, $familiaId)
    {
        return $query->where('familia_a', $familiaId)
            ->orWhere('familia_b', $familiaId);
    }

    /**
     * Método helper para crear o actualizar combinaciones
     * Asegura que las familias estén en orden consistente
     */
    public static function crearOActualizar($tipoServicio, $familia1, $familia2, $incremento = 1)
    {
        // Ordenar familias para consistencia (familia_a siempre la menor)
        $familiaA = min($familia1, $familia2);
        $familiaB = max($familia1, $familia2);

        $combinacion = self::firstOrNew([
            'tipo_servicio' => $tipoServicio,
            'familia_a' => $familiaA,
            'familia_b' => $familiaB
        ]);

        $combinacion->veces_juntos = ($combinacion->veces_juntos ?? 0) + $incremento;
        $combinacion->save();

        return $combinacion;
    }

    /**
     * Obtener la otra familia en la combinación
     */
    public function otraFamilia($familiaId)
    {
        if ($this->familia_a == $familiaId) {
            return $this->familia_b;
        } elseif ($this->familia_b == $familiaId) {
            return $this->familia_a;
        }

        return null;
    }
}
