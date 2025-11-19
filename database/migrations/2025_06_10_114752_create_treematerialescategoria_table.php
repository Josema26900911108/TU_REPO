<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

        public function up(): void
    {
        Schema::create('treematerialescategoria', function (Blueprint $table) {
            $table->id();
            if (!Schema::hasColumn('treematerialescategoria', 'fkTienda')) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')
            ->references('idTienda')
            ->on('tienda')
            ->onDelete('set null');
            }

            $table->string('nombre');
            $table->string('SKU');
            $table->float('limite')->nullable();
            $table->float('minimo')->nullable();
            $table->string('fotografia')->nullable();
            $table->string('obs')->nullable();
            $table->unsignedBigInteger('padre_id')->nullable();
            $table->foreign('padre_id')->references('id')->on('treematerialescategoria')->onDelete('cascade');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treematerialescategoria');
    }
};
