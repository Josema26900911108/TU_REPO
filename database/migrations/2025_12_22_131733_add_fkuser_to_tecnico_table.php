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
        Schema::table('tecnico', function (Blueprint $table) {
        $table->unsignedBigInteger('fkuser')->nullable()->after('id');

        $table->foreign('fkuser')
            ->references('id')
            ->on('users');
    });
}

public function down()
{
    Schema::table('tecnico', function (Blueprint $table) {
        $table->dropForeign(['fkuser']);
        $table->dropColumn('fkuser');
    });
}
};
