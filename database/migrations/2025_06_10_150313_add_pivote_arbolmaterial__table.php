<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tablas = [
            'arbolmaterial',
        ];

        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                    if (!Schema::hasColumn($tabla, 'idpivote')) {
                        $table->unsignedBigInteger('idpivote')->nullable();
                        $table->foreign('idpivote')
                            ->references('id')
                            ->on('arbolmanoobra')
                            ->onDelete('set null');
                    }
                });
            }
        }


    }

    public function down(): void
    {
        $tablas = [
            'arbolmaterial',
        ];


        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) {
                    if (Schema::hasColumn($table->getTable(), 'idpivote')) {
                        $table->dropForeign(['idpivote']);
                        $table->dropColumn('idpivote');
                    }
                });
            }
        }
    }
};
