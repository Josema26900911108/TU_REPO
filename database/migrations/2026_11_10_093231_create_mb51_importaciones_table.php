<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mb51_importaciones', function (Blueprint $table) {
            $table->id();

            // Campos determinantes de duplicado
            $table->string('material', 100);
            $table->string('referencia', 100)->nullable();
            $table->decimal('cantidad', 18, 4)->default(0);
            $table->string('clase_movimiento', 20);
            $table->string('texto_clase_movimiento', 150)->nullable();
            $table->string('centro', 20);

            // Resto de campos del CSV
            $table->string('texto_breve_material', 255)->nullable();
            $table->date('fe_contabilizacion')->nullable();
            $table->string('nombre_usuario', 150)->nullable();
            $table->string('documento_material', 50)->nullable();
            $table->string('pedido', 50)->nullable();
            $table->string('texto_cab_documento', 255)->nullable();
            $table->string('num_reserva', 50)->nullable();
            $table->string('grafo', 50)->nullable();
            $table->decimal('importe_ml', 18, 4)->nullable();
            $table->string('nombre_1', 150)->nullable();
            $table->string('almacen', 20)->nullable();
            $table->string('sociedad', 20)->nullable();
            $table->date('fecha_entrada')->nullable();
            $table->string('hora_entrada', 20)->nullable();

            // Llave única de negocio (evita duplicados en DB)
            $table->string('unique_key', 32)->unique();

            $table->unsignedBigInteger('fk_usuario')->nullable();
            $table->timestamps();

            $table->index(['material', 'referencia', 'centro'], 'idx_mb51_busqueda');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mb51_importaciones');
    }
};