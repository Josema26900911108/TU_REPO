<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        $tablas = [
            'tienda',
            'productos',
            'tecnico',
            'users',
        ];

        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                if (Schema::hasColumn($tabla, 'logo')) {
                    DB::statement("ALTER TABLE $tabla MODIFY logo LONGBLOB NULL");
                } else {
                    DB::statement("ALTER TABLE $tabla ADD logo LONGBLOB NULL");
                }
            }
        }
    }

       public function down(): void
    {
        foreach (['tienda', 'productos', 'tecnico', 'users'] as $tabla) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, 'logo')) {
                DB::statement("ALTER TABLE $tabla MODIFY logo LONGTEXT NULL");
            }
        }
    }
};
