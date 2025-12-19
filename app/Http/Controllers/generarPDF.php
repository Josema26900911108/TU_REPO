<?php

namespace App\Http\Controllers;
use Barryvdh\DomPDF\Facade\Pdf;


use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\CuentaContable;
use App\Models\Comprobante;
use App\Models\Folio;
use App\Models\DetalleFolio;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GenericExport;
use App\Models\plantillahtml;
use Illuminate\Support\Facades\Auth;
use App\Exports\UniversalExport;
use Carbon\Carbon;
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

public function diarioindex(Request $request)
{
    $fkTienda = session('user_fkTienda');

    // Lista de cuentas seleccionadas
    $cuentasSeleccionadas = (array) $request->input('cuentas', []);

    // Query principal
    $cuentas = DB::table('cuentas_contables as cc')
        ->join('DetalleFolio as df', 'cc.id', '=', 'df.fkCuenetaContable')
        ->join('Folio as f', 'df.fkFolio', '=', 'f.idFolio')
        ->join('users as u', 'f.fkUsuario', '=', 'u.id')
        ->select(
            'cc.id',
            'cc.nombre')
        ->where('f.fkTienda', $fkTienda)
        ->distinct()
        ->orderBy('f.idFolio')
        ->get();

            $query = DB::table('cuentas_contables as cc')
        ->join('DetalleFolio as df', 'cc.id', '=', 'df.fkCuenetaContable')
        ->join('Folio as f', 'df.fkFolio', '=', 'f.idFolio')
        ->join('users as u', 'f.fkUsuario', '=', 'u.id')
        ->select(
            'cc.id',
            'cc.nombre as NombreCuenta',
            'df.Naturaleza',
            'df.Monto',
            'f.idFolio as NumeroFolio',
            'u.name as usuario',
            DB::raw("DATE_FORMAT(f.FechaContabilizacion, '%d/%m/%Y') as fecha"),
            DB::raw("CASE WHEN df.Naturaleza = 'D' THEN df.Monto ELSE 0 END AS Debe"),
            DB::raw("CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE 0 END AS Haber")
        )
        ->where('f.fkTienda', $fkTienda);


    // FILTRO: Fecha inicial
    if ($request->inicio) {
        $query->whereDate('f.FechaContabilizacion', '>=', $request->inicio);
    }

    // FILTRO: Fecha final
    if ($request->fin) {
        $query->whereDate('f.FechaContabilizacion', '<=', $request->fin);
    }

    // FILTRO: Cuenta contable específica
    if (!empty($cuentasSeleccionadas) && !in_array(0, $cuentasSeleccionadas)) {
        $query->whereIn('cc.id', $cuentasSeleccionadas);
    }

    // Obtener resultados
    $asientos = $query->orderBy('f.idFolio')->orderBy('df.fkFolio')->get();

    // Calcular totales
    $totalDebe = $asientos->sum('Debe');
    $totalHaber = $asientos->sum('Haber');

    // Gráfica
    $labels = $asientos->pluck('fecha');
    $debe = $asientos->pluck('Debe');
    $haber = $asientos->pluck('Haber');
/*$cuentas = $asientos
    ->groupBy('NombreCuenta')
    ->map(function ($items) {
        return [
            'id' => $items->first()->id,
            'nombre' => $items->first()->NombreCuenta
        ];
    })
    ->values(); // Limpia índices*/


    return view('PDF.reportediario', compact(
        'asientos',
        'labels',
        'debe',
        'cuentas',
        'haber',
        'totalDebe',
        'totalHaber'
    ));
}

public function exportdiarioExcel(Request $request)
{
$fkTienda = session('user_fkTienda');
$productosSeleccionados = (array) $request->input('producto', []);


            $query = DB::table('cuentas_contables as cc')
        ->join('DetalleFolio as df', 'cc.id', '=', 'df.fkCuenetaContable')
        ->join('Folio as f', 'df.fkFolio', '=', 'f.idFolio')
        ->join('users as u', 'f.fkUsuario', '=', 'u.id')
        ->select(
            'cc.id',
            'cc.nombre as NombreCuenta',
            'df.Naturaleza',
            'df.Monto',
            'f.idFolio as NumeroFolio',
            'u.name as usuario',
            DB::raw("DATE_FORMAT(f.FechaContabilizacion, '%d/%m/%Y') as fecha"),
            DB::raw("CASE WHEN df.Naturaleza = 'D' THEN df.Monto ELSE 0 END AS Debe"),
            DB::raw("CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE 0 END AS Haber")
        )
        ->where('f.fkTienda', $fkTienda);


    // FILTRO: Fecha inicial
    if ($request->inicio) {
        $query->whereDate('f.FechaContabilizacion', '>=', $request->inicio);
    }

    // FILTRO: Fecha final
    if ($request->fin) {
        $query->whereDate('f.FechaContabilizacion', '<=', $request->fin);
    }

    // FILTRO: Cuenta contable específica
    if (!empty($cuentasSeleccionadas) && !in_array(0, $cuentasSeleccionadas)) {
        $query->whereIn('cc.id', $cuentasSeleccionadas);
    }

    // Obtener resultados
    $data = $query->orderBy('f.idFolio')->orderBy('df.fkFolio')->get();


return Excel::download(new GenericExport($data), 'Diario_reporte_dashboard.xlsx');


}
public function exportmayorExcel(Request $request)
{
$fkTienda = session('user_fkTienda');
$productosSeleccionados = (array) $request->input('producto', []);


            $query = DB::table('cuentas_contables as cc')
        ->join('DetalleFolio as df', 'cc.id', '=', 'df.fkCuenetaContable')
        ->join('Folio as f', 'df.fkFolio', '=', 'f.idFolio')
        ->join('users as u', 'f.fkUsuario', '=', 'u.id')
        ->select(
            'cc.id',
            'cc.nombre as NombreCuenta',
            'df.Naturaleza',
            'df.Monto',
            'f.idFolio as NumeroFolio',
            'u.name as usuario',
            DB::raw("DATE_FORMAT(f.FechaContabilizacion, '%d/%m/%Y') as fecha"),
            DB::raw("CASE WHEN df.Naturaleza = 'D' THEN df.Monto ELSE 0 END AS Debe"),
            DB::raw("CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE 0 END AS Haber")
        )
        ->where('f.fkTienda', $fkTienda);


    // FILTRO: Fecha inicial
    if ($request->inicio) {
        $query->whereDate('f.FechaContabilizacion', '>=', $request->inicio);
    }

    // FILTRO: Fecha final
    if ($request->fin) {
        $query->whereDate('f.FechaContabilizacion', '<=', $request->fin);
    }

    // FILTRO: Cuenta contable específica
    if (!empty($cuentasSeleccionadas) && !in_array(0, $cuentasSeleccionadas)) {
        $query->whereIn('cc.id', $cuentasSeleccionadas);
    }

    // Obtener resultados
    $data = $query->orderBy('f.idFolio')->orderBy('df.fkFolio')->get();


return Excel::download(new GenericExport($data), 'Diario_reporte_dashboard.xlsx');


}
public function exportKardexExcel(Request $request)
{
$fkTienda = session('user_fkTienda');
$cuentasSeleccionadas = (array) $request->input('producto', []);

    $productos = Producto::select('id', 'nombre')
    ->where('fkTienda', $fkTienda)
    ->whereIn('estado', [1, 2, 3])
    ->get();

   $producto = DB::table('productos as p')
    ->select([
        DB::raw('IFNULL(l.codigo, 0) AS codlote'),
        DB::raw('IFNULL(l.stock, 0) AS cantidadlote'),
        DB::raw("IFNULL(l.fecha_vencimiento, '') AS fecha_vencimiento"),

        'p.id',
        'p.nombre',
        'p.codigo',
        'p.stock as stock_actual',
        'p.img_path',
        'p.estado',
        'p.perecedero',
        'p.fkTienda',

        'carMarca.nombre as Marca',

        DB::raw("GROUP_CONCAT(DISTINCT car.nombre ORDER BY car.nombre SEPARATOR ', ') AS Categoria"),

        // Costo promedio ponderado
        DB::raw("SUM(comp.precio_compra * comp.cantidad) / SUM(comp.cantidad) AS costo_promedio"),

        // Costo del stock actual
        DB::raw("p.stock * (SUM(comp.precio_compra * comp.cantidad) / SUM(comp.cantidad)) AS costo_total_actual"),
        DB::raw("p.stock * (SUM(comp.precio_venta * comp.cantidad) / SUM(comp.cantidad)) AS venta_total_actual")
    ])
    ->join('marcas as m', 'p.marca_id', '=', 'm.id')
    ->join('caracteristicas as carMarca', 'm.caracteristica_id', '=', 'carMarca.id')
    ->join('presentaciones as pres', 'p.presentacione_id', '=', 'pres.id')
    ->join('caracteristicas as carPres', 'pres.caracteristica_id', '=', 'carPres.id')
    ->leftJoin('categoria_producto as cp', 'p.id', '=', 'cp.producto_id')
    ->leftJoin('categorias as cat', 'cp.categoria_id', '=', 'cat.id')
    ->leftJoin('caracteristicas as carCat', 'cat.caracteristica_id', '=', 'carCat.id')
    ->leftJoin('caracteristicas as car', 'car.id', '=', 'carCat.id')
    ->leftJoin('lotes as l', 'p.id', '=', 'l.fkProductos')
    ->leftJoin('compra_producto as comp', 'p.id', '=', 'comp.producto_id')
    ->where('p.fkTienda', $fkTienda);


    // FILTRO: Cuenta contable específica
    if (!empty($cuentasSeleccionadas) && !in_array(0, $cuentasSeleccionadas)) {
        $producto->whereIn('p.id', $cuentasSeleccionadas);
    }

    // Obtener resultados
    $data = $producto->groupBy(
        'p.id',
        'p.nombre',
        'p.codigo',
        'p.stock',
        'p.img_path',
        'p.estado',
        'p.perecedero',
        'p.fkTienda',
        'carMarca.nombre',
        'l.id',
        'l.codigo',
        'l.stock',
        'l.fecha_vencimiento'
    )
    ->get();


return Excel::download(new GenericExport($data), 'Diario_reporte_dashboard.xlsx');


}
public function balanceindex(request $request)
{
    $fkTienda = session('user_fkTienda');

    // Lista de cuentas seleccionadas
    $cuentasSeleccionadas = (array) $request->input('cuentas', []);

        $cuentas = DB::table('cuentas_contables as cc')
        ->join('DetalleFolio as df', 'cc.id', '=', 'df.fkCuenetaContable')
        ->join('Folio as f', 'df.fkFolio', '=', 'f.idFolio')
        ->join('users as u', 'f.fkUsuario', '=', 'u.id')
        ->select(
            'cc.id',
            'cc.nombre')
        ->where('f.fkTienda', $fkTienda)
        ->distinct()
        ->orderBy('f.idFolio')
        ->get();

    // Query principal
$query = DB::table(DB::raw("(
    SELECT
        DATE_FORMAT(f.FechaContabilizacion, '%d/%m/%Y') AS Fecha,
        f.FechaContabilizacion AS FechaReporte,
        cc.id AS idCuenta,
        cc.nombre AS Cuenta,
        cc.formula,
        t.Nombre,
        t.departamento,
        t.municipio,
        t.representante,
        t.Telefono,
        t.nit,
        t.logo,

        -- SUMAS GENERALES (toda la tabla filtrada)
        SUM(CASE WHEN df.Naturaleza = 'D' THEN df.Monto ELSE 0 END)
            OVER () AS DebeGeneral,

        SUM(CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE 0 END)
            OVER () AS HaberGeneral,

        df.Naturaleza,
        df.Monto,

        -- SALDO FINAL GLOBAL
        SUM(CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE -df.Monto END)
            OVER () AS SaldoFinal,

        -- CAMPOS INDIVIDUALES
        CASE WHEN df.Naturaleza = 'D' THEN df.Monto ELSE 0 END AS Debe,
        CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE 0 END AS Haber

    FROM Folio AS f
    INNER JOIN DetalleFolio AS df ON f.idFolio = df.fkFolio
    INNER JOIN cuentas_contables AS cc ON df.fkCuenetaContable = cc.id
    INNER JOIN tienda AS t ON f.fkTienda = t.idTienda

    WHERE f.fkTienda = {$fkTienda}
) x"));



 // Filtro fecha inicial
if ($request->inicio) {
    $query->whereDate('x.FechaReporte', '>=', $request->inicio);
}

// Filtro fecha final
if ($request->fin) {
    $query->whereDate('x.FechaReporte', '<=', $request->fin);
}

// Filtro cuentas seleccionadas
if (!empty($cuentasSeleccionadas) && !in_array(0, $cuentasSeleccionadas)) {
    $query->whereIn('x.idCuenta', $cuentasSeleccionadas);
}


    // Obtener resultados
    $asientos = $query
    ->selectRaw("
        x.SaldoFinal,
        x.DebeGeneral,
        x.HaberGeneral,
        x.Fecha,
        x.FechaReporte,
        x.nit,
        x.logo,
        SUM(x.Debe)  AS Debe,
        SUM(x.Haber) AS Haber,
        SUM(
            CASE
                WHEN x.Naturaleza = 'D' THEN x.Monto
                ELSE -x.Monto
            END
        ) AS SaldoCuenta,
        x.idCuenta,
        x.Cuenta AS nombre,
        x.formula,
        x.Nombre AS Tienda,
        x.departamento,
        x.municipio,
        x.representante,
        x.Telefono
    ")
->groupBy(
    'x.idCuenta', 'x.Cuenta', 'x.formula',
    'x.Nombre', 'x.departamento',
    'x.municipio', 'x.representante',
    'x.Telefono', 'x.nit', 'x.logo',
    'x.SaldoFinal',
    'x.DebeGeneral',
    'x.HaberGeneral',
    'x.Fecha',
    'x.FechaReporte'
)

    ->orderBy('x.idCuenta')
    ->get();


    // Calcular totales
    $totalDebe = $asientos->sum('Debe');
    $totalHaber = $asientos->sum('Haber');

    // Gráfica
$labels = [];
$debe = [];
$haber = [];

foreach ($asientos as $a) {
    $labels[] = $a->Fecha . ' - ' . $a->nombre; // ejemplo: "2025-01-12 - Caja Bancos"
    $debe[] = $a->Debe;
    $haber[] = $a->Haber;
}


$SaldoFinal=0;
$DebeFinal=0;
$HaberFinal=0;

    return view('PDF.reportebalance', compact(
        'asientos',
        'labels',
        'debe',
        'cuentas',
        'haber',
        'totalDebe',
        'totalHaber',
        'SaldoFinal'
    ));

}
   public function generarMayor(Request $request)
{
    $fkTienda = session('user_fkTienda');



           $query = DB::table('plantillahtml as ph')
           ->join('documentdesigns as dd', 'ph.fkDesignDocument', '=', 'dd.id')
           ->join('comprobantes as c', 'ph.id', '=', 'c.fkPlantillaHtml')
        ->where('ph.fkTienda', $fkTienda)
        ->where('c.ClaveVista', 'CM')
        ->select(
            'ph.id',
            'ph.Titulo',
            'ph.fkDesignDocument',
            'ph.plantillahtml as detallehijo',
            'ph.descripcion as detallenieto',
            'ph.detalle',
            'dd.alto_pt',
            'dd.ancho_pt',
            'dd.orientacion_vertical as orientation',
            'ph.cabecera',
            'ph.pie',
            'ph.consulta'
        )
        ->distinct(); // 3 = Diario

$fechafiltro="";
$fechalabel="";

    // FILTRO: Fecha inicial
    if ($request->inicio) {
        $fechafiltro="'".$request->inicio."'";
        $fechalabel=" Desde ".$request->inicio;
    }

    // FILTRO: Fecha final
    if ($request->fin) {

        $fechafiltro=$fechafiltro." AND "."'".$request->fin."'";
        $fechalabel=$fechalabel." Hasta ".$request->fin;
    }


        $plantilla = $query->orderBy('ph.updated_at')->first();


    $cabecera = $plantilla->cabecera;
    $detalle = $plantilla->detalle;
    $pie = $plantilla->pie;
    $consulta = $plantilla->consulta;
    $detalleHijo=$plantilla->detallehijo;
    $detalleNieto=$plantilla->detallenieto;

    $tokens = ['idventa' => $fechafiltro, 'idtienda' => $fkTienda];


    // Si height_mm o width_mm es null, dar valor por defecto
    $altura = ($plantilla->alto_pt ?? 205);
    $ancho = $plantilla->ancho_pt ?? 226.77;
    $orientacion = $plantilla->orientation ?? 'portrait';

    $cons = $this->procesarConsulta($consulta, $tokens);
    $tokenss = $this->ejecutarconsulta($cons);
    //$tokenss['filas'][0]['FechaReporte']=$fechafiltro;

    foreach ($tokenss['filas'] as $index => $item) {
    $tokenss['filas'][$index]['FechaReporte'] = $fechalabel;
}

    $detalle=$this->renderDetalleOptimizado($detalle, $tokenss, $detalleHijo, $detalleNieto );


    $htmlFinal = $this->procesarPlantilla($cabecera, $detalle, $detalleHijo.$detalleNieto.$pie, $tokenss['columnas'], $tokenss['filas']);

      $footerHTML = '
<div class="footer">

        Página <span class="page-number"></span> / {{TOTAL_PAGINAS}} - Impreso por {{username}}

</div>

        <style>
@page {
    margin-top: 80px;
    margin-bottom: 50px;
}
.header {
    position: fixed;
    top: 10px;
    left: 0;
    right: 0;
}
.footer {
    position: fixed;
    bottom: 10px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 10px;
}

.page-number:before {
    content: counter(page);
}
        </style>
    ';


    // Procesar HTML final con footer agregado
    $htmlFinal = $this->procesarPlantilla(
        $cabecera,
        $detalle,
        $detalleHijo.$detalleNieto.$footerHTML.$pie,
        $tokenss['columnas'],
        $tokenss['filas']
    );



        $pdfTemp = Pdf::loadHtml($htmlFinal);
    $pdfTemp->setPaper([0, 0, $ancho, $altura], $orientacion);

    $pdfTemp->render();
    $totalPaginas = $pdfTemp->getDomPDF()->getCanvas()->get_page_count();

$htmlFinal = str_replace("{{TOTAL_PAGINAS}}", $totalPaginas, $htmlFinal);
$htmlFinal = str_replace("{{username}}", auth()->user()->name, $htmlFinal);


    $pdf = Pdf::loadHTML($htmlFinal)->setPaper([0, 0, $ancho, $altura], $orientacion);

    // Crear carpeta si no existe
    $rutaCarpeta = storage_path('app/public/recibos');
    if (!file_exists($rutaCarpeta)) {
        mkdir($rutaCarpeta, 0777, true);
    }

    // Guardar PDF
    $rutaArchivo = $rutaCarpeta.'/recibocompra_'.$fechafiltro.'.pdf';
    $pdf->save($rutaArchivo);

    // Finalmente, abrir en el navegador
    return response()->file($rutaArchivo);
}
   public function generarKardex(Request $request)
{
    $fkTienda = session('user_fkTienda');



           $query = DB::table('plantillahtml as ph')
           ->join('documentdesigns as dd', 'ph.fkDesignDocument', '=', 'dd.id')
           ->join('comprobantes as c', 'ph.id', '=', 'c.fkPlantillaHtml')
        ->where('ph.fkTienda', $fkTienda)
        ->where('c.ClaveVista', 'KI')
        ->select(
            'ph.id',
            'ph.Titulo',
            'ph.fkDesignDocument',
            'ph.plantillahtml as detallehijo',
            'ph.descripcion as detallenieto',
            'ph.detalle',
            'dd.alto_pt',
            'dd.ancho_pt',
            'dd.orientacion_vertical as orientation',
            'ph.cabecera',
            'ph.pie',
            'ph.consulta'
        )
        ->distinct(); // 3 = Diario

$fechafiltro="";
$fechalabel="";

    // FILTRO: Fecha inicial
    if ($request->inicio) {
        $fechafiltro="'".$request->inicio."'";
        $fechalabel=" Desde ".$request->inicio;
    }

    // FILTRO: Fecha final
    if ($request->fin) {

        $fechafiltro=$fechafiltro." AND "."'".$request->fin."'";
        $fechalabel=$fechalabel." Hasta ".$request->fin;
    }


        $plantilla = $query->orderBy('ph.updated_at')->first();


    $cabecera = $plantilla->cabecera;
    $detalle = $plantilla->detalle;
    $pie = $plantilla->pie;
    $consulta = $plantilla->consulta;
    $detalleHijo=$plantilla->detallehijo;
    $detalleNieto=$plantilla->detallenieto;

    $tokens = ['idventa' => $fechafiltro, 'idtienda' => $fkTienda];


    // Si height_mm o width_mm es null, dar valor por defecto
    $altura = ($plantilla->alto_pt ?? 205);
    $ancho = $plantilla->ancho_pt ?? 226.77;
    $orientacion = $plantilla->orientation ?? 'portrait';

    $cons = $this->procesarConsulta($consulta, $tokens);
    $tokenss = $this->ejecutarconsulta($cons);
    //$tokenss['filas'][0]['FechaReporte']=$fechafiltro;

    foreach ($tokenss['filas'] as $index => $item) {
    $tokenss['filas'][$index]['FechaReporte'] = $fechalabel;
}

    $detalle=$this->renderDetalleOptimizado($detalle, $tokenss, $detalleHijo, $detalleNieto );


    $htmlFinal = $this->procesarPlantilla($cabecera, $detalle, $detalleHijo.$detalleNieto.$pie, $tokenss['columnas'], $tokenss['filas']);

      $footerHTML = '
<div class="footer">

        Página <span class="page-number"></span> / {{TOTAL_PAGINAS}} - Impreso por {{username}}

</div>

        <style>
@page {
    margin-top: 80px;
    margin-bottom: 50px;
}
.header {
    position: fixed;
    top: 10px;
    left: 0;
    right: 0;
}
.footer {
    position: fixed;
    bottom: 10px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 10px;
}

.page-number:before {
    content: counter(page);
}
        </style>
    ';


    // Procesar HTML final con footer agregado
    $htmlFinal = $this->procesarPlantilla(
        $cabecera,
        $detalle,
        $detalleHijo.$detalleNieto.$footerHTML.$pie,
        $tokenss['columnas'],
        $tokenss['filas']
    );



        $pdfTemp = Pdf::loadHtml($htmlFinal);
    $pdfTemp->setPaper([0, 0, $ancho, $altura], $orientacion);

    $pdfTemp->render();
    $totalPaginas = $pdfTemp->getDomPDF()->getCanvas()->get_page_count();

$htmlFinal = str_replace("{{TOTAL_PAGINAS}}", $totalPaginas, $htmlFinal);
$htmlFinal = str_replace("{{FechaReporte}}", $fechalabel, $htmlFinal);
$htmlFinal = str_replace("{{username}}", auth()->user()->name, $htmlFinal);
$htmlFinal = str_replace("{{ENCABEZADOPAGINA}}", $fechaHora = $this->fechaHoraEnLetras(), $htmlFinal);


    $pdf = Pdf::loadHTML($htmlFinal)->setPaper([0, 0, $ancho, $altura], $orientacion);

    // Crear carpeta si no existe
    $rutaCarpeta = storage_path('app/public/recibos');
    if (!file_exists($rutaCarpeta)) {
        mkdir($rutaCarpeta, 0777, true);
    }

    // Guardar PDF
    $rutaArchivo = $rutaCarpeta.'/recibocompra_'.$fechafiltro.'.pdf';
    $pdf->save($rutaArchivo);

    // Finalmente, abrir en el navegador
    return response()->file($rutaArchivo);
}

public function fechaHoraEnLetras()
{
    Carbon::setLocale('es');

    return Carbon::now()->translatedFormat('l d \d\e F \d\e Y H:i');
}



  public function generarBalance(Request $request)
{
    $fkTienda = session('user_fkTienda');

    $query = DB::table('plantillahtml as ph')
        ->join('documentdesigns as dd', 'ph.fkDesignDocument', '=', 'dd.id')
        ->join('comprobantes as c', 'ph.id', '=', 'c.fkPlantillaHtml')
        ->where('ph.fkTienda', $fkTienda)
        ->where('c.ClaveVista', 'CB')
        ->select(
            'ph.id',
            'ph.Titulo',
            'ph.fkDesignDocument',
            'ph.plantillahtml as detallehijo',
            'ph.descripcion as detallenieto',
            'ph.detalle',
            'dd.alto_pt',
            'dd.ancho_pt',
            'dd.orientacion_vertical as orientation',
            'ph.cabecera',
            'ph.pie',
            'ph.consulta'
        )
        ->distinct();

    $fechafiltro = "";
    $fechalabel = "";

    if ($request->inicio) {
        $fechafiltro = "'" . $request->inicio . "'";
        $fechalabel = " Desde " . $request->inicio;
    }

    if ($request->fin) {
        $fechafiltro .= " AND '" . $request->fin . "'";
        $fechalabel .= " Hasta " . $request->fin;
    }

    $plantilla = $query->orderBy('ph.updated_at')->first();

    $cabecera = $plantilla->cabecera;
    $detalle = $plantilla->detalle;
    $pie = $plantilla->pie;
    $consulta = $plantilla->consulta;
    $detalleHijo = $plantilla->detallehijo;
    $detalleNieto = $plantilla->detallenieto;

    $tokens = [
        'idventa' => $fechafiltro,
        'idtienda' => $fkTienda
    ];

    $altura = ($plantilla->alto_pt ?? 205);
    $ancho = ($plantilla->ancho_pt ?? 226.77);
    $orientacion = $plantilla->orientation ?? 'portrait';

    $cons = $this->procesarConsulta($consulta, $tokens);
    $tokenss = $this->ejecutarconsulta($cons);

    foreach ($tokenss['filas'] as $index => $item) {
        $tokenss['filas'][$index]['FechaReporte'] = $fechalabel;
    }

    $detalle = $this->renderDetalleOptimizado(
        $detalle,
        $tokenss,
        $detalleHijo,
        $detalleNieto
    );

    // ============================================================
    // PRIMERA PASADA: calcular número real de páginas
    // ============================================================

        $footerHTML = '
<div class="footer">

        Página <span class="page-number"></span> / {{TOTAL_PAGINAS}} - Impreso por {{username}}

</div>

        <style>
@page {
    margin-top: 80px;
    margin-bottom: 50px;
}
.header {
    position: fixed;
    top: 10px;
    left: 0;
    right: 0;
}
.footer {
    position: fixed;
    bottom: 10px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 10px;
}

.page-number:before {
    content: counter(page);
}
        </style>
    ';


    $htmlTemp = $this->procesarPlantilla(
        $cabecera,
        $detalle,
        $detalleHijo . $detalleNieto . $footerHTML .$pie,
        $tokenss['columnas'],
        $tokenss['filas']
    );


    $pdfTemp = Pdf::loadHtml($htmlTemp);
    $pdfTemp->setPaper([0, 0, $ancho, $altura], $orientacion);

    $pdfTemp->render();
    $totalPaginas = $pdfTemp->getDomPDF()->getCanvas()->get_page_count();

    // ============================================================
    // SEGUNDA PASADA: insertar el total real de páginas
    // ============================================================

    $htmlFinal = str_replace("{{TOTAL_PAGINAS}}", $totalPaginas, $htmlTemp);
    $htmlFinal = str_replace("{{FechaReporte}}", $fechalabel, $htmlFinal);
    $htmlFinal = str_replace("{{username}}", auth()->user()->name, $htmlFinal);
    $htmlFinal = str_replace("{{ENCABEZADOPAGINA}}", $this->fechaHoraEnLetras(), $htmlFinal);

    $pdf = Pdf::loadHtml($htmlFinal);
    $pdf->setPaper([0, 0, $ancho, $altura], $orientacion);

    // Crear carpeta si no existe
    $rutaCarpeta = storage_path('app/public/recibos');
    if (!file_exists($rutaCarpeta)) {
        mkdir($rutaCarpeta, 0777, true);
    }

    $rutaArchivo = $rutaCarpeta . '/recibocompra_' . date('Ymd_His') . '.pdf';

    $pdf->save($rutaArchivo);

    return response()->file($rutaArchivo);
}

public function mayorindex(Request $request)
{
    $fkTienda = session('user_fkTienda');

    // Lista de cuentas seleccionadas
    $cuentasSeleccionadas = (array) $request->input('cuentas', []);

    // Query principal
    $cuentas = DB::table('cuentas_contables as cc')
        ->join('DetalleFolio as df', 'cc.id', '=', 'df.fkCuenetaContable')
        ->join('Folio as f', 'df.fkFolio', '=', 'f.idFolio')
        ->join('users as u', 'f.fkUsuario', '=', 'u.id')
        ->select(
            'cc.id',
            'cc.nombre')
        ->where('f.fkTienda', $fkTienda)
        ->distinct()
        ->orderBy('f.idFolio')
        ->get();

            $query = DB::table('cuentas_contables as cc')
        ->join('DetalleFolio as df', 'cc.id', '=', 'df.fkCuenetaContable')
        ->join('Folio as f', 'df.fkFolio', '=', 'f.idFolio')
        ->join('users as u', 'f.fkUsuario', '=', 'u.id')
        ->select(
            'cc.id',
            'cc.nombre as NombreCuenta',
            'df.Naturaleza',
            'df.Monto',
            'f.idFolio as NumeroFolio',
            'u.name as usuario',
            DB::raw("DATE_FORMAT(f.FechaContabilizacion, '%d/%m/%Y') as fecha"),
            DB::raw("    (CASE WHEN df.Naturaleza = 'D' THEN df.Monto ELSE 0 END)
    -
    (CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE 0 END) AS SaldoLinea"),
            DB::raw("    SUM(CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE 0 END)
        OVER (PARTITION BY f.idFolio) AS HaberTotal"),
            DB::raw("SUM(CASE WHEN df.Naturaleza = 'D' THEN df.Monto ELSE 0 END)
        OVER (PARTITION BY f.idFolio) AS DebeTotal"),
            DB::raw("CASE WHEN df.Naturaleza = 'D' THEN df.Monto ELSE 0 END AS Debe"),
            DB::raw("CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE 0 END AS Haber")
        )
        ->where('f.fkTienda', $fkTienda);


    // FILTRO: Fecha inicial
    if ($request->inicio) {
        $query->whereDate('f.FechaContabilizacion', '>=', $request->inicio);
    }

    // FILTRO: Fecha final
    if ($request->fin) {
        $query->whereDate('f.FechaContabilizacion', '<=', $request->fin);
    }

    // FILTRO: Cuenta contable específica
    if (!empty($cuentasSeleccionadas) && !in_array(0, $cuentasSeleccionadas)) {
        $query->whereIn('cc.id', $cuentasSeleccionadas);
    }

    // Obtener resultados
    $asientos = $query->orderBy('f.idFolio')->orderBy('df.fkFolio')->get();

    // Calcular totales
    $totalDebe = $asientos->sum('Debe');
    $totalHaber = $asientos->sum('Haber');

    // Gráfica
    $labels = $asientos->pluck('fecha');
    $debe = $asientos->pluck('Debe');
    $haber = $asientos->pluck('Haber');
/*$cuentas = $asientos
    ->groupBy('NombreCuenta')
    ->map(function ($items) {
        return [
            'id' => $items->first()->id,
            'nombre' => $items->first()->NombreCuenta
        ];
    })
    ->values(); // Limpia índices*/
$SaldoFinal=0;
$DebeFinal=0;
$HaberFinal=0;

    return view('PDF.reportemayor', compact(
        'asientos',
        'labels',
        'debe',
        'cuentas',
        'haber',
        'totalDebe',
        'totalHaber',
        'SaldoFinal'
    ));
}
public function puntodeequilibrioindex()
{
    $pdf = Pdf::loadView('PDF.diario')
    ->setPaper('a4', 'landscape')
    ->setOptions([
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => true
    ]);

    return $pdf->stream('diario.pdf'); // o ->download('diario.pdf');{

}

public function utilidadesindex()
{
    $pdf = Pdf::loadView('PDF.diario')
    ->setPaper('a4', 'landscape')
    ->setOptions([
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => true
    ]);

    return $pdf->stream('diario.pdf'); // o ->download('diario.pdf');{

}
public function flujoefectivoindex()
{
    $pdf = Pdf::loadView('PDF.diario')
    ->setPaper('a4', 'landscape')
    ->setOptions([
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => true
    ]);

    return $pdf->stream('diario.pdf'); // o ->download('diario.pdf');{

}

function agruparDetalle(array $filas): array
{
    $padres = [];

    foreach ($filas as $fila) {

        $idPadre = $fila['idPivot'];
        $idHijo  = $fila['idPivotHijo'];

        // Crear padre si no existe
        if (!isset($padres[$idPadre])) {
            $padres[$idPadre] = [
                'data' => $fila,
                'hijos' => []
            ];
        }

        // Crear hijo si no existe
        if (!isset($padres[$idPadre]['hijos'][$idHijo])) {
            $padres[$idPadre]['hijos'][$idHijo] = [
                'data' => $fila,
                'nietos' => []
            ];
        }

        // Agregar nieto
        $padres[$idPadre]['hijos'][$idHijo]['nietos'][] = $fila;
    }

    return array_values($padres);
}


public function KardexInvenarioindex(Request $request)
{
    $fkTienda = session('user_fkTienda');
    $cuentasSeleccionadas = (array) $request->input('producto', []);

    $productos = Producto::select('id', 'nombre')
    ->where('fkTienda', $fkTienda)
    ->whereIn('estado', [1, 2, 3])
    ->get();

   $producto = DB::table('productos as p')
    ->select([
        DB::raw('IFNULL(l.codigo, 0) AS codlote'),
        DB::raw('IFNULL(l.stock, 0) AS cantidadlote'),
        DB::raw("IFNULL(l.fecha_vencimiento, '') AS fecha_vencimiento"),

        'p.id',
        'p.nombre',
        'p.codigo',
        'p.stock as stock_actual',
        'p.img_path',
        'p.estado',
        'p.perecedero',
        'p.fkTienda',

        'carMarca.nombre as Marca',

        DB::raw("GROUP_CONCAT(DISTINCT car.nombre ORDER BY car.nombre SEPARATOR ', ') AS Categoria"),

        // Costo promedio ponderado
        DB::raw("SUM(comp.precio_compra * comp.cantidad) / SUM(comp.cantidad) AS costo_promedio"),

        // Costo del stock actual
        DB::raw("p.stock * (SUM(comp.precio_compra * comp.cantidad) / SUM(comp.cantidad)) AS costo_total_actual"),
        DB::raw("p.stock * (SUM(comp.precio_venta * comp.cantidad) / SUM(comp.cantidad)) AS venta_total_actual")
    ])
    ->join('marcas as m', 'p.marca_id', '=', 'm.id')
    ->join('caracteristicas as carMarca', 'm.caracteristica_id', '=', 'carMarca.id')
    ->join('presentaciones as pres', 'p.presentacione_id', '=', 'pres.id')
    ->join('caracteristicas as carPres', 'pres.caracteristica_id', '=', 'carPres.id')
    ->leftJoin('categoria_producto as cp', 'p.id', '=', 'cp.producto_id')
    ->leftJoin('categorias as cat', 'cp.categoria_id', '=', 'cat.id')
    ->leftJoin('caracteristicas as carCat', 'cat.caracteristica_id', '=', 'carCat.id')
    ->leftJoin('caracteristicas as car', 'car.id', '=', 'carCat.id')
    ->leftJoin('lotes as l', 'p.id', '=', 'l.fkProductos')
    ->leftJoin('compra_producto as comp', 'p.id', '=', 'comp.producto_id')
    ->where('p.fkTienda', $fkTienda);


    // FILTRO: Cuenta contable específica
    if (!empty($cuentasSeleccionadas) && !in_array(0, $cuentasSeleccionadas)) {
        $producto->whereIn('p.id', $cuentasSeleccionadas);
    }

    // Obtener resultados
    $asientos = $producto->groupBy(
        'p.id',
        'p.nombre',
        'p.codigo',
        'p.stock',
        'p.img_path',
        'p.estado',
        'p.perecedero',
        'p.fkTienda',
        'carMarca.nombre',
        'l.id',
        'l.codigo',
        'l.stock',
        'l.fecha_vencimiento'
    )
    ->get();

    // Calcular totales

    $totalHaber = $asientos->sum('costo_total_actual');

    // Gráfica
    $labels = $asientos->pluck('nombre');
    $debe = $asientos->pluck('stock_actual');

    $haber = $asientos->pluck('costo_total_actual');
    $venta = $asientos->pluck('venta_total_actual');
$values = $debe;

    return view('PDF.reportekardexinventario', compact(
        'productos',
        'labels',
        'debe',
        'asientos',
        'haber',
        'venta',
        'values',
        'totalHaber'
    ));
}

function renderDetalleOptimizado(
    string $template,
    array $detalle,
    string $detalleHijo,
    string $detalleNieto
): string {

    // ---- 1) Extraer bloque padre ----
    if (!preg_match('/{{#detalle}}([\s\S]*?){{\/detalle}}/', $template, $padreMatch)) {
        return $template;
    }

    $padreTemplate = $padreMatch[1];

    // ---- 2) Obtener lista única de padres ----
    $detalleUnico = collect($detalle['filas'])->unique('idPivot')->values();

    // ---- 3) Renderizar cada padre ----
    $renderPadres = $detalleUnico->map(function ($padre) use ($detalle, $padreTemplate, $detalleHijo, $detalleNieto) {

        $bloquePadre = $padreTemplate;

        // ---- 3.1) Procesar hijos ----
        $bloquePadre = preg_replace_callback(
            '/{{#detallehijo}}([\s\S]*?){{\/detallehijo}}/',
            function ($match) use ($padre, $detalle) {

                $hijoTemplate = $match[1];

                // Filtrar hijos del padre
                $hijos = collect($detalle['filas'])
                    ->where('idPivot', $padre['idPivot']);

                return $hijos->map(function ($hijo) use ($hijoTemplate, $detalle) {

                    $bloqueHijo = $hijoTemplate;

                    // Reemplazar variables del hijo
                    foreach ($hijo as $key => $value) {
                        $bloqueHijo = str_replace("{{{$key}}}", $value, $bloqueHijo);
                    }

                    // ---- 3.2) Procesar nietos ----
                    $bloqueHijo = preg_replace_callback(
                        '/{{#detallenieto}}([\s\S]*?){{\/detallenieto}}/',
                        function ($m) use ($hijo, $detalle) {

                            $nietoTemplate = $m[1];

                            $nietos = collect($detalle['filas'])
                                ->where('idPivotHijo', $hijo['idPivotHijo']);

                            return $nietos->map(function ($nieto) use ($nietoTemplate) {

                                $row = $nietoTemplate;

                                foreach ($nieto as $key => $value) {
                                    $row = str_replace("{{{$key}}}", $value, $row);
                                }

                                return $row;

                            })->implode('');
                        },
                        $bloqueHijo
                    );

                    return $bloqueHijo;

                })->implode('');
            },
            $bloquePadre
        );

        // ---- 3.3) Reemplazar variables del padre ----
        foreach ($padre as $key => $value) {
            $bloquePadre = str_replace("{{{$key}}}", $value, $bloquePadre);
        }

        return $bloquePadre;

    })->implode('');

    // ---- 4) Reemplazar bloque completo ----
    return str_replace($padreMatch[0], $renderPadres, $template);
}


function renderDetalleOptimizado1(
    string $template,
    array $detalle
): string {

/*dd(
    collect($detalle['filas'])->pluck('idPivot')
);*/



    if (!preg_match('/{{#detalle}}([\s\S]*?){{\/detalle}}/', $template, $padreMatch)) {
        return $template;
    }

    $padreTemplate = $padreMatch[1];

    // AGRUPAR UNA SOLA VEZ
    $padres = $this->agruparDetalle($detalle['filas']);

    $renderPadres = collect($padres)->map(function ($padre) use ($padreTemplate) {

        $bloquePadre = $padreTemplate;

        // HIJOS
        $bloquePadre = preg_replace_callback(
            '/{{#detallehijo}}([\s\S]*?){{\/detallehijo}}/',
            function ($match) use ($padre) {

                $hijoTemplate = $match[1];

                return collect($padre['hijos'])->map(function ($hijo) use ($hijoTemplate) {

                    $bloqueHijo = $hijoTemplate;

                    // reemplazar datos hijo
                    foreach ($hijo['data'] as $key => $value) {
                        $bloqueHijo = str_replace("{{{$key}}}", $value, $bloqueHijo);
                    }

                    // NIETOS
                    $bloqueHijo = preg_replace_callback(
                        '/{{#detallenieto}}([\s\S]*?){{\/detallenieto}}/',
                        function ($m) use ($hijo) {

                            $nietoTemplate = $m[1];

                            return collect($hijo['nietos'])->map(function ($nieto) use ($nietoTemplate) {

                                $row = $nietoTemplate;

                                foreach ($nieto as $key => $value) {
                                    $row = str_replace("{{{$key}}}", $value, $row);
                                }

                                return $row;

                            })->implode('');
                        },
                        $bloqueHijo
                    );

                    return $bloqueHijo;

                })->implode('');
            },
            $bloquePadre
        );

        // datos del padre
        foreach ($padre['data'] as $key => $value) {
            $bloquePadre = str_replace("{{{$key}}}", $value, $bloquePadre);
        }

        return $bloquePadre;

    })->implode('');

    return str_replace($padreMatch[0], $renderPadres, $template);
}


   public function generarDiario(Request $request)
{
    $fkTienda = session('user_fkTienda');

    $query = DB::table('plantillahtml as ph')
        ->join('documentdesigns as dd', 'ph.fkDesignDocument', '=', 'dd.id')
        ->join('comprobantes as c', 'ph.id', '=', 'c.fkPlantillaHtml')
        ->where('ph.fkTienda', $fkTienda)
        ->where('c.ClaveVista', 'CD')
        ->select(
            'ph.id',
            'ph.Titulo',
            'ph.fkDesignDocument',
            'ph.plantillahtml as detallehijo',
            'ph.descripcion as detallenieto',
            'ph.detalle',
            'dd.alto_pt',
            'dd.ancho_pt',
            'dd.orientacion_vertical as orientation',
            'ph.cabecera',
            'ph.pie',
            'ph.consulta'
        )
        ->distinct();

    $fechafiltro = "";
    $fechalabel = "";

    if ($request->inicio) {
        $fechafiltro = "'".$request->inicio."'";
        $fechalabel = " Desde ".$request->inicio;
    }

    if ($request->fin) {
        $fechafiltro .= " AND '".$request->fin."'";
        $fechalabel .= " Hasta ".$request->fin;
    }

    $plantilla = $query->orderBy('ph.updated_at')->first();

    $cabecera = $plantilla->cabecera;
    $detalle = $plantilla->detalle;
    $pie = $plantilla->pie;
    $consulta = $plantilla->consulta;
    $detalleHijo = $plantilla->detallehijo;
    $detalleNieto = $plantilla->detallenieto;

    $tokens = [
        'idventa' => $fechafiltro,
        'idtienda' => $fkTienda
    ];

    $altura = $plantilla->alto_pt ?? 205;
    $ancho = $plantilla->ancho_pt ?? 226.77;
    $orientacion = $plantilla->orientation ?? 'portrait';

    $cons = $this->procesarConsulta($consulta, $tokens);
    $tokenss = $this->ejecutarconsulta($cons);

    foreach ($tokenss['filas'] as $i => $fila) {
        $tokenss['filas'][$i]['FechaReporte'] = $fechalabel;
    }

    $detalle = $this->renderDetalleOptimizado(
        $detalle,
        $tokenss,
        $detalleHijo,
        $detalleNieto
    );




    // ---------------------------------------------
    // 🔥 AÑADIR FOOTER AUTOMÁTICO CON NUMERACIÓN
    // ---------------------------------------------
    $footerHTML = '
<div class="footer">

        Página <span class="page-number"></span> / {{TOTAL_PAGINAS}} - Impreso por {{username}}

</div>

        <style>
@page {
    margin-top: 80px;
    margin-bottom: 50px;
}
.header {
    position: fixed;
    top: 10px;
    left: 0;
    right: 0;
}
.footer {
    position: fixed;
    bottom: 10px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 10px;
}

.page-number:before {
    content: counter(page);
}
        </style>
    ';


    // Procesar HTML final con footer agregado
    $htmlFinal = $this->procesarPlantilla(
        $cabecera,
        $detalle,
        $detalleHijo.$detalleNieto.$footerHTML.$pie,
        $tokenss['columnas'],
        $tokenss['filas']
    );

        $pdfTemp = Pdf::loadHtml($htmlFinal);
    $pdfTemp->setPaper([0, 0, $ancho, $altura], $orientacion);

    $pdfTemp->render();
    $totalPaginas = $pdfTemp->getDomPDF()->getCanvas()->get_page_count();

$htmlFinal = str_replace("{{TOTAL_PAGINAS}}", $totalPaginas, $htmlFinal);
$htmlFinal = str_replace("{{username}}", Auth::user()->name, $htmlFinal);
$htmlFinal = str_replace("{{ENCABEZADOPAGINA}}", $fechaHora = $this->fechaHoraEnLetras(), $htmlFinal);

    // Generar PDF
    $pdf = Pdf::loadHTML($htmlFinal)->setPaper([0, 0, $ancho, $altura], $orientacion);

    // Carpeta
    $rutaCarpeta = storage_path('app/public/recibos');
    if (!file_exists($rutaCarpeta)) {
        mkdir($rutaCarpeta, 0777, true);
    }

    $rutaArchivo = $rutaCarpeta.'/recibodiario_'.$request->inicio.'_'.$request->fin.'.pdf';

    $pdf->save($rutaArchivo);

    return response()->file($rutaArchivo);
}

function procesarConsulta($consulta, $tokens)
{
    $consultaprocesada = $consulta;

    foreach ($tokens as $token => $valor) {
        $pattern = '/@{{\s*' . preg_quote($token, '/') . '\s*}}/';
        $consultaprocesada = preg_replace($pattern, $valor, $consultaprocesada);
    }

    return $consultaprocesada;
}
function procesarPlantilla($cab, $htmlDetalle, $pi, $variablesGlobales, $detalle)
{
    foreach ($variablesGlobales as $token => $valor) {

        $pattern = '/\{\{\s*' . preg_quote($valor, '/') . '\s*\}\}/';

        if ($valor == "logo") {
            $compressed = trim($detalle[0][$valor] ?? '');
            $compressed = str_replace(['"', "'"], '', $compressed);
            $compressed = preg_replace('/\s+/', '', $compressed);
            $cab = preg_replace($pattern, $compressed, $cab);
        } else {
            $cab = preg_replace($pattern, $detalle[0][$valor] ?? '', $cab);
        }

        $pi = preg_replace($pattern, $detalle[0][$valor] ?? '', $pi);
    }

    // 👉 AQUÍ EL CAMBIO IMPORTANTE
    $htmlFinal = $cab . $htmlDetalle . $pi;

    return $htmlFinal;
}
public function ejecutarconsulta($consulta)
    {
   $filas = DB::select($consulta);

    if (count($filas) == 0) {
        return [
            "columnas" => [],
            "filas" => []
        ];
    }

    // convierte los objetos stdClass en arrays
    $filasArray = array_map(function ($row) {
        return (array) $row;
    }, $filas);

    // columnas = keys del primer registro
    $columnas = array_keys($filasArray[0]);

    return [
        "columnas" => $columnas,
        "filas"    => $filasArray
    ];
    }


}
