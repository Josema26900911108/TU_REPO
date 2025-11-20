<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add fkTienda to caja table
        Schema::table('caja', function (Blueprint $table) {
            if (!Schema::hasColumn('caja', 'fkTienda')) {
                $table->unsignedBigInteger('fkTienda')->nullable();
                $table->foreign('fkTienda')->references('idTienda')->on('tienda')->onDelete('cascade');
            }
        });

        // Add fkTienda to compra_producto table
        Schema::table('compra_producto', function (Blueprint $table) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')->references('idTienda')->on('tienda')->onDelete('cascade');
        });

        // Add fkTienda to compras table
        Schema::table('compras', function (Blueprint $table) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')->references('idTienda')->on('tienda')->onDelete('cascade');
        });

        // Add fkTienda to arqueocaja table
        Schema::table('ArqueoCaja', function (Blueprint $table) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')->references('idTienda')->on('tienda')->onDelete('cascade');
        });

        // Add fkTienda to permissions table
        Schema::table('permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')->references('idTienda')->on('tienda')->onDelete('cascade');
        });

        // Add fkTienda to producto_venta table
        Schema::table('producto_venta', function (Blueprint $table) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')->references('idTienda')->on('tienda')->onDelete('cascade');
        });

        // Add fkTienda to productos table
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')->references('idTienda')->on('tienda')->onDelete('cascade');
        });

        // Add fkTienda to proveedores table
        Schema::table('proveedores', function (Blueprint $table) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')->references('idTienda')->on('tienda')->onDelete('cascade');
        });

        // Add fkTienda to role_has_permissions table
        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')->references('idTienda')->on('tienda')->onDelete('cascade');
        });

        // Add fkTienda to roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')->references('idTienda')->on('tienda')->onDelete('cascade');
        });

        // Add fkTienda to ventas table
        Schema::table('ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')->references('idTienda')->on('tienda')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key and column from caja table
        Schema::table('caja', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
            $table->dropColumn('fkTienda');
        });


        // Drop foreign key and column from compra_producto table
        Schema::table('compra_producto', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
            $table->dropColumn('fkTienda');
        });

        // Drop foreign key and column from compras table
        Schema::table('compras', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
            $table->dropColumn('fkTienda');
        });

        // Drop foreign key and column from arqueocaja table
        Schema::table('ArqueoCaja', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
            $table->dropColumn('fkTienda');
        });

        // Drop foreign key and column from permissions table
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
            $table->dropColumn('fkTienda');
        });

        // Drop foreign key and column from producto_venta table
        Schema::table('producto_venta', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
            $table->dropColumn('fkTienda');
        });

        // Drop foreign key and column from productos table
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
            $table->dropColumn('fkTienda');
        });

        // Drop foreign key and column from proveedores table
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
            $table->dropColumn('fkTienda');
        });

        // Drop foreign key and column from role_has_permissions table
        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
            $table->dropColumn('fkTienda');
        });

        // Drop foreign key and column from roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
            $table->dropColumn('fkTienda');
        });

        // Drop foreign key and column from ventas table
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
            $table->dropColumn('fkTienda');
        });
    }
};
