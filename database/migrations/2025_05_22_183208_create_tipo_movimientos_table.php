<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('tipos_movimiento', function (Blueprint $table) {
        $table->id();
        $table->string('codigo')->unique();
        $table->string('abreviatura');
        $table->string('descripcion');
        $table->timestamps();
    });

    // Insertar datos después de que la tabla ya fue creada
    DB::table('tipos_movimiento')->insert([
        ['codigo' => '101', 'abreviatura' => 'EM', 'descripcion' => 'Stock en tránsito'],
        ['codigo' => '201', 'abreviatura' => 'SM', 'descripcion' => 'SM para centro coste'],
        ['codigo' => '221', 'abreviatura' => 'SM', 'descripcion' => 'SM para proyecto'],
        ['codigo' => '231', 'abreviatura' => 'TM', 'descripcion' => 'Stock en tránsito con técnico'],
        ['codigo' => '251', 'abreviatura' => 'SM', 'descripcion' => 'SM para ventas'],
        ['codigo' => '311', 'abreviatura' => 'TR', 'descripcion' => 'Traslado en ce.'],
        ['codigo' => '561', 'abreviatura' => 'EM', 'descripcion' => 'Entr.inicial stocks'],
        ['codigo' => '641', 'abreviatura' => 'TR', 'descripcion' => 'TR A stock tránsito'],
        ['codigo' => '642', 'abreviatura' => 'TR', 'descripcion' => 'TR A stock tránsito'],
        ['codigo' => '905', 'abreviatura' => 'SM', 'descripcion' => 'SM Ajuste Inv.CIA'],
        ['codigo' => '906', 'abreviatura' => 'SM', 'descripcion' => 'Anul. SM Ajuste CIA'],
    ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_movimiento');
    }
};
