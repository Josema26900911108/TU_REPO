<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientefotograficotecnico', function (Blueprint $table) {
            // Guardará coordenadas, hora, observaciones y config de overlays
            $table->json('meta_data')->nullable()->after('fotografia'); 
        });
    }

    public function down(): void
    {
        Schema::table('expedientefotograficotecnico', function (Blueprint $table) {
            $table->dropColumn('meta_data');
        });
    }
};