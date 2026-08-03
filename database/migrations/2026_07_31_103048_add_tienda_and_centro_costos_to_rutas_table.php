<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Validamos e inyectamos las columnas únicamente si no existen en la tabla
        Schema::table('rutas', function (Blueprint $table) {
            if (!Schema::hasColumn('rutas', 'fkTienda')) {
                $table->unsignedBigInteger('fkTienda')->nullable()->after('id');
            }
            if (!Schema::hasColumn('rutas', 'centro_costos')) {
                $table->string('centro_costos', 50)->nullable()->after('tipo_ciclo');
            }
        });

        // 2. Enlazamos la llave foránea de forma directa e independiente
        Schema::table('rutas', function (Blueprint $table) {
            $table->foreign('fkTienda')
                  ->references('id')
                  ->on('tienda')
                  ->onDelete('cascade');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rutas', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
            $table->dropColumn(['fkTienda', 'centro_costos']);
        });
    }
};
