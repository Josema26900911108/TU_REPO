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
    if (!Schema::hasColumn('lotesalarma', 'compra_id')) {
        Schema::table('lotesalarma', function (Blueprint $table) {
            $table->unsignedBigInteger('compra_id')->nullable()->after('id');
        });
    }
    if (!Schema::hasColumn('lotesalarma', 'fkTienda')) {
        Schema::table('lotesalarma', function (Blueprint $table) {
            $table->unsignedBigInteger('fkTienda')->nullable()->after('compra_id');
        });
    }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lotesalarma', function (Blueprint $table) {
        $table->dropForeign(['compra_id']);
        $table->dropColumn('compra_id');
        $table->dropForeign(['fkTienda']);
        $table->dropColumn('fkTienda');

        });
    }


};
