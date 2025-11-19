<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

     public function up(): void
    {
            Schema::table('plantillahtml', function (Blueprint $table) {
            $table->longText('cabecera');
            $table->longText('detalle');
            $table->longText('pie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            Schema::table('plantillahtml', function (Blueprint $table) {
            $table->longText('cabecera');
            $table->longText('detalle');
            $table->longText('pie');
        });
    }
};
