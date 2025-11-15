<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('eta', function (Blueprint $table) {
            $table->string('Orden', 50)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
            $table->string('SKU', 50)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
            $table->integer('Cantidad')->change();
            $table->string('Serie', 100)->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
            $table->text('Descripcion')->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
            $table->string('MAC1', 100)->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
            $table->string('MAC2', 100)->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
            $table->string('MAC3', 100)->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
            $table->string('TIPO_DE_SERVICIO', 100)->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
            $table->string('TIPO_DE_ORDEN', 100)->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
            $table->string('CENTRO', 50)->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
            $table->string('EMPLEADO', 100)->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
