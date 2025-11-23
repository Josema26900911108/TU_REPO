<?php

namespace Database\Seeders;

use App\Models\DocumentDesings;
use App\Models\Documento;
use Illuminate\Database\Seeder;

class DocumentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documentoss=[
            [
                'tipo_documento' => 'DPI',
            ],
            [
                'tipo_documento' => 'NIT',
            ],
            [
                'tipo_documento' => 'Pasaporte',
            ],
            [
                'tipo_documento' => 'RUC',
            ],
            [
                'tipo_documento' => 'Carnet Extranjería',
            ],
        ];

        foreach ($documentoss as $doc) {

    Documento::updateOrCreate(
        ['tipo_documento' => $doc['tipo_documento']], // clave única
        [
            'tipo_documento' => $doc['tipo_documento']
        ]
    );
}

  $documentos = [
            [
                'nombre' => 'Ticket 80mm',
                'ancho_mm' => 80,
                'alto_mm' => null,
                'tipo' => 'ticket'
            ],
            [
                'nombre' => 'Ticket 58mm',
                'ancho_mm' => 58,
                'alto_mm' => null,
                'tipo' => 'ticket'
            ],
            [
                'nombre' => 'Carta',
                'ancho_mm' => 216,
                'alto_mm' => 279,
                'tipo' => 'documento'
            ],
            [
                'nombre' => 'Oficio',
                'ancho_mm' => 216,
                'alto_mm' => 356,
                'tipo' => 'documento'
            ],
            [
                'nombre' => 'A4',
                'ancho_mm' => 210,
                'alto_mm' => 297,
                'tipo' => 'documento'
            ],
            [
                'nombre' => 'Etiqueta 100mm',
                'ancho_mm' => 100,
                'alto_mm' => null,
                'tipo' => 'label'
            ],
            [
                'nombre' => 'Carnet CR80',
                'ancho_mm' => 85.6,
                'alto_mm' => 54,
                'tipo' => 'carnet'
            ],
            [
                'nombre' => 'Carnet Vertical',
                'ancho_mm' => 54,
                'alto_mm' => 86,
                'tipo' => 'carnet'
            ],
            [
                'nombre' => 'Gafete 100x140',
                'ancho_mm' => 100,
                'alto_mm' => 140,
                'tipo' => 'carnet'
            ],
        ];


foreach ($documentos as $doc) {

    // Convertir mm → pt solo si ancho_pt no viene definido
    $ancho_pt = $doc['ancho_pt'] ?? ($doc['ancho_mm'] ? $doc['ancho_mm'] * 2.8346456693 : null);
$alto_pt = $doc['alto_pt']
    ?? (($doc['alto_mm'] ?? 300) * 2.8346456693);
    DocumentDesings::updateOrCreate(
        ['nombre' => $doc['nombre']], // clave única
        [
            'ancho_pt' => $ancho_pt,
            'alto_pt'  => $alto_pt,
            'ancho_mm' => $doc['ancho_mm'],
            'alto_mm'  => $doc['alto_mm'],
            'tipo'     => $doc['tipo']
        ]
    );
}




    }
}
