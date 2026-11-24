<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Mb51Importacion extends Model
{
    protected $table = 'mb51_importaciones';

    protected $fillable = [
        'material', 'texto_breve_material', 'referencia', 'fe_contabilizacion',
        'cantidad', 'nombre_usuario', 'clase_movimiento', 'texto_clase_movimiento',
        'documento_material', 'pedido', 'texto_cab_documento', 'num_reserva',
        'grafo', 'importe_ml', 'centro', 'nombre_1', 'almacen', 'sociedad',
        'fecha_entrada', 'hora_entrada', 'unique_key', 'fk_usuario', 'fkTienda',
    ];

    protected $casts = [
        'cantidad'           => 'decimal:4',
        'importe_ml'         => 'decimal:4',
        'fe_contabilizacion' => 'date',
        'fecha_entrada'      => 'date',
    ];

    /**
     * Genera la llave única a partir de los 6 campos determinantes.
     * Normaliza para que "  ABC " === "ABC" y "10.00" === "10".
     */
    public static function buildUniqueKey(array $data): string
    {
        $normalizar = function ($valor) {
            $valor = trim((string) ($valor ?? ''));
            // Normaliza decimales: "10.00" -> "10", "10,50" -> "10.5"
            if (is_numeric(str_replace(',', '.', $valor))) {
                return (string) (float) str_replace(',', '.', $valor);
            }
            return mb_strtoupper($valor, 'UTF-8');
        };

        $partes = [
            $normalizar($data['material'] ?? ''),
            $normalizar($data['referencia'] ?? ''),
            $normalizar($data['cantidad'] ?? ''),
            $normalizar($data['clase_movimiento'] ?? ''),
            $normalizar($data['texto_clase_movimiento'] ?? ''),
            $normalizar($data['centro'] ?? ''),
        ];

        return md5(implode('|', $partes));
    }

    public static function generarUniqueKey(array $data): string
    {
        return self::buildUniqueKey($data);
    }
}