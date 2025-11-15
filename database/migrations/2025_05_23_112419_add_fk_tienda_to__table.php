<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tablas = [
            'centro',
            'contrata',
            'documentos_sap',
            'instalaciones_tecnicos',
            'tecnico',
            'movimiento_materiales',
        ];

        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                    if (!Schema::hasColumn($tabla, 'fkTienda')) {
                        $table->unsignedBigInteger('fkTienda')->nullable()->after('id');
                        $table->foreign('fkTienda')
                            ->references('idTienda')
                            ->on('tienda')
                            ->onDelete('set null');
                    }
                });
            }
        }
    }

    public function down(): void
    {
        $tablas = [
            'centro',
            'contrata',
            'documentos_sap',
            'instalaciones_tecnicos',
            'tecnico',
            'movimiento_materiales',
        ];

        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) {
                    if (Schema::hasColumn($table->getTable(), 'fkTienda')) {
                        $table->dropForeign(['fkTienda']);
                        $table->dropColumn('fkTienda');
                    }
                });
            }
        }
    }
};
