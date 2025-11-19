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
        Schema::create('usuario_tienda', function (Blueprint $table) {
            $table->bigIncrements('idUsuarioTienda');
            $table->unsignedBigInteger('fkUsuario'); // Agregar la columna fkUsuario
            $table->unsignedBigInteger('fkTienda');
            $table->char('Estatus',2)->default('EA');
            $table->timestamp('FechaIngreso')->nullable();
            $table->timestamp('FechaEgreso')->nullable();
            $table->timestamp('FechaBaja')->nullable();
            $table->timestamp('FechaActualizacion')->nullable();
            $table->timestamps();
            $table->foreign('fkUsuario')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('fkTienda')->references('idTienda')->on('tienda')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_tienda');
    }
};
