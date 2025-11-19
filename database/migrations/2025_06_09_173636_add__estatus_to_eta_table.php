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
            'tipos_movimiento',
            'movimientomateriales',
            'documentosap',
            'eta',
            'pagotecnico',
            'descuentotecnico'
        ];

        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                    if ($tabla=='documentosap' || $tabla=='descuentotecnico' || $tabla=='pagotecnico') {

                        if (!Schema::hasColumn($tabla, 'fkTienda')) {
                            $table->unsignedBigInteger('fkTienda')->nullable();
                            $table->foreign('fkTienda')
                                ->references('idTienda')
                                ->on('tienda')
                                ->onDelete('set null');
                        }

                    }
                    if ($tabla=='descuentotecnico' || $tabla=='pagotecnico') {

                        if (!Schema::hasColumn($tabla, 'fkTecnico')) {
                            $table->unsignedBigInteger('fkTecnico')->nullable();
                            $table->foreign('fkTecnico')
                                ->references('id')
                                ->on('tecnico')
                                ->onDelete('set null');

                        }

                    }
                    if ($tabla=='movimientomateriales') {

                        if (!Schema::hasColumn($tabla, 'fkTecnico')) {
                            $table->unsignedBigInteger('fkTecnico')->nullable();
                        }

                    }
                        $table->char('Naturaleza',1)->nullable();
                        $table->char('Status',2)->nullable();
                });
            }
        }



        DB::table('tipos_movimiento')->delete();

        DB::table('tipos_movimiento')->insert([
        ['Status'=>'AC','Naturaleza'=>'D','codigo' => '101', 'abreviatura' => 'EM', 'descripcion' => 'Stock en tránsito'],
        ['Status'=>'AC','Naturaleza'=>'H','codigo' => '201', 'abreviatura' => 'SM', 'descripcion' => 'SM para centro coste'],
        ['Status'=>'AC','Naturaleza'=>'H','codigo' => '221', 'abreviatura' => 'SM', 'descripcion' => 'SM para proyecto'],
        ['Status'=>'AC','Naturaleza'=>'H','codigo' => '231', 'abreviatura' => 'TM', 'descripcion' => 'Stock en tránsito con técnico'],
        ['Status'=>'AC','Naturaleza'=>'H','codigo' => '251', 'abreviatura' => 'SM', 'descripcion' => 'SM para ventas'],
        ['Status'=>'AC','Naturaleza'=>'H','codigo' => '311', 'abreviatura' => 'TR', 'descripcion' => 'Traslado en ce.'],
        ['Status'=>'AC','Naturaleza'=>'D','codigo' => '561', 'abreviatura' => 'EM', 'descripcion' => 'Entr.inicial stocks'],
        ['Status'=>'AC','Naturaleza'=>'D','codigo' => '641', 'abreviatura' => 'TR', 'descripcion' => 'TR A stock tránsito'],
        ['Status'=>'AC','Naturaleza'=>'D','codigo' => '642', 'abreviatura' => 'TR', 'descripcion' => 'TR A stock tránsito'],
        ['Status'=>'AC','Naturaleza'=>'H','codigo' => '905', 'abreviatura' => 'SM', 'descripcion' => 'SM Ajuste Inv.CIA'],
        ['Status'=>'AC','Naturaleza'=>'D','codigo' => '906', 'abreviatura' => 'SM', 'descripcion' => 'Anul. SM Ajuste CIA'],
    ]);


    }

   public function down(): void
{
    $tablas = [
        'tipos_movimiento',
        'movimientomateriales',
        'documentosap',
        'eta',
        'pagotecnico',
        'descuentotecnico'
    ];

    foreach ($tablas as $tabla) {
        if (Schema::hasTable($tabla)) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {

                if (in_array($tabla, ['documentosap', 'descuentotecnico', 'pagotecnico'])) {
                    if (Schema::hasColumn($tabla, 'fkTienda')) {
                        $table->dropForeign(['fkTienda']);
                        $table->dropColumn('fkTienda');
                    }
                }

                if (in_array($tabla, ['descuentotecnico', 'pagotecnico'])) {
                    if (Schema::hasColumn($tabla, 'fkTecnico')) {
                        $table->dropForeign(['fkTecnico']);
                        $table->dropColumn('fkTecnico');
                    }
                }

                if ($tabla == 'movimientomateriales') {
                    if (Schema::hasColumn($tabla, 'fkTecnico')) {
                        $table->dropColumn('fkTecnico');
                    }
                }

                if (Schema::hasColumn($tabla, 'Naturaleza')) {
                    $table->dropColumn('Naturaleza');
                }

                if (Schema::hasColumn($tabla, 'Status')) {
                    $table->dropColumn('Status');
                }
            });
        }
    }
}

};
