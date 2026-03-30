<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CentrosOrganizacion extends Model
{
    use HasFactory;

    protected $table = 'centros_organizacion';
    protected $fillable = ['fkTiendaPrincipal', 'fkTiendaDependiente', 'fkCentro', 'status'];

    // Relación con la Tienda Principal (La que surte/manda)
    public function tiendaPrincipal()
    {
        return $this->belongsTo(Tienda::class, 'fkTiendaPrincipal', 'idTienda');
    }

    // Relación con la Tienda Dependiente (La sucursal/punto de venta)
    public function tiendaDependiente()
    {
        return $this->belongsTo(Tienda::class, 'fkTiendaDependiente', 'idTienda');
    }

    // Relación con el Centro de Costos asociado
    public function centro()
    {
        return $this->belongsTo(Centro::class, 'fkCentro');
    }
}
