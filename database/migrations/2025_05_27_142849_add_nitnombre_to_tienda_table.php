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
            Schema::table('tienda', function (Blueprint $table) {
            $table->string('departamento');
            $table->string('municipio');
            $table->string('representante');
            $table->string('nit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            Schema::table('tienda', function (Blueprint $table) {
$table->string('departamento');
            $table->string('municipio');
            $table->string('representante');
            $table->string('nit');
        });
    }
};
