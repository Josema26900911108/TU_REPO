<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoSAP extends Model
{
    use HasFactory;
        protected $fillable = [
        'numero_documento', 'referencia_sap', 'texto_clase_movimiento_sap',
        'fecha_contabilizacion_sap', 'unidad_medida_base_sap',
        'cantidad_sap', 'clase_movimiento_sap', 'centro_sap'
    ];

    public function movimientos() {
        return $this->hasMany(MovimientoMaterial::class);
    }
        public function tienda() {
        return $this->hasMany(Tienda::class);
    }
}
