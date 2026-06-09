<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Solo intenta crearla si por alguna razón no existiera
        if (!Schema::hasColumn('MaterialManoObra', 'centrocostoespecifico')) {
            Schema::table('MaterialManoObra', function (Blueprint $table) {
                $table->string('centrocostoespecifico', 255)->nullable()->after('fkTienda');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('MaterialManoObra', 'centrocostoespecifico')) {
            Schema::table('MaterialManoObra', function (Blueprint $table) {
                $table->dropColumn('centrocostoespecifico');
            });
        }
    }
};
