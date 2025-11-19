<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientetecnico', function (Blueprint $table) {
            $table->id();
            if (!Schema::hasColumn('expedientetecnico', 'fkTienda')) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')
            ->references('idTienda')
            ->on('tienda')
            ->onDelete('set null');
            }

            $table->string('Orden');
            $table->string('virtual');
            $table->char('Status',1);
            $table->char('Tipo_servicio',2)->nullable();
            $table->char('Tipo_orden',2)->nullable();
            $table->string('NOMBRECLIENTE')->nullable();
            $table->string('DIRECCION')->nullable();
            $table->string('OBS')->nullable();
            $table->string('SIGLASCENTRAL')->nullable();
            $table->string('AREA')->nullable();
            $table->dateTime('FECHAINSTALACION')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expedientetecnico');
    }
};
