<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('documentdesigns', function (Blueprint $table) {
            $table->id();

            $table->string('nombre'); // Ticket 80mm, Carta, etc.

            // Medidas en puntos PDF
            $table->integer('ancho_pt');
            $table->integer('alto_pt'); // 0 = altura variable (para tickets)

            // Medidas en mm (referencia)
            $table->decimal('ancho_mm', 8, 2)->nullable();
            $table->decimal('alto_mm', 8, 2)->nullable();

            // Tipo de documento
            $table->enum('tipo', [
                'ticket',
                'documento',
                'label',
                'custom',
                'carnet',
            ]);

            // Vertical (true) u horizontal (false)
            $table->boolean('orientacion_vertical')->default(true);

            // Configuración JSON para edición personalizada
            $table->json('config')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('documentdesigns');
    }
};
