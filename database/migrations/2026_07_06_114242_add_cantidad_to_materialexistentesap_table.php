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
        Schema::table('materialexistentesap', function (Blueprint $table) {
            $table->double('cantidad', 8, 2)->default(0.00)->after('COSTO');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materialexistentesap', function (Blueprint $table) {
            $table->dropColumn('cantidad');
        });
    }
};
