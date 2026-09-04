<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenTrabajo extends Model
{
    // Definimos el nombre real de tu tabla
    protected $table = 'ordenes_trabajo';

    // 🚀 CRUCIAL: Indicamos que el ID no es autoincremental porque viene del archivo
    public $incrementing = false;
    protected $keyType = 'string'; // Por seguridad, si el ID es muy largo o alfanumérico

    protected $fillable = [
        'id',
        'Orden_Trabajo',
        'Descripcion',
        'Id_Contratista',
        'Id_Servicio',
        'Cantidad',
        'Centro_Mano_Obra',
        'COSTO',
        'SUBTOTAL',
        'TECNO'
    ];
}
