<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up(): void
    {
        Schema::table('treematerialescategoria', function (Blueprint $table) {
        $table->char('tipo', 2)->default('MA')->after('minimo');
        $table->char('operacion', 10)->default('MULTIPLO')->after('tipo');
        $table->decimal('valor', 10, 2)->default(1)->after('operacion');
    });
}
    
public function down()
{
    Schema::table('treematerialescategoria', function (Blueprint $table) {

        $table->dropColumn('tipo');
        $table->dropColumn('operacion');
        $table->dropColumn('valor');
    });
}
};
