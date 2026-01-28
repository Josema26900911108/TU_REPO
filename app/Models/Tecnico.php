<?php

namespace App\Models;

use App\Models\Persona;
use App\Models\Tienda;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tecnico extends Model
{
    use HasFactory;

    protected $table = 'tecnico';

    protected $fillable = [
        'fkTienda',
        'fkuser',
        'nombre',
        'codigo',
        'especialidad',
        'logo',
        'fkpersona'
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'fkPersona');
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'fkTienda', 'idTienda');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'fkuser');
    }
}
