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
        Schema::create('arbolmaterial', function (Blueprint $table) {
            $table->id();
            if (!Schema::hasColumn('arbolmaterial', 'fkTienda')) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')
            ->references('idTienda')
            ->on('tienda')
            ->onDelete('set null');
            }

            $table->string('nombre');
            $table->string('SKU');
            $table->char('Tipo_servicio',2)->nullable();
            $table->char('Tipo_orden',2)->nullable();
            $table->char('aplicafotografia',2)->nullable();
            $table->string('obs')->nullable();
            $table->unsignedBigInteger('padre_id')->nullable();
            $table->foreign('padre_id')->references('id')->on('arbolmaterial')->onDelete('cascade');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arbolmaterial');
    }
};
