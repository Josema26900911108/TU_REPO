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

        Schema::table('ruta_dia_cliente', function (Blueprint $table) {
            if (!Schema::hasColumn('ruta_dia_cliente', 'fkTienda')) {
                $table->unsignedBigInteger('fkTienda')->nullable()->after('cliente_id');
            }
            if (!Schema::hasColumn('ruta_dia_cliente', 'centro_costos')) {
                $table->string('centro_costos', 50)->nullable()->after('orden');
            }
        });

        Schema::table('ruta_dia_cliente', function (Blueprint $table) {
            $table->foreign('fkTienda')
                  ->references('idTienda')
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
        Schema::table('ruta_dia_cliente', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
            $table->dropColumn(['fkTienda', 'centro_costos']);
        });
    }
};
