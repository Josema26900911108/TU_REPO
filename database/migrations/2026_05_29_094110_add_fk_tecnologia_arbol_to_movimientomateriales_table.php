<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientomateriales', function (Blueprint $table) {
            // Se agrega como BigInteger Unsigned para que coincida con la PK de arbolmaterial
            $table->bigInteger('fkTecnologiaarbol')->unsigned()->nullable()->after('fkExpediente');
            
            // Opcional: Si deseas amarrar la integridad con una llave foránea física
            // $table->foreign('fkTecnologiaarbol')->references('id')->on('arbolmaterial')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('movimientomateriales', function (Blueprint $table) {
            // Si deseas remover la llave foránea física en el rollback
            // $table->dropForeign(['fkTecnologiaarbol']);
            
            $table->dropColumn('fkTecnologiaarbol');
        });
    }
};
