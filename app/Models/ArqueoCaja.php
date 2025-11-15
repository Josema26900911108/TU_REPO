<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArqueoCaja extends Model
{
    use HasFactory;
    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'fkTienda', 'idTienda');
    }
    public function cashRegisters()
    {
        return $this->belongsTo(Tienda::class, 'fkCaja', 'id');
    }
    protected $table = 'arqueocaja'; // Cambia 'tienda' por el nombre correcto de tu tabla
    protected $primaryKey = 'idArqueoCaja'; // Especifica la clave primaria
    public $incrementing = true; // Si es autoincremental
    protected $keyType = 'int'; // Tipo de la clave primaria
    protected $fillable = ['idArqueoCaja','CEF', 'VD', 'VO','D','CC','OG','CEI','ChCo','vales','created_at', 'update_at','fkTienda','fkCaja','Estatus'];
}
