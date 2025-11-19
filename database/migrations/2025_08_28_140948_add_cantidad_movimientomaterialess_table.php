<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
    {
        $tablas = [
            'movimientomateriales',
        ];

        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                    if (!Schema::hasColumn($tabla, 'cantidad')) {
                        $table->float('cantidad')->nullable();
                    }
                });
            }
        }


    }

    public function down(): void
    {
        $tablas = [
            'movimientomateriales',
        ];


        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) {
                    if (Schema::hasColumn($table->getTable(), 'cantidad')) {
                        $table->dropColumn('cantidad');
                    }
                });
            }
        }
    }
};
