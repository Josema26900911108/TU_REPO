<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_learning_data', function (Blueprint $table) {
            // Agregamos el resultado que arrojó el sistema
            $table->string('resultado_sistema')->nullable(); // ej. 'requiere', 'requiere - Exceso'
            // Agregamos lo que el humano decidió
            $table->string('feedback_humano')->nullable(); // 'confirmado', 'ajustado', 'borrado'
            $table->text('notas_ajuste')->nullable(); 
        });
    }

    public function down(): void
    {
        Schema::table('monthly_learning_data', function (Blueprint $table) {
            $table->dropColumn(['resultado_sistema', 'feedback_humano', 'notas_ajuste']);
        });
    }
};