<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_learning_data', function (Blueprint $table) {
            $table->id();
            $table->string('periodo'); // ej. '2026-06'
            $table->string('orden_id');
            $table->string('skufinal_regla')->nullable();
            $table->string('sku_nodo');
            $table->string('depende_sku')->nullable();
            $table->float('usado')->default(0);
            $table->float('valor')->default(0);
            $table->float('valant_final')->default(0);
            $table->string('resultado_real'); // 'aprobada', 'rechazada', 'exceso', 'sin_regla'
            $table->json('contexto')->nullable();
            $table->timestamps();
            
            // Índices para acelerar las consultas de minería
            $table->index(['periodo', 'resultado_real']);
            $table->index(['sku_nodo', 'depende_sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_learning_data');
    }
};