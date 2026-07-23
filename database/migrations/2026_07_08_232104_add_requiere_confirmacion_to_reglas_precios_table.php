<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('reglas_precios', function (Blueprint $table) {
            // true = pregunta antes de aplicar, false = aplica directamente en automático
            $table->boolean('requiere_confirmacion')->default(false)->after('prioritaria');
        });
    }

    public function down(): void {
        Schema::table('reglas_precios', function (Blueprint $table) {
            $table->dropColumn('requiere_confirmacion');
        });
    }
};
