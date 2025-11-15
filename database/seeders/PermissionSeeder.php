<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permisos = [

            //categorías
            'panel-categoria',
            'ver-categoria',
            'crear-categoria',
            'editar-categoria',
            'eliminar-categoria',

            //Cliente
            'ver-cliente',
            'panel-cliente',
            'crear-cliente',
            'editar-cliente',
            'eliminar-cliente',

            //Compra
            'ver-compra',
            'panel-compra',
            'crear-compra',
            'mostrar-compra',
            'eliminar-compra',

            //Marca
            'panel-marca',
            'ver-marca',
            'crear-marca',
            'editar-marca',
            'eliminar-marca',

            //Presentacione
            'panel-presentacione',
            'ver-presentacione',
            'crear-presentacione',
            'editar-presentacione',
            'eliminar-presentacione',

            //Producto
            'panel-producto',
            'ver-producto',
            'crear-producto',
            'editar-producto',
            'eliminar-producto',
            'vertienda-producto',

            //Proveedore
            'panel-proveedore',
            'ver-proveedore',
            'crear-proveedore',
            'editar-proveedore',
            'eliminar-proveedore',

            //Venta
            'ver-venta',
            'crear-venta',
            'mostrar-venta',
            'eliminar-venta',

            //Roles
            'ver-role',
            'crear-role',
            'editar-role',
            'eliminar-role',

            //Comprobante
            'ver-comprobante',
            'crear-comprobante',
            'editar-comprobante',
            'eliminar-comprobante',

            //User
            'panel-user',
            'ver-user',
            'crear-user',
            'editar-user',
            'eliminar-user',

            //Perfil
            'ver-perfil',
            'editar-perfil',
            //Caja
            'ingresar-caja',
            'panel-caja',
            'panel-caja-otro',
            'panel-caja-banco',
            'panel-caja-comprar',
            'panel-caja-venta',
            'aperturar-caja',
            'ver-caja',
            'editar-caja',
            'eliminar-caja',
            'crear-caja',
            'caja-cobrar-venta',
            'caja-anular-venta',
            'caja-ver-venta',
            //Permisos
            'ver-permiso',
            'crear-permiso',
            'editar-permiso',
            'eliminar-permiso',
            //Tienda
            'ver-tienda',
            'crear-tienda',
            'editar-tienda',
            'eliminar-tienda',
            //Usuario por Tienda
            'ver-usuariotienda',
            'editar-usuariotienda',
            'crear-usuariotienda',
            'eliminar-usuariotienda',

            //APP TECNICA mano de obra
            'ver-materialmanoobra',
            'editar-materialmanoobra',
            'crear-materialmanoobra',
            'eliminar-materialmanoobra',

            //APP ETA
            'ver-eta',
            'editar-eta',
            'crear-eta',
            'crear-etamaterial',
            'crear-etamaterial',
            'eliminar-eta',
            //documentos sap
            'ver-documentosap',
            'editar-documentosap',
            'crear-documentosap',
            'eliminar-documentosap',
            //movimientos materiales
            'ver-movimientomateriales',
            'editar-movimientomateriales',
            'crear-movimientomateriales',
            'eliminar-movimientomateriales',

            //tecnico
            'ver-tecnico',
            'editar-tecnico',
            'buckets-tecnicos',
            'ruta-tecnico',
            'ordenruta-tecnico',
            'bucket-tecnico',
            'crear-tecnico',
            'eliminar-tecnico',

            //contrata
            'ver-contrata',
            'editar-contrata',
            'crear-contrata',
            'eliminar-contrata',

            //arbolmaterial
            'ver-arbolmaterial',
            'editar-arbolmaterial',
            'crear-arbolmaterial',
            'eliminar-arbolmaterial',

            //arbrmanoobra
            'ver-arbrmanoobra',
            'editar-arbrmanoobra',
            'crear-arbrmanoobra',
            'eliminar-arbrmanoobra',

            //treematerialescategoria
            'ver-treematerialescategoria',
            'editar-treematerialescategoria',
            'crear-treematerialescategoria',
            'eliminar-treematerialescategoria',

            //pagotecnico
            'ver-pagotecnico',
            'editar-pagotecnico',
            'crear-pagotecnico',
            'eliminar-pagotecnico',
            'ver-cobrotecnico',
            'ver-pagocobrotecnico',

        ];

            foreach ($permisos as $perm) {

            Permission::updateOrCreate(
                ['name' => $perm],
                ['guard_name' => 'web'] // asegúrate que coincida con tu guard
            );
        }

        $this->command->info('Permisos actualizados correctamente.');

    }

}
