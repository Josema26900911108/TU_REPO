<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_proposals', function (Blueprint $table) {
            $table->id();
            $table->string('tipo'); // 'nueva_regla', 'ajuste_maximo', 'ajuste_minimo', 'ajuste_formula'
            $table->string('skufinal_propuesto')->nullable();
            $table->string('sku_nodo')->nullable();
            $table->string('depende_sku')->nullable();
            $table->json('datos_sugeridos')->nullable(); // {"nuevo_maximo": 3, "frecuencia": 150}
            $table->text('justificacion');
            $table->string('estado')->default('pendiente'); // pendiente, aprobada, rechazada
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_proposals');
    }
};