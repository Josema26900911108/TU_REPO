<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AprendizajeFamilia extends Model
{
    use HasFactory;

    protected $table = 'aprendizaje_familia';

    public $incrementing = false;

    protected $primaryKey = ['tipo_servicio', 'familia_id'];

    protected $fillable = [
        'tipo_servicio',
        'familia_id',
        'veces_usado'
    ];

    protected $casts = [
        'familia_id' => 'integer',
        'veces_usado' => 'integer'
    ];

    /**
     * Set the keys for a save update query.
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
     * Relación con familia (si existe)
     */
    public function familia()
    {
        return $this->belongsTo(Treematerialescategoria::class, 'familia_id');
    }

    /**
     * Método para crear o actualizar fácilmente
     */
    public static function registrarUso($tipoServicio, $familiaId, $incremento = 1)
    {
        $aprendizaje = self::firstOrNew([
            'tipo_servicio' => $tipoServicio,
            'familia_id' => $familiaId
        ]);

        $aprendizaje->veces_usado = ($aprendizaje->veces_usado ?? 0) + $incremento;
        $aprendizaje->save();

        return $aprendizaje;
    }
}
