<?php

namespace Database\Seeders;

use App\Models\Comprobante;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatosestaticosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

        public static function getVistas()
    {
        return [
            'DC' => 'Compras',
            'DV' => 'Ventas',
            'DB' => 'Devoluciones',
            'CV' => 'Cuentas por Cobrar',
            'DI' => 'Depósitos',
            'CC' => 'Cuentas por contables',
            'BB' => 'Bancos',
            'BD' => 'Devoluciones de Bancos',
            'CD' => 'Diario',
            'CM' => 'Mayor',
            'CB' => 'Balance',
            'CT' => 'Cuentas por Pagar',
            'DT' => 'Pagos a tecnicos',
            'KI' => 'Kardex Inventario',
            'PC' => 'Pedidos de Compra',
            'PV' => 'Pedidos de Venta',
            'NC' => 'Notas de Crédito',
            'ND' => 'Notas de Débito',
            'AJ' => 'Ajustes',
            'IN' => 'Inventarios',
            'TR' => 'Transferencias'
        ];
    }

    public function run(): void
    {


    }
}
