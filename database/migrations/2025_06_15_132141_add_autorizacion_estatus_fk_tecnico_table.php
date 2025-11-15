<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tablas = [
            'expedientetecnico',
        ];

        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                    if (!Schema::hasColumn($tabla, 'fkTecnico')) {
                        $table->unsignedBigInteger('fkTecnico')->nullable();
                        $table->foreign('fkTecnico')
                            ->references('id')
                            ->on('tecnico')
                            ->onDelete('set null');
                    }
                    $table->char('AUTORIZA',2)->nullable();
                    $table->char('ESTATUS',2)->nullable();
                    $table->string('TECNOLOGIA')->nullable();
                });
            }
        }


    }

    public function down(): void
    {
        $tablas = [
            'expedientetecnico',
        ];


        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) {
                    if (Schema::hasColumn($table->getTable(), 'fkTecnico')) {
                        $table->dropForeign(['fkTecnico']);
                        $table->dropColumn('fkTecnico');
                    }
                });
            }
        }
    }
};
