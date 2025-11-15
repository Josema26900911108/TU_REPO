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
                    if (!Schema::hasColumn($tabla, 'idtree')) {
                        $table->unsignedBigInteger('idtree')->nullable();
                        $table->foreign('idtree')
                            ->references('id')
                            ->on('treematerialescategoria')
                            ->onDelete('set null');
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
                    if (Schema::hasColumn($table->getTable(), 'idtree')) {
                        $table->dropForeign(['idtree']);
                        $table->dropColumn('idtree');
                    }
                });
            }
        }
    }
};
