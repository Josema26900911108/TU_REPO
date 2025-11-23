<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('comprobantes', function (Blueprint $table) {
        $table->unsignedBigInteger('fkPlantillaHtml')->nullable()->after('id');

        $table->foreign('fkPlantillaHtml')
            ->references('id')
            ->on('plantillahtml')
            ->onDelete('set null');
    });
}

public function down()
{
    Schema::table('comprobantes', function (Blueprint $table) {
        $table->dropForeign(['fkPlantillaHtml']);
        $table->dropColumn('fkPlantillaHtml');
    });
}

};
