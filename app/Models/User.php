<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @method bool can(string $ability, array|mixed $arguments = [])
 */
class User extends Authenticatable
{

    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $appends = ['idtienda'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'logo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getIdtiendaAttribute()
{
    // Aquí deberías devolver el valor real de idTienda (por ejemplo, desde una relación)
    $relacion = DB::table('usuario_tienda')
        ->where('fkUsuario', $this->id)
        ->where('Estatus', 'EA')
        ->first();

    return $relacion ? $relacion->fkTienda : null;
}

    public function ventas(){
        return $this->hasMany(Venta::class);
    }

    public function users()
    {
        return $this->hasMany(usuariotienda::class, 'fkUsuario', 'id');
    }
}
