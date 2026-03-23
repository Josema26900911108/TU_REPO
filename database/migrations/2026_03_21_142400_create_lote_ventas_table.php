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
        Schema::create('lote_ventas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('venta_id')->constrained('ventas');
    $table->foreignId('lote_id')->constrained('lotesalarma');
    $table->integer('cantidad'); // Cantidad descontada de ESTE lote
    $table->foreignId('producto_id')->constrained('productos');
    $table->char('status', 1)->default('I'); // A: Anulado, C: Contable, I: Inicial
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lote_ventas');
        Schema::table('lote_ventas', function (Blueprint $table) {
            $table->dropForeign(['venta_id']);
            $table->dropForeign(['lote_id']);
        });
    }
};
