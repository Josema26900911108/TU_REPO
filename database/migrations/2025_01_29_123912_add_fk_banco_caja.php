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
        Schema::table('caja', function (Blueprint $table) {
            $table->unsignedBigInteger(column: 'fkBanco');
            $table->unsignedBigInteger(column: 'fkOtro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caja', function (Blueprint $table) {
            $table->dropForeign(['fkBanco']); // Eliminar la clave foránea
            $table->dropForeign(['fkOtro']); // Eliminar la clave foránea
        });
    }
};
