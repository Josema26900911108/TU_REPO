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
    if (!Schema::hasColumn('tienda', 'fkCentro')) {
        $table->unsignedBigInteger('fkCentro')->nullable();

        $table->foreign('fkCentro')
            ->references('id')
            ->on('centro')
            ->onDelete('set null');
    }
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            Schema::table('tienda', function (Blueprint $table) {
            $table->dropForeign(['fkCentro']); // Eliminar la clave foránea
            $table->dropColumn('fkCentro');    // Eliminar la columna
        });
    }
};
