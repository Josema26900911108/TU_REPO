<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientefotograficotecnico', function (Blueprint $table) {
            // Agregamos la columna justo después de 'fotografia'
            $table->longText('overlay_config')->nullable()->after('fotografia');
        });
    }

    public function down(): void
    {
        Schema::table('expedientefotograficotecnico', function (Blueprint $table) {
            $table->dropColumn('overlay_config');
        });
    }
};