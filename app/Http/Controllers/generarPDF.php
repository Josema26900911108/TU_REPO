<?php

namespace App\Http\Controllers;
use Barryvdh\DomPDF\Facade\Pdf;


use Illuminate\Http\Request;

class generarPDF extends Controller
{
    public function generarRecibo()
{
    //$pdf = Pdf::loadView('PDF.ticket')->setPaper([0, 0, 226.77, 600], 'portrait'); // térmica
     $pdf = Pdf::loadView('PDF.ticket')
    ->setPaper('a4', 'portrait')
    ->setOptions([
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => true
    ]);


    return $pdf->stream('recibo.pdf'); // o ->download('recibo.pdf');
}
}
