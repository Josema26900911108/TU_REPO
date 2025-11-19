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
        Schema::table('detalle_comprobantes', function (Blueprint $table) {
            $table->char('Naturaleza',1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('detalle_comprobantes', function (Blueprint $table) {
            $table->char('Naturaleza')->nullable();
        });
    }
};
