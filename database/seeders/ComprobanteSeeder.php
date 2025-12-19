<?php

namespace Database\Seeders;

use App\Models\Comprobante;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ComprobanteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

                $documentoss=[
            [
                'tipo_comprobante' => 'Boleta',
                'ClaveVista' => 'DV',
            ],
            [
                'tipo_comprobante' => 'Factura',
                'ClaveVista' => 'DV',
            ],
            [
                'tipo_comprobante' => 'Diario',
                'ClaveVista' => 'CC',
            ],
            [
                'tipo_comprobante' => 'Mayor',
                'ClaveVista' => 'CC',
            ],
            [
                'tipo_comprobante' => 'Balance',
                'ClaveVista' => 'CC',
            ],
            [
                'tipo_comprobante' => 'Banco',
                'ClaveVista' => 'BB',
            ],
            [
                'tipo_comprobante' => 'Devolución',
                'ClaveVista' => 'BD',
            ],
        ];

        foreach ($documentoss as $doc) {

        Comprobante::updateOrCreate(
        ['tipo_comprobante' => $doc['tipo_comprobante']], // clave única
        [
            'tipo_comprobante' => $doc['tipo_comprobante'],
            'estado' => $doc['estado'] ?? 1,
            'ClaveVista' => $doc['ClaveVista'] ?? null
        ]
    );
}
    }
}
