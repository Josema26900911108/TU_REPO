<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('plantillahtml', function (Blueprint $table) {
        $table->unsignedBigInteger('fkDesignDocument')->nullable()->after('id');

        $table->foreign('fkDesignDocument')
            ->references('id')
            ->on('documentdesigns')
            ->onDelete('set null');
    });
}

public function down()
{
    Schema::table('plantillahtml', function (Blueprint $table) {
        $table->dropForeign(['fkDesignDocument']);
        $table->dropColumn('fkDesignDocument');
    });
}

};
