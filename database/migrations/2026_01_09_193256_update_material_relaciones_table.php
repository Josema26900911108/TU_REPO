<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
public function up(): void
    {
        // Método 1: Modificar directamente la columna ENUM
        DB::statement("ALTER TABLE material_relaciones MODIFY COLUMN tipo_relacion ENUM('requiere', 'incompatible', 'calculo', '') NOT NULL DEFAULT ''");

    }
  public function down(): void
    {
        // Revertir a los valores originales
        DB::statement("ALTER TABLE material_relaciones MODIFY COLUMN tipo_relacion ENUM('requiere', 'incompatible', '') NOT NULL DEFAULT ''");

        // Opcional: Actualizar registros que tengan 'calculo' a otro valor
        DB::table('material_relaciones')
            ->where('tipo_relacion', 'calculo')
            ->update(['tipo_relacion' => '']);
    }
};
