<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Arbmanoobra;
use App\Models\Tienda;

class Pagotecnico extends Model
{
    protected $table = 'pagotecnico'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'id'; // Especifica la clave primaria

    public $incrementing = true; // Si es autoincremental

    public function arbolmanoobra()
    {
        return $this->belongsTo(Arbmanoobra::class, 'SKU', 'SKU')
        //->where('activo', 1)
                    ->withDefault(); // Para evitar null si no existe relación
    }
    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'fkTienda'); 
    }
    
    protected $fillable = ['id','Orden','SKU','Descripcion','OBS','Cantidad','COSTOPAGO','created_at','updated_at', 'fkTienda', 'fkTecnico','Naturaleza','Status'];

    public $timestamps = true;

}
