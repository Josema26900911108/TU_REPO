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
        Schema::table('movimientomateriales', function (Blueprint $table) {
            // Cambiar la columna 'serie' a string (varchar) en lugar de double
            $table->string('serie', 100)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientomateriales', function (Blueprint $table) {
            // Revertir el cambio (volver a double si antes lo era)
            $table->double('serie')->change();
        });
    }
};
