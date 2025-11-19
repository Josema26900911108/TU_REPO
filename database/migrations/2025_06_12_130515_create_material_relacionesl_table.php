<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::create('material_relaciones', function (Blueprint $table) {
            $table->id();
            if (!Schema::hasColumn('material_relaciones', 'fkTienda')) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')
            ->references('idTienda')
            ->on('tienda')
            ->onDelete('set null');
            }

            $table->string('nombre');
            $table->string('SKU');
            $table->string('depende_SKU');
            $table->enum('tipo_relacion',['requiere','incompatible']);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_relaciones');
    }
};
