<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('Cash_registers', function (Blueprint $table) {
            $table->id();
            $table->string('Nombre',45);
            $table->bigInteger('fkTienda');
            $table->char('Estatus',1);
            $table->decimal('initial_amount', 8, 2);
            $table->decimal('closing_amount', 8, 2)->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Cash_registers');
    }
};
