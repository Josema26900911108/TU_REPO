<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFormulaToComprobantesTable extends Migration
{
    public function up(): void
    {
        // Add 'formula' column to the 'comprobantes' table
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->string('formula')->nullable(); // Agregar el campo 'formula'
        });
    }

    public function down(): void
    {
        // Drop 'formula' column from the 'comprobantes' table
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropColumn('formula'); // Eliminar el campo 'formula'
        });
    }
}
