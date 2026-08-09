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
            // Se añade la columna para la firma del representante legal en Base64
            $table->text('firma_representante')->nullable()->after('nit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tienda', function (Blueprint $table) {
            $table->dropColumn('firma_representante');
        });
    }
};
