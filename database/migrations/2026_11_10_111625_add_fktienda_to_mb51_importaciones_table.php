<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mb51_importaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('fkTienda')->nullable()->after('fk_usuario');
            $table->index('fkTienda');
        });
    }

    public function down(): void
    {
        Schema::table('mb51_importaciones', function (Blueprint $table) {
            $table->dropIndex(['fkTienda']);
            $table->dropColumn('fkTienda');
        });
    }
};