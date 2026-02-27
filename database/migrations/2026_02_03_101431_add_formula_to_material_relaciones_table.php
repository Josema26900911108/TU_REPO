<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

        public function up(): void
    {
        Schema::table('material_relaciones', function (Blueprint $table) {
            $table->string('formula', 200)->default('valor-(maximo*usado)')->after('minimo');
    });
}

public function down()
{
    Schema::table('material_relaciones', function (Blueprint $table) {

        $table->dropColumn('formula');
    });
}

};
