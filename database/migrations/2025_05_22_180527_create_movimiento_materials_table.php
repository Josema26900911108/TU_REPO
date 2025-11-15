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
Schema::create('movimiento_materiales', function (Blueprint $table) {
    $table->id();
    $table->foreignId('fkMateriales')->constrained('productos');
    $table->foreignId('fkLotes')->nullable()->constrained('lotes');

    $table->string('centro');
    $table->string('contrata')->nullable();
    $table->string('almacen');

    $table->string('clase_movimiento'); // Ej: 101, 201, 311, etc.
    $table->string('tipo_movimiento');  // Ej: EM, SM, TR, AJ, AN
    $table->string('texto_clase_movimiento')->nullable();

    $table->time('hora_entrada')->nullable();
    $table->date('fecha_contabilizacion');
    $table->string('unidad_medida_base');
    $table->decimal('cantidad', 15, 3);

    $table->string('documento_material');
    $table->string('posicion_documento');
    $table->string('referencia')->nullable();

    $table->string('documento_material_sap')->nullable();
    $table->string('referencia_sap')->nullable();
    $table->string('texto_clase_movimiento_sap')->nullable();
    $table->date('fecha_contabilizacion_sap')->nullable();
    $table->string('unidad_medida_base_sap')->nullable();
    $table->decimal('cantidad_sap', 15, 3)->nullable();
    $table->string('clase_de_movimiento_sap')->nullable();
    $table->string('centro_sap')->nullable();

    $table->enum('origen_uso', ['venta', 'instalacion', 'otros'])->default('otros');

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
                    Schema::table('movimiento_materiales', function (Blueprint $table) {
            $table->dropForeign(['fkMateriales']); // Eliminar la clave foránea
            $table->dropColumn('fkMateriales');    // Eliminar la columna
            $table->dropForeign(['fkLotes']); // Eliminar la clave foránea
            $table->dropColumn('fkLotes');    // Eliminar la columna
        });
    }
};
