<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_learning_data', function (Blueprint $table) {
            $table->string('fkTienda')->nullable()->after('periodo');
            $table->string('centro')->nullable()->after('fkTienda');
            $table->string('tipo_servicio')->nullable()->after('centro');
            
            // Índices para acelerar las búsquedas de la IA
            $table->index(['fkTienda', 'centro', 'skufinal_regla']);
        });
    }

    public function down(): void
    {
        Schema::table('monthly_learning_data', function (Blueprint $table) {
            $table->dropColumn(['fkTienda', 'centro', 'tipo_servicio']);
        });
    }
};