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
        Schema::table('materialmanoobra', function (Blueprint $table) {
            // change() modifica el tipo de dato existente a string (VARCHAR) de forma segura
            $table->string('centrocostoespecifico', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materialmanoobra', function (Blueprint $table) {
            // En caso de rollback, intentaría volver a entero, aunque se recomienda dejarlo así
            $table->bigInteger('centrocostoespecifico')->nullable()->change();
        });
    }
};
