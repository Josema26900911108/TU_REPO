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
        Schema::table('detalle_comprobantes', function (Blueprint $table) {
            // Creamos la columna como string de longitud corta (ej: 5 caracteres) y nullable
            $table->string('tipo_arqueo', 5)->nullable()->after('Naturaleza'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalle_comprobantes', function (Blueprint $table) {
            $table->dropColumn('tipo_arqueo');
        });
    }
};
