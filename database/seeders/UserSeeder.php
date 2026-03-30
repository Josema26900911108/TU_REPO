<?php

namespace Database\Seeders;

use App\Models\Tienda;
use App\Models\User;
use App\Models\usuariotienda;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    $user = User::updateOrCreate(
        ['email' => 'josema.alvarezgarcia@gmail.com'], // Criterio de búsqueda
        [
            'name' => 'root',
            'password' => bcrypt('12345678')
        ]
    );

        //Usuario administrador
        $rol = Role::updateOrCreate(['name' => 'administrador']);
        $permisos = Permission::pluck('id','id')->all();
        $rol->syncPermissions($permisos);
        //$user = User::find(1);
        $user->assignRole('administrador');

            $store = Tienda::updateOrCreate([
            'Nombre' => 'root',
            'Direccion' => 'No aplica',
            'EstatusContable'=>'A',
            'Telefono'=>'59202023',
            'departamento' => 'No aplica',
            'municipio' => 'No aplica',
            'representante' => 'No aplica',
            'nit' => '123456789'
        ]);

           $userstore = usuariotienda::updateOrCreate([
            'fkUsuario' => $user->id,
            'fkTienda' => $store->idTienda,
            'Estatus' => 'ER'
        ]);


    }
}
