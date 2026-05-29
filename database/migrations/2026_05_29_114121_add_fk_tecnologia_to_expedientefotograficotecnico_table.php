<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientefotograficotecnico', function (Blueprint $table) {
            // Se agrega como BigInteger Unsigned para coincidir con PK de arbolmanoobra/tecnologías
            $table->bigInteger('fkTecnologia')->unsigned()->nullable()->after('Orden');
            
            // Opcional: Índice para acelerar las consultas masivas por tecnología
            $table->index('fkTecnologia');
        });
    }

    public function down(): void
    {
        Schema::table('expedientefotograficotecnico', function (Blueprint $table) {
            $table->dropColumn('fkTecnologia');
        });
    }
};
