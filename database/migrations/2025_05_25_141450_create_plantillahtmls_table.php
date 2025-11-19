<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantillahtml', function (Blueprint $table) {
            $table->id();
            $table->string('Titulo');
            $table->longText('plantillahtml');
            $table->text('descripcion');

            // Add the foreign key column first
            $table->unsignedBigInteger('fkTienda'); // Add this line

            // Then create the foreign key constraint
            $table->foreign('fkTienda')
                  ->references('idTienda')
                  ->on('tienda')
                  ->onDelete('cascade'); // Added cascade deletion

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('plantillahtml', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['fkTienda']);
            // Then drop the column
            $table->dropColumn('fkTienda');
        });

        Schema::dropIfExists('plantillahtml');
    }
};
