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
        Schema::table('compra_producto', function (Blueprint $table) {
            $table->char('Naturaleza',1)->nullable();
            $table->char('Estado',1)->nullable();
            $table->decimal('impuesto',12,2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compra_producto', function (Blueprint $table) {
            $table->char('Naturaleza',1)->nullable();
            $table->char('Estado',1)->nullable();
            $table->decimal('impuesto',12,2);
        });
    }

};
