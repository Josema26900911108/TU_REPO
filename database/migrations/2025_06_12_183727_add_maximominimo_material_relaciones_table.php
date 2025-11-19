<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tablas = [
            'material_relaciones',
        ];

        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                    if (!Schema::hasColumn($tabla, 'minimo')) {
                        $table->float('minimo')->nullable();
                    }
                    if (!Schema::hasColumn($tabla, 'maximo')) {
                        $table->float('maximo')->nullable();
                    }
                });
            }
        }


    }

    public function down(): void
    {
        $tablas = [
            'material_relaciones',
        ];


        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) {
                    if (Schema::hasColumn($table->getTable(), 'minimo')) {
                        $table->dropColumn('minimo');
                    }
                    if (Schema::hasColumn($table->getTable(), 'maximo')) {
                        $table->dropColumn('maximo');
                    }
                });
            }
        }
    }
};
