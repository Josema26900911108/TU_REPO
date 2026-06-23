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
        Schema::table('expedientetecnico', function (Blueprint $table) {
            // Creamos el campo para la ruta de la firma, almacena texto y permite nulos
            $table->string('firma_cliente')->nullable()->after('TECNOLOGIA');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expedientetecnico', function (Blueprint $table) {
            // Regla de retorno: Eliminamos el campo si se hace un rollback
            $table->dropColumn('firma_cliente');
        });
    }
};
