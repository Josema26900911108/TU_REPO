<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientefotograficotecnico', function (Blueprint $table) {
            $table->id();
            if (!Schema::hasColumn('expedientefotograficotecnico', 'fkTienda')) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')
            ->references('idTienda')
            ->on('tienda')
            ->onDelete('set null');
            }

            $table->string('Orden');
            $table->string('fotografia');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expedientefotograficotecnico');
    }
};
