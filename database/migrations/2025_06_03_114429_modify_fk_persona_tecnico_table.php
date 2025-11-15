<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
    {
        $tablas = [
            'tecnico',
        ];

        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                    if (!Schema::hasColumn($tabla, 'fkpersona')) {
                        $table->unsignedBigInteger('fkpersona')->nullable(true);



            $table->foreign('fkpersona')
                  ->references('id')
                  ->on('personas')
                  ->onDelete('cascade'); // Added cascade deletion
                    }
                });
            }
        }
    }

    public function down(): void
    {
        $tablas = [
            'tecnico',
        ];

        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) {
                    if (Schema::hasColumn($table->getTable(), 'fkpersona')) {
                        $table->unsignedBigInteger('fkpersona');
                    }
                });
            }
        }
    }
};
