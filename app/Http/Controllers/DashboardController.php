<?php

namespace App\Http\Controllers;

use App\Imports\DashboardImport;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function index()
    {
        // Ruta del archivo Excel
        $path = storage_path('app/public/reporte.xlsx');

        // Leer datos
        $rows = Excel::toCollection(new DashboardImport, $path)[0];

        // Quitar cabecera
        $rows = $rows->skip(1);

        // Asumamos que:
        // Columna A = nombre
        // Columna B = valor

        $labels = $rows->pluck(0);  // Columna A
        $values = $rows->pluck(1);  // Columna B

        return view('dashboard.index', compact('labels', 'values'));
    }
}
