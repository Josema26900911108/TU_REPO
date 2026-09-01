<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; // Asegúrate de que esta línea esté presente

use App\Models\Materialmanoobra;
use Illuminate\Http\Request;
use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Http\Requests\StoreClienteExistenteRequest;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Documento;
use App\Models\Persona;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Pagotecnico;
use App\Models\Expedientetecnico;
use App\Models\Expedientefotograficotecnico;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class pagotecnicoController  extends Controller
{
    public function __construct()
    {
        // Aplicar middleware de permisos
        $this->middleware('permission:ver-pagotecnico', ['only' => ['index']]);
        $this->middleware('permission:crear-pagotecnico', ['only' => ['create', 'store', 'exist']]);
        $this->middleware('permission:editar-pagotecnico', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-pagotecnico', ['only' => ['destroy']]);

    }

           public function generarExpedientePdf(Request $request)
    {
        // 1. Validar archivo de entrada con órdenes
        if (!$request->hasFile('excel_ordenes')) {
            return back()->with('error', 'No se recibió ningún archivo de órdenes.');
        }

        $file = $request->file('excel_ordenes');
        $path = $file->getRealPath();
        $ordenesRaw = [];
        
        try {
            $spreadsheetInput = IOFactory::load($path);
            $worksheetInput = $spreadsheetInput->getActiveSheet();
            $highestRow = $worksheetInput->getHighestRow();

            for ($row = 2; $row <= $highestRow; $row++) {
                $valorCelda = $worksheetInput->getCell('A' . $row)->getCalculatedValue();
                $valorCelda = trim(preg_replace('/[\s\x{00a0}]+/u', ' ', $valorCelda));
                if ($valorCelda !== '' && !is_null($valorCelda)) {
                    $ordenesRaw[] = (string)$valorCelda;
                }
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error al leer el archivo de órdenes: ' . $e->getMessage());
        }

        $ordenes = array_values(array_unique($ordenesRaw));
        if (empty($ordenes)) {
            return back()->with('error', 'El archivo Excel no contiene órdenes legibles.');
        }

        $tiendaId = session('user_fkTienda');

        // 2. Extraer el logotipo que ya está guardado como Base64 puro en la tabla tienda local
        $tienda = DB::table('tienda')->where('idTienda', $tiendaId)->first();
        $logoBase64Completo = null;
        if ($tienda && !empty($tienda->logo)) {
            $logoBase64Completo = 'data:image/png;base64,' . trim($tienda->logo);
        }

        // 3. Extraer expedientes e información general cruzados con técnicos
        $expedientesRaw = DB::table('expedientetecnico as ex')
            ->leftJoin('tecnico as t', 'ex.fkTecnico', '=', 't.id')
            ->whereIn('ex.Orden', $ordenes)
            ->where('ex.fkTienda', $tiendaId)
            ->select([
                'ex.id', 'ex.Orden', 'ex.virtual', 'ex.Tipo_orden', 'ex.Tipo_servicio',
                'ex.NOMBRECLIENTE', 'ex.DIRECCION', 'ex.FECHAINSTALACION', 'ex.OBS',
                'ex.firma_cliente', 'ex.SIGLASCENTRAL', 'ex.AREA',
                't.nombre as tecnico_nombre', 't.codigo as tecnico_codigo'
            ])
            ->get();

        if ($expedientesRaw->isEmpty()) {
            return back()->with('error', 'No se encontraron expedientes válidos.');
        }

        $expedientesIds = $expedientesRaw->pluck('id')->toArray();
        $nombreBucket = 'sistema-pv-imagenes-tienda';

        // 4. Extraer movimientos haciendo LEFT JOIN con arbolmaterial mediante fkTecnologiaarbol
        $todosMateriales = DB::table('movimientomateriales as mm')
            ->join('MaterialManoObra as mamo', 'mm.SKU', '=', 'mamo.SKU')
            ->leftJoin('arbolmaterial as abmamo', 'mm.fkTecnologiaarbol', '=', 'abmamo.id')
            ->whereIn('mm.fkExpediente', $expedientesIds)
            ->select([
                'mm.fkExpediente', 'mm.SKU', 'mm.cantidad', 'mm.serie', 
                'mamo.Descripcion', 'mamo.TIPO', 'mamo.CATEGORIA', 'mamo.unidadmedida',
                'abmamo.nombre as TecnologiaCatalogo',
                DB::raw("CAST(mamo.CATEGORIACOBRO AS DECIMAL(10,2)) as precio_unitario")
            ])
            ->get();

        // 5. Agrupar la información por cada Tecnología ÚNICA obtenida del Árbol de Materiales
        $tecnologiasAgrupadas = [];
        $materialesPorTecnologia = $todosMateriales->groupBy(function($item) {
            return empty($item->TecnologiaCatalogo) ? 'OTRAS_TECNOLOGIAS' : $item->TecnologiaCatalogo;
        });

        foreach ($materialesPorTecnologia as $nombreTecnologia => $materialesTec) {
            // Obtener qué IDs de expedientes tienen movimientos asignados a ESTA tecnología
            $idsDeEstaTecnologia = $materialesTec->pluck('fkExpediente')->unique()->toArray();
            $listaExpedientes = $expedientesRaw->whereIn('id', $idsDeEstaTecnologia);

            if ($listaExpedientes->isEmpty()) {
                continue;
            }

            // Separar insumos físicos de Mano de Obra
            $materialesFisicosTec = $materialesTec->where('CATEGORIA', '!=', 'MANO DE OBRA');
            $manoObraTec = $materialesTec->where('CATEGORIA', '==', 'MANO DE OBRA');

            // Procesar Cuadro Financiero de Mano de Obra para ESTA tecnología
            $resumenMO = [];
            $totalMO = 0;
            foreach ($manoObraTec->groupBy('Descripcion') as $desc => $itemsMO) {
                $cantTotal = $itemsMO->sum('cantidad');
                $precio = $itemsMO->first()->precio_unitario ?? 0;
                $subtotal = $cantTotal * $precio;
                $totalMO += $subtotal;

                $resumenMO[] = [
                    'descripcion' => $desc,
                    'unidad' => empty($itemsMO->first()->unidadmedida) ? 'UNIDAD' : $itemsMO->first()->unidadmedida,
                    'cantidad' => $cantTotal,
                    'precio' => $precio,
                    'total' => $subtotal
                ];
            }

            $iva = $totalMO * 0.12;
            $totalConIva = $totalMO + $iva;

            // Consolidador global de materiales físicos de esta tecnología
            $materialesGlobales = [];
            foreach ($materialesFisicosTec->groupBy('SKU') as $sku => $itemsMat) {
                $materialesGlobales[] = [
                    'sku' => $sku,
                    'descripcion' => $itemsMat->first()->Descripcion,
                    'cantidad' => $itemsMat->sum('cantidad')
                ];
            }

            // Procesar los expedientes e inyectar firmas digitales de GCS
            $expedientesProcesados = [];
            foreach ($listaExpedientes as $exp) {
                $firmaBase64 = null;
                if (!empty($exp->firma_cliente)) {
                    $urlFirma = $exp->firma_cliente;
                    $pathBucketFirma = $urlFirma;

                    if (str_contains($urlFirma, $nombreBucket)) {
                        $posBucket = strpos($urlFirma, $nombreBucket);
                        $pathBucketFirma = substr($urlFirma, $posBucket + strlen($nombreBucket));
                        $pathBucketFirma = ltrim($pathBucketFirma, '/');
                    } else {
                        $pathBucketFirma = ltrim(parse_url($urlFirma, PHP_URL_PATH), '/');
                    }

                    if (Storage::disk('gcs_images')->exists($pathBucketFirma)) {
                        $binaryFirma = Storage::disk('gcs_images')->get($pathBucketFirma);
                        $type = pathinfo($pathBucketFirma, PATHINFO_EXTENSION);
                        $type = empty($type) ? 'png' : $type;
                        $firmaBase64 = 'data:image/' . $type . ';base64,' . base64_encode($binaryFirma);
                    }
                }

                $expedientesProcesados[] = [
                    'id' => $exp->id,
                    'Orden' => $exp->Orden,
                    'virtual' => $exp->virtual,
                    'Tipo_orden' => $exp->Tipo_orden,
                    'Tipo_servicio' => $exp->Tipo_servicio,
                    'NOMBRECLIENTE' => $exp->NOMBRECLIENTE,
                    'DIRECCION' => $exp->DIRECCION,
                    'FECHAINSTALACION' => $exp->FECHAINSTALACION ? Carbon::parse($exp->FECHAINSTALACION)->format('d/m/Y H:i') : 'N/A',
                    'tecnico_nombre' => $exp->tecnico_nombre,
                    'tecnico_codigo' => $exp->tecnico_codigo,
                    'firma_base64' => $firmaBase64,
                    'materiales' => $materialesFisicosTec->where('fkExpediente', $exp->id)
                ];
            }

            $tecnologiasAgrupadas[$nombreTecnologia] = [
                'nombre' => $nombreTecnologia,
                'resumenManoObra' => $resumenMO,
                'totalManoObra' => $totalMO,
                'iva' => $iva,
                'totalConIva' => $totalConIva,
                'materialesGlobales' => $materialesGlobales,
                'expedientes' => $expedientesProcesados
            ];
        }

        // 6. Configurar y compilar los datos estructurados en DomPDF
        $dataReporte = [
            'fecha_reporte' => Carbon::now()->format('d/m/Y'),
            'periodo_mes' => 'DEL 01 AL 31 DE MAYO del 2026',
            'logo_tienda' => $logoBase64Completo,
            'nombre_tienda' => $tienda->Nombre ?? 'Distribuidor Autorizado',
            'tecnologias' => $tecnologiasAgrupadas
        ];

        $pdf = Pdf::loadView('reportes.expediente_completo_pdf', $dataReporte);
        $pdf->setPaper('letter', 'portrait'); 

        return $pdf->download('Expediente_Masivo_Tecnologias_' . date('Ymd_His') . '.pdf');
    }
public function index(Request $request)
{
    // 1. Iniciar la consulta base sobre el modelo Pagotecnico
    $query = Pagotecnico::with('tienda');

    // 2. Aplicar Filtro por Orden/Expediente si se proporciona
    if ($request->filled('orden')) {
        $query->where('Orden', 'LIKE', '%' . trim($request->input('orden')) . '%');
    }

    // 3. Aplicar Filtro por Técnico (ID o Código)
    if ($request->filled('tecnico_id')) {
        $query->where('fkTecnico', $request->input('tecnico_id'));
    }

    // 4. NUEVO: Aplicar Filtro por Estatus de Pago (Faltaba en tu backend)
    if ($request->filled('Status')) {
        $query->where('Status', $request->input('Status'));
    }

    // 5. Aplicar Filtro por Rango de Fechas (Cambiado a updated_at para coincidir con tu CSV masivo)
    if ($request->filled('fecha_inicio')) {
        $query->whereDate('updated_at', '>=', $request->input('fecha_inicio'));
    }
    if ($request->filled('fecha_fin')) {
        $query->whereDate('updated_at', '<=', $request->input('fecha_fin'));
    }
      $query->orderBy('id', 'desc'); 
    // 6. Obtener los registros filtrados para la tabla
    $pagostecnico = $query->latest()->get();

    // 7. LOGICA DE BALANCES ALGEBRAICOS (Suma 'H' y Resta 'D')
    $calcularBalance = function ($coleccion) {
        return $coleccion->reduce(function ($carry, $pago) {
            $monto = floatval($pago->COSTOPAGO);
            return $pago->Naturaleza === 'D' ? $carry - $monto : $carry + $monto;
        }, 0);
    };

    // Balance General (Todo lo que arrojó el filtro actual)
    $totalBalance = $calcularBalance($pagostecnico);

    // Balances específicos filtrados desde la colección en memoria
    $balanceC = $calcularBalance($pagostecnico->where('Status', 'C'));
    $balanceS = $calcularBalance($pagostecnico->where('Status', 'S'));
    $balanceB = $calcularBalance($pagostecnico->where('Status', 'B'));

    // Obtener lista de técnicos para el select del filtro
    $tecnicos = DB::table('tecnico')->select('id', 'nombre')->where('fkTienda', session('user_fkTienda'))->get();
  
    // 8. Retornar todas las variables calculadas a la vista
    return view('pagotecnicos.index', compact(
        'pagostecnico', 
        'totalBalance', 
        'balanceC', 
        'balanceS', 
        'balanceB', 
        'tecnicos'
    ));
}



public function movimiento(Request $request)
{
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    try {
        DB::beginTransaction();

        // Registrar el movimiento en la tabla 'movimientomateriales'
        DB::table('movimientomateriales')->insert([
            'fkTienda' => session('user_fkTienda'),
             'fkTecnico' => $request->fkTecnico,
             'Orden' => $request->Orden,
             'SKU' => $request->SKU,
             'Descripcion' => $request->Descripcion,
             'OBS' => $request->OBS,
             'clase_movimiento' => '311', // Código estándar para traslados
             'tipo_movimiento' => 'TRASLADO',
             'origen_uso' => 'traslado_entre_bodegas',
             'cantidad' => $request->cantidad,
             'fecha_contabilizacion' => now(),
             'created_at' => now(),
             'updated_at' => now(),
            // Agrega aquí los campos de 'centro' o 'almacen' según tus modelos de Centros
        ]);

        // Registrar el pago técnico en la tabla 'pagotecnico'
        DB::table('pagotecnico')->insert([
            'fkTienda' => session('user_fkTienda'),
            'fkTecnico' => $request->fkTecnico,
            'Orden' => $request->Orden,
            'SKU' => $request->SKU,
            'Descripcion' => $request->Descripcion,
            'OBS' => $request->OBS,
            'Naturaleza' => $request->Naturaleza,
            'COSTOPAGO' => $request->COSTOPAGO,
            'Status' => $request->Status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::commit();
        return redirect()->back()->with('success', 'Movimiento registrado exitosamente.');
    } catch (\Exception $e) {
        DB::rollback();
        return redirect()->back()->with('error', 'Error al registrar el movimiento.');
    }
}
public function generarMemoriaFotografica(Request $request)
{
    if (!$request->hasFile('excel_ordenes')) {
        return back()->with('error', 'No se recibió ningún archivo de órdenes.');
    }

    $file = $request->file('excel_ordenes');
    $path = $file->getRealPath();
    $ordenesRaw = [];
    
    try {
        $spreadsheetLoad = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $worksheetLoad = $spreadsheetLoad->getActiveSheet();
        $highestRow = $worksheetLoad->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $valorCelda = $worksheetLoad->getCell('A' . $row)->getCalculatedValue();
            $valorCelda = trim(preg_replace('/[\s\x{00a0}]+/u', ' ', $valorCelda));
            if ($valorCelda !== '' && !is_null($valorCelda)) {
                $ordenesRaw[] = (string)$valorCelda;
            }
        }
    } catch (\Exception $e) {
        return back()->with('error', 'Error al leer el archivo de órdenes: ' . $e->getMessage());
    }

    $ordenes = array_values(array_unique($ordenesRaw));
    if (empty($ordenes)) {
        return back()->with('error', 'El archivo no contiene órdenes legibles.');
    }

    // Palabras reservadas solicitadas
    $palabrasClave = ['antena', 'conectividad', 'mastil', 'switch', 'poste antes', 'poste despues', 'anillo postes'];

    // Consultar el universo fotográfico cruzando con el árbol de tecnología
    $fotografiasUniverso = DB::table('expedientefotograficotecnico as ef')
        ->leftJoin('arbolmanoobra as am', 'ef.fkTecnologia', '=', 'am.id')
        ->whereIn('ef.Orden', $ordenes)
        ->select(['ef.*', 'am.nombre as nombre_tecnologia'])
        ->get();

    if ($fotografiasUniverso->isEmpty()) {
        return back()->with('error', 'No se encontraron evidencias fotográficas para las órdenes suministradas.');
    }

    $fotosPorTecnologia = $fotografiasUniverso->groupBy('nombre_tecnologia');

    $zipFileName = 'Memorias_Fotograficas_' . date('Ymd_His') . '.zip';
    $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFileName;
    $zip = new ZipArchive;
    $imagenesTemporalesABorrar = [];

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return back()->with('error', 'No se pudo inicializar el empaquetador ZIP.');
    }
        $nombreBucket = 'sistema-pv-imagenes-tienda';

    foreach ($fotosPorTecnologia as $tecnologiaNombre => $fotosTecnologia) {
        
        $techClean = empty($tecnologiaNombre) ? 'OTRAS_TECNOLOGIAS' : str_replace(['/', '\\', '?', '*', ':', '[', ']'], '_', $tecnologiaNombre);
        $spreadsheet = new Spreadsheet();
        $sheetIndex = 0;

        foreach ($palabrasClave as $palabra) {
            
            // Filtrar las fotos por coincidencia con la palabra clave
            $fotosFiltradas = $fotosTecnologia->filter(function ($f) use ($palabra) {
                return str_contains(strtolower($f->fotografia), strtolower($palabra));
            });

            if ($fotosFiltradas->isEmpty()) {
                continue;
            }

            if ($sheetIndex === 0) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            $tituloPestana = substr(ucwords($palabra) . ' ' . strtoupper($techClean), 0, 31);
            $sheet->setTitle($tituloPestana);

            // --- DISEÑO DE ENCABEZADOS SUPERIORES ---
            $sheet->mergeCells('B2:I2');
            $sheet->setCellValue('B2', 'MEMORIA FOTOGRAFICA');
            $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('B3:I3');
            $sheet->setCellValue('B3', strtoupper($palabra) . ' - SERVICIOS ' . strtoupper($techClean));
            $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('B5', 'DATOS DE LA OBRA:');
            $sheet->getStyle('B5')->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('B5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('000000');
            $sheet->getStyle('B5')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));

            $sheet->setCellValue('B6', 'DIVISION:');      $sheet->setCellValue('C6', strtoupper($techClean));
            $sheet->setCellValue('B7', 'NOMBRE DEL COORDINADOR:'); $sheet->setCellValue('C7', 'Carlos Oliva');
            $sheet->setCellValue('B8', 'NOMBRE DEL CONTRATISTA:'); $sheet->setCellValue('C8', 'SIC');
            
            $sheet->setCellValue('E6', 'AREA:');          $sheet->setCellValue('F6', 'OCCIDENTE');
            $sheet->setCellValue('E7', 'FECHA INICIO:');   $sheet->setCellValue('F7', '01/05/2026');
            $sheet->setCellValue('E8', 'FECHA TERMINACION:'); $sheet->setCellValue('F8', '31/05/2026');
            
            $sheet->getStyle('B6:B8')->getFont()->setBold(true);
            $sheet->getStyle('E6:E8')->getFont()->setBold(true);
            $sheet->getStyle('B6:I8')->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            // --- DISTRIBUCIÓN DE IMÁGENES EN 3 COLUMNAS ---
            $columnaLetras = ['B', 'E', 'H']; 
            $fotoIndex = 0;
            $filaBaseFotos = 11; 

            foreach ($fotosFiltradas as $fotoItem) {
                
                $subColIndex = $fotoIndex % 3;
                $lineaMultiplo = floor($fotoIndex / 3);
                
                $filaImagenInicio = $filaBaseFotos + ($lineaMultiplo * 16);
                $filaImagenFin    = $filaImagenInicio + 11;
                $filaOrdenTexto   = $filaImagenFin + 1;

                $colLetra = $columnaLetras[$subColIndex];
                $colSiguiente = $subColIndex == 0 ? 'C' : ($subColIndex == 1 ? 'F' : 'S');

                $urlCompleta = $fotoItem->fotografia;
                $pathBucket = $urlCompleta;

                if (str_contains($urlCompleta, $nombreBucket)) {
                    $posicionBucket = strpos($urlCompleta, $nombreBucket);
                    $pathBucket = substr($urlCompleta, $posicionBucket + strlen($nombreBucket));
                    $pathBucket = ltrim($pathBucket, '/');
                } else {
                    $pathBucket = ltrim(parse_url($urlCompleta, PHP_URL_PATH), '/');
                }

                if (Storage::disk('gcs_images')->exists($pathBucket)) {
                    
                    $imageBinary = Storage::disk('gcs_images')->get($pathBucket);
                    $tempImageName = 'temp_img_' . uniqid() . '.jpg';
                    $tempImagePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tempImageName;
                    
                    file_put_contents($tempImagePath, $imageBinary);

                    $drawing = new Drawing();
                    $drawing->setName('Evidencia_' . $fotoItem->Orden);
                    $drawing->setPath($tempImagePath);
                    $drawing->setHeight(190); // Alto fijo para mantener el recuadro simétrico
                    $drawing->setCoordinates($colLetra . $filaImagenInicio);
                    $drawing->setOffsetX(10);
                    $drawing->setOffsetY(5);
                    $drawing->setWorksheet($sheet);

                    $imagenesTemporalesABorrar[] = $tempImagePath;
                }

                // Dibujar marco e información de la Orden técnica
                $sheet->getStyle("{$colLetra}{$filaImagenInicio}:{$colSiguiente}{$filaImagenFin}")
                      ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $sheet->setCellValue($colLetra . $filaOrdenTexto, 'ORDEN');
                $sheet->getStyle($colLetra . $filaOrdenTexto)->getFont()->setBold(true);
                
                $sheet->setCellValue($colSiguiente . $filaOrdenTexto, $fotoItem->Orden);
                $sheet->getStyle($colSiguiente . $filaOrdenTexto)->getFont()->setFontFamily('Courier New');
                $sheet->getStyle("{$colLetra}{$filaOrdenTexto}:{$colSiguiente}{$filaOrdenTexto}")
                      ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $fotoIndex++;
            }

            // Ancho simétrico de columnas
            $sheet->getColumnDimension('B')->setWidth(16); $sheet->getColumnDimension('C')->setWidth(16);
            $sheet->getColumnDimension('E')->setWidth(16); $sheet->getColumnDimension('F')->setWidth(16);
            $sheet->getColumnDimension('H')->setWidth(16); $sheet->getColumnDimension('S')->setWidth(16);
            
            $sheetIndex++;
        }

        // Si el libro acumuló evidencias válidas, se integra a la raíz del ZIP
        if ($sheetIndex > 0) {
            $writer = new Xlsx($spreadsheet);
            $excelPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Memoria_Fotografica_' . $techClean . '.xlsx';
            $writer->save($excelPath);
            $zip->addFile($excelPath, 'Memoria_Fotografica_' . $techClean . '.xlsx');
        }
    }
    $zip->close();

    // Eliminar del almacenamiento local los archivos temporales utilizados para dibujar en las celdas
    if (!empty($imagenesTemporalesABorrar)) {
        foreach ($imagenesTemporalesABorrar as $p) {
            if (file_exists($p)) { 
                @unlink($p); 
            }
        }
    }

    if (!file_exists($zipPath) || filesize($zipPath) <= 22) {
        @unlink($zipPath);
        return back()->with('error', 'No se generaron memorias fotográficas. Ninguna imagen cumplió las condiciones.');
    }

    // Iniciar transmisión del ZIP y destruirlo tras completarse la descarga
    return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
}


public function exportarFotosZip(Request $request)
{
    // 1. Recoger los filtros desde el Request
    $fkTienda    = session('user_fkTienda') ?? 0;
    $orden       = $request->input('orden');
    $tecnicoId   = $request->input('tecnico_id');
    $fechaInicio = $request->input('fecha_inicio');
    $fechaFin    = $request->input('fecha_fin');

    // 2. Filtrar los expedientes en la base de datos
    $queryExpedientes = Expedientetecnico::query();

    if ($fkTienda) {
        $queryExpedientes->where('fkTienda', $fkTienda);
    }
    if (!empty($orden)) {
        $queryExpedientes->where('Orden', 'LIKE', '%' . trim($orden) . '%');
    }
    if (!empty($tecnicoId)) {
        $queryExpedientes->where('fkTecnico', $tecnicoId);
    }
    if (!empty($fechaInicio)) {
        $queryExpedientes->whereDate('FECHAINSTALACION', '>=', $fechaInicio);
    }
    if (!empty($fechaFin)) {
        $queryExpedientes->whereDate('FECHAINSTALACION', '<=', $fechaFin);
    }

    $ordenesFiltradas = $queryExpedientes->pluck('Orden')->toArray();

    if (empty($ordenesFiltradas)) {
        return back()->with('error', 'No se encontraron expedientes bajo los filtros seleccionados.');
    }

    // 3. Buscar las fotografías ligadas a esas órdenes
    $fotografias = Expedientefotograficotecnico::whereIn('Orden', $ordenesFiltradas)->get();

    if ($fotografias->isEmpty()) {
        return back()->with('error', 'Los expedientes filtrados no cuentan con evidencias fotográficas.');
    }

    // 4. Configurar el archivo ZIP en el directorio temporal del sistema operativo (XAMPP)
    $zipFileName = 'Evidencias_Bucket_' . date('Ymd_His') . '.zip';
    $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFileName; 
    
    $zip = new ZipArchive;

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        foreach ($fotografias as $foto) {
            
            $urlCompleta = $foto->fotografia;
            $pathBucket = $urlCompleta;

            // =================================================================
            // LIMPIEZA ABSOLUTA DE RUTA PARA EL DISCO: gcs_images
            // =================================================================
            $nombreBucket = 'sistema-pv-imagenes-tienda';

            if (str_contains($urlCompleta, $nombreBucket)) {
                // Cortar la cadena justo después de que termine el nombre del bucket
                $posicionBucket = strpos($urlCompleta, $nombreBucket);
                $pathBucket = substr($urlCompleta, $posicionBucket + strlen($nombreBucket));
                $pathBucket = ltrim($pathBucket, '/'); // Remueve diagonales iniciales (ej: "/productos/123.jpg" -> "productos/123.jpg")
            } else {
                // Si ya es una ruta relativa, limpiamos protocolo y dominios usando parse_url
                $pathBucket = ltrim(parse_url($urlCompleta, PHP_URL_PATH), '/');
            }

            // =================================================================
            // EXTRACCIÓN CONECTADA DIRECTAMENTE A TU DISCO GOOGLE BUCKET
            // =================================================================
            // Laravel busca la ruta $pathBucket (ej: "productos/1779159067_62055.jpg") dentro del bucket asignado a gcs_images
            if (Storage::disk('gcs_images')->exists($pathBucket)) {
                
                // Descarga el binario del servidor de Google al servidor local de XAMPP de forma interna
                $imageContent = Storage::disk('gcs_images')->get($pathBucket);
                
                // Obtener el nombre del archivo con su extensión original
                $nombreArchivoOriginal = pathinfo($pathBucket, PATHINFO_BASENAME);
                
                // Clasificar el ZIP creando una subcarpeta interna por cada Número de Orden Técnica
                $nombreArchivoInterno = "Orden_{$foto->Orden}/" . $nombreArchivoOriginal;
                
                $zip->addFromString($nombreArchivoInterno, $imageContent);
            }
        }
        $zip->close();
    } else {
        return back()->with('error', 'No se pudo inicializar el motor de compresión ZIP en el servidor.');
    }

    // 5. Validación final del peso y existencia del ZIP armado
    if (!file_exists($zipPath) || filesize($zipPath) <= 22) { 
        @unlink($zipPath);
        return back()->with('error', 'El archivo comprimido se generó vacío. Comprueba que las columnas apunten a los archivos existentes en tu disco gcs_images.');
    }

    // Iniciar transferencia masiva al navegador y purgar el archivo de XAMPP automáticamente
    return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
}
public function descargarFormatoPago()
{
    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=Formato_Desglose_Pagos_Tecnicos.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columnas = [
        'Orden', 'SKU', 'Descripcion', 'OBS', 'Cantidad',
        'COSTOPAGO', 'Status', 'Naturaleza', 'fkTecnico', 'fkTienda'
    ];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        
        // Añadir el BOM UTF-8 para que Excel reconozca los acentos correctamente
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // === CONFIGURACIÓN SEGURA DE COMAS ===
        fputcsv($file, $columnas, ',', '"'); // Delimitador: , Enclosure: "

        fputcsv($file, [
            'ORD-100254',
            'SKU-MO-001',
            'INSTALACION TRIPLE PLAY TV VOZ Y DATOS', // Texto protegido contra rupturas
            'Instalación Caja Adicional Servicio Técnico',            
            1,
            250.00,
            'C', 
            'H', 
            12,
            5
        ], ',', '"'); // Delimitador: , Enclosure: "

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

public function descargarFormatoModificacionPago()
{
    // Limpiar cualquier residuo en el búfer de salida para evitar que XAMPP rompa el CSV
    if (ob_get_level()) {
        ob_end_clean();
    }

    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=Formato_Modificacion_Pagos_Tecnicos.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columnas = [
        'id',
        'Orden',
        'SKU',
        'Descripcion',
        'OBS',
        'Cantidad',
        'COSTOPAGO',
        'Status',
        'Naturaleza',
        'fkTecnico'
    ];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        
        // BOM UTF-8 para que Excel en Windows abra los acentos sin problemas
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($file, $columnas);

        // Fila de ejemplo
        fputcsv($file, [
            '1548', 'ORD-100254', 'SKU-MO-001', 'INSTALACION TOMA DE LINEA RJ11', 
            'Actualizacion', '1.00', '275.50', 'C', 'H', '12'
        ]);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}


public function descargarinventariopago()
{
    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=Formato_Expediente_Pago_Tecnico.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    // Columnas ordenadas de acuerdo a la estructura de tu tabla 'pagotecnico'
    $columnas = [
        'Orden',
        'SKU',
        'Descripcion',
        'OBS',
        'Cantidad',
        'COSTOPAGO',
        'fkTienda',
        'fkTecnico',
        'Naturaleza',
        'Status'
    ];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        
        // Inyectar BOM UTF-8 para evitar errores de codificación en Excel
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($file, $columnas); // encabezado

        $fkTienda = session('user_fkTienda') ?? 1;

        // Línea de ejemplo adaptada fielmente a los tipos de columnas de tu base de datos:
        fputcsv($file, [
            'ORD-23450285',                // Orden (varchar)
            '4013896',                     // SKU (varchar)
            'Mano de Obra Instalación',    // Descripcion (text)
            'Pago correspondiente a cajas adicionales', // OBS (text)
            1.00,                          // Cantidad (double)
            350.00,                        // COSTOPAGO (double)
            $fkTienda,                     // fkTienda (bigint)
            12,                            // fkTecnico (bigint)
            'D',                           // Naturaleza (char 1 - ej: D o H)
            'S'                            // Status (char 2)
        ]);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

public function exportarExcel(Request $request)
{
    // 1. Capturar exactamente los mismos filtros de tu consulta de la tabla
    $fkTienda    = session('user_fkTienda') ?? 0;
    $orden       = $request->input('orden');
    $tecnicoId   = $request->input('tecnico_id');
    $fechaInicio = $request->input('fecha_inicio');
    $fechaFin    = $request->input('fecha_fin');

    // 2. Consulta idéntica aplicando los filtros acumulativos
    $query = Pagotecnico::query();

    if ($fkTienda) {
        $query->where('fkTienda', $fkTienda);
    }

    if (!empty($orden)) {
        $query->where('Orden', 'LIKE', '%' . trim($orden) . '%');
    }

    if (!empty($tecnicoId)) {
        $query->where('fkTecnico', $tecnicoId);
    }

    if (!empty($fechaInicio)) {
        $query->whereDate('created_at', '>=', $fechaInicio);
    }

    if (!empty($fechaFin)) {
        $query->whereDate('created_at', '<=', $fechaFin);
    }

    // 3. Configurar cabeceras de descarga HTTP para Excel/CSV
    $fileName = 'Reporte_Desglose_Pagos_' . date('Y-m-d_H-i') . '.csv';
    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    // 4. Generar el archivo en streaming línea por línea para cuidar la RAM
    $callback = function() use ($query) {
        $file = fopen('php://output', 'w');
        
        // Agregar BOM UTF-8 para que Excel reconozca correctamente las tildes y eñes
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Cabeceras adaptadas a las columnas reales de pagotecnico
        fputcsv($file, ['ID Pago', 'Orden / Expediente', 'SKU', 'Descripcion', 'Cantidad', 'Costo Pago ($)', 'Naturaleza', 'Estatus', 'ID Tienda', 'ID Técnico', 'Fecha Registro']);

        // Variable para ir acumulando el balance total algebraico del reporte
        $totalBalance = 0;

        // Procesar los registros en bloques de 1000 para no colapsar la memoria (Chunking)
        $query->chunk(1000, function($registros) use ($file, &$totalBalance) {
            foreach ($registros as $row) {
                $monto = floatval($row->COSTOPAGO);
                $naturaleza = strtoupper(trim($row->Naturaleza));

                // Lógica algebraica: D resta, H suma
                if ($naturaleza === 'D') {
                    $totalBalance -= $monto;
                } elseif ($naturaleza === 'H') {
                    $totalBalance += $monto;
                }

                fputcsv($file, [
                    $row->id,
                    $row->Orden,
                    $row->SKU,
                    $row->Descripcion,
                    $row->Cantidad,
                    number_format($monto, 2, '.', ''), // Formato numérico limpio para Excel
                    $naturaleza,
                    $row->Status,
                    $row->fkTienda,
                    $row->fkTecnico,
                    date('d-m-Y H:i:s', strtotime($row->created_at))
                ]);
            }
        });

        // 5. INYECTAR FILA DE RESULTADO FINAL BALANCEADO EN EL EXCEL
        fputcsv($file, []); // Renglón vacío de separación
        fputcsv($file, [
            'RESULTADO GENERAL BALANCEADO:', 
            '', '', '', '', 
            number_format($totalBalance, 2, '.', ''), // Imprime el total abajo de Costo Pago
            'Suma total (Abonos H menos Cargos D)', 
            '', '', ''
        ]);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

public function importarPagosTecnico(Request $request)
{
    // Desactivar logs de consultas inmediatamente para proteger la memoria RAM
    DB::connection()->disableQueryLog(); 

    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $fkTienda = session('user_fkTienda') ?? 0;
    
    // Configuraciones de alto rendimiento para procesamiento masivo
    set_time_limit(0); 
    ini_set('memory_limit', '512M');

    $request->validate([
        'archivo' => 'required|file|mimes:csv,txt,xlsx',
    ]);

    $path = $request->file('archivo')->getRealPath();
    $file = fopen($path, 'r');
    
    // 1. DETECCIÓN AUTOMÁTICA MEJORADA (Soporta Tabuladores, Comas y Puntos y comas)
    $primeraLinea = fgets($file);
    
    // Si contiene un tabulador, asignamos "\t". Si contiene ';', asignamos ';'. De lo contrario ','
    if (strpos($primeraLinea, "\t") !== false) {
        $delimitador = "\t";
    } elseif (strpos($primeraLinea, ';') !== false) {
        $delimitador = ';';
    } else {
        $delimitador = ',';
    }
    
    $enclosure = '"';
    
    // Volver al inicio del archivo para procesar formalmente
    rewind($file);

    // 2. LEER EL ENCABEZADO CON EL DELIMITADOR DETECTADO
    $encabezadoRaw = fgetcsv($file, 0, $delimitador, $enclosure);
    
    // Limpiar el posible carácter invisible BOM UTF-8 del inicio si existe
    if ($encabezadoRaw && isset($encabezadoRaw[0]) && str_contains($encabezadoRaw[0], chr(0xEF).chr(0xBB).chr(0xBF))) {
        $encabezadoRaw[0] = str_replace(chr(0xEF).chr(0xBB).chr(0xBF), '', $encabezadoRaw[0]);
    }
    
    // Sanitizar nombres de columnas (Quitar espacios, comillas accidentales y normalizar a UTF-8)
    $encabezado = array_map(function($col) {
        $colClean = trim(str_replace(['"', "'"], '', $col));
        return mb_convert_encoding($colClean, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    }, $encabezadoRaw);


    $insertados = 0;
    $omitidos = 0;
    $batchSize = 200; // Bloques óptimos para evitar bloqueos en el servidor
    $batchData = [];
    $now = now();

    try {
        // 3. CICLO PRINCIPAL DE LECTURA
        while (($lineaRaw = fgetcsv($file, 0, $delimitador, $enclosure)) !== false) {
            
            // Omitir de forma segura líneas que resulten completamente vacías
            if (empty($lineaRaw) || (count($lineaRaw) === 1 && $lineaRaw[0] === null)) {
                continue;
            }

            // Sanitizar y reparar la codificación corrupta (Acentos/Eñes) de la fila antes de procesar
            $lineaLimpia = array_map(function($campo) {
                if ($campo === null || $campo === '') return '';
                return mb_convert_encoding(trim($campo), 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
            }, $lineaRaw);

            // Validar consistencia estructural de columnas
            if (count($encabezado) !== count($lineaLimpia)) {
                // Registra la fila en storage/logs/laravel.log para auditoría si llega a fallar
                \Log::warning("Fila omitida por discrepancia de columnas. Cabecera: " . count($encabezado) . " | Fila: " . count($lineaLimpia), [
                    'fila_datos' => $lineaLimpia
                ]);
                $omitidos++;
                continue;
            }

            // Combinar la cabecera limpia con los datos limpios de la fila
            $data = array_combine($encabezado, $lineaLimpia);

            // Validaciones estrictas de campos obligatorios
            $orden = trim($data['Orden'] ?? '');
            $sku = trim($data['SKU'] ?? '');

            if (empty($orden) || empty($sku)) {
                $omitidos++;
                continue;
            }

            // Normalización del campo Naturaleza (D / H)
            $naturaleza = strtoupper(trim($data['Naturaleza'] ?? 'D'));
            if (!in_array($naturaleza, ['D', 'H'])) {
                $naturaleza = 'D'; 
            }

            // Procesamiento dinámico de costos contables
            $valorcosto = isset($data['COSTOPAGO']) ? floatval(trim($data['COSTOPAGO'])) : 0.00;

            if ($valorcosto == 0) {
                $tecnicoId = intval($data['fkTecnico'] ?? 0);
                $tecnicoCodigo = '';
                
                if ($tecnicoId > 0) {
                    $tecnicoCodigo = DB::table('tecnico')->where('id', $tecnicoId)->value('codigo') ?? '';
                }

                // Búsqueda jerárquica en el catálogo de materiales y mano de obra
                $obtenervalor = Materialmanoobra::where('SKU', $sku)
                    ->where(function ($query) use ($fkTienda, $tecnicoCodigo) {
                        $query->where('centrocostoespecifico', '=', $tecnicoCodigo)
                              ->orWhere('centrocostoespecifico', '=', $fkTienda)
                              ->orWhereNull('centrocostoespecifico')
                              ->orWhere('centrocostoespecifico', '=', '');
                    })
                    ->select('CATEGORIACOBRO', 'COSTOPAGO', 'CATEGORIA', 'TIPO')
                    ->orderByRaw("CASE 
                        WHEN centrocostoespecifico = ? AND ? != '' THEN 1
                        WHEN centrocostoespecifico = ? THEN 2
                        ELSE 3 
                    END ASC", [$tecnicoCodigo, $tecnicoCodigo, $fkTienda])
                    ->latest()
                    ->first();

                if ($obtenervalor) {
                    if ($obtenervalor->CATEGORIA === 'MANO DE OBRA' || $obtenervalor->TIPO === 'MANO DE OBRA') {
                        $valorcosto = floatval($obtenervalor->COSTOPAGO);
                    } elseif ($obtenervalor->CATEGORIA === 'MATERIAL' || $obtenervalor->TIPO === 'MATERIAL') {
                        $valorcosto = floatval($obtenervalor->CATEGORIACOBRO);
                    } else {
                        $valorcosto = floatval($obtenervalor->COSTOPAGO ?? 0.00);
                    }
                }
            }

            $status = substr(trim($data['Status'] ?? 'S'), 0, 2);

            // Estructuración del registro para el lote
            $batchData[] = [
                'Orden'       => $orden,
                'SKU'         => $sku,
                'Descripcion' => $data['Descripcion'] ?? '', 
                'OBS'         => $data['OBS'] ?? 'Importacion masiva', 
                'Cantidad'    => isset($data['Cantidad']) ? floatval($data['Cantidad']) : 1.00,
                'COSTOPAGO'   => $valorcosto,
                'fkTienda'    => $fkTienda,
                'fkTecnico'   => intval($data['fkTecnico'] ?? 0),
                'Naturaleza'  => $naturaleza,
                'Status'      => $status,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];

            $insertados++;

            // Ejecución segura por bloques usando Sincronización updateOrInsert
            if (count($batchData) >= $batchSize) {
                DB::transaction(function () use ($batchData) {
                    foreach ($batchData as $row) {
                        DB::table('pagotecnico')->updateOrInsert(
                            ['Orden' => $row['Orden'], 'SKU' => $row['SKU']], 
                            $row 
                        );
                    }
                });
                $batchData = [];
                gc_collect_cycles(); // Liberación de memoria interna de PHP
            }
        }

        // 4. PROCESAR REGISTROS REMANENTES FINALES
        if (!empty($batchData)) {
            DB::transaction(function () use ($batchData) {
                foreach ($batchData as $row) {
                    DB::table('pagotecnico')->updateOrInsert(
                        ['Orden' => $row['Orden'], 'SKU' => $row['SKU']],
                        $row
                    );
                }
            });
        }

        fclose($file);

        return back()->with('success', "Importación de pagos exitosa: {$insertados} filas procesadas y sincronizadas, {$omitidos} omitidas por inconsistencias.");

    } catch (\Exception $e) {
        if (is_resource($file)) {
            fclose($file);
        }
        return back()->with('error', 'Error crítico en procesamiento de archivo: ' . $e->getMessage());
    }
}

public function modificarPagosTecnicoMasivo(Request $request)
{
    DB::connection()->disableQueryLog(); 
    
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $fkTienda = session('user_fkTienda') ?? 0;    
    
    set_time_limit(0); 
    ini_set('memory_limit', '512M');    

    $request->validate([
        'archivo' => 'required|file|mimes:csv,txt',
    ]);

    $path = $request->file('archivo')->getRealPath();

    // =================================================================
    // DETECTOR AUTOMÁTICO Y DINÁMICO DE DELIMITADORES
    // =================================================================
    $delimitador = ","; // Por defecto
    $delimitadoresPosibles = [",", ";", "\t", "|"];
    $fileCheck = fopen($path, 'r');
    if ($fileCheck) {
        $primeraLinea = fgets($fileCheck);
        fclose($fileCheck);
        
        if ($primeraLinea !== false) {
            $conteos = [];
            foreach ($delimitadoresPosibles as $del) {
                // Cuenta cuántas veces aparece cada delimitador en la primera línea
                $conteos[$del] = substr_count($primeraLinea, $del);
            }
            // Selecciona el delimitador que más veces aparezca
            arsort($conteos);
            $delimitador = key($conteos);
            
            // Si el archivo no tiene delimitadores válidos, regresa al fallback
            if ($conteos[$delimitador] === 0) {
                $delimitador = ",";
            }
        }
    }
    // =================================================================

    $file = fopen($path, 'r');    
    
    // Leer encabezado usando el delimitador autodetectado
    $encabezadoRaw = fgetcsv($file, 0, $delimitador);
    if ($encabezadoRaw && str_contains($encabezadoRaw[0], chr(0xEF).chr(0xBB).chr(0xBF))) {
        $encabezadoRaw[0] = str_replace(chr(0xEF).chr(0xBB).chr(0xBF), '', $encabezadoRaw[0]);
    }
    
    // Limpieza estricta de caracteres invisibles en el encabezado
    $encabezado = array_map(function($val) {
        return trim(preg_replace('/[\x00-\x1F\x7F-\x9F\xA0]/u', '', $val));
    }, $encabezadoRaw);

    $actualizados = 0;
    $omitidos = 0;   
    $batchSize = 500; 
    $batchData = [];
    $now = now();

    try {
        // Leer cada línea usando el delimitador autodetectado
        while (($linea = fgetcsv($file, 0, $delimitador)) !== false) {
            
            // Ignorar líneas completamente vacías
            if (count($linea) === 1 && empty(trim($linea[0]))) {
                continue;
            }

            if (count($encabezado) !== count($linea)) {
                $omitidos++;
                continue;
            }

            $data = array_combine($encabezado, $linea);

            // Limpiar el ID de espacios antes de validarlo
            $idLimpio = isset($data['id']) ? trim($data['id']) : '';

            // SOPORTE DE ENCABEZADO MULTI-CASO (id u Orden en Automa)
            if (empty($idLimpio) && isset($data['Orden'])) {
                $idLimpio = trim($data['Orden']);
            }

            if (empty($idLimpio)) {
                $omitidos++;
                continue;
            }

            $naturaleza = strtoupper(trim($data['Naturaleza'] ?? 'D'));
            if (!in_array($naturaleza, ['D', 'H'])) {
                $naturaleza = 'D'; 
            }

            $valorcosto = floatval($data['COSTOPAGO'] ?? 0.00);

            if ($valorcosto == 0 && !empty($data['SKU'])) {
                $tecnicoId = intval($data['fkTecnico'] ?? 0);
                $tecnicoCodigo = '';
                
                if ($tecnicoId > 0) {
                    $tecnicoCodigo = DB::table('tecnico')->where('id', $tecnicoId)->value('codigo') ?? '';
                }

                $obtenervalor = DB::table('MaterialManoObra')
                    ->where('SKU', $data['SKU'])
                    ->where(function ($query) use ($fkTienda, $tecnicoCodigo) {
                        $query->where('centrocostoespecifico', '=', $tecnicoCodigo)
                              ->orWhere('centrocostoespecifico', '=', $fkTienda)
                              ->orWhereNull('centrocostoespecifico')
                              ->orWhere('centrocostoespecifico', '=', '');
                    })
                    ->select('CATEGORIACOBRO', 'COSTOPAGO', 'CATEGORIA', 'TIPO', 'centrocostoespecifico')
                    ->orderByRaw("CASE 
                        WHEN centrocostoespecifico = ? AND ? != '' THEN 1
                        WHEN centrocostoespecifico = ? THEN 2
                        ELSE 3 
                    END ASC", [$tecnicoCodigo, $tecnicoCodigo, $fkTienda])
                    ->latest()
                    ->first();

                if ($obtenervalor) {
                    if ($obtenervalor->CATEGORIA === 'MANO DE OBRA' || $obtenervalor->TIPO === 'MANO DE OBRA') {
                        $data['COSTOPAGO'] = floatval($obtenervalor->COSTOPAGO);
                    } elseif ($obtenervalor->CATEGORIA === 'MATERIAL' || $obtenervalor->TIPO === 'MATERIAL') {
                        $data['COSTOPAGO'] = floatval($obtenervalor->CATEGORIACOBRO);
                    } else {
                        $data['COSTOPAGO'] = floatval($obtenervalor->COSTOPAGO ?? 0.00);
                    }
                } else {
                    $data['COSTOPAGO'] = 0.00;
                }
            } else {
                $data['COSTOPAGO'] = $valorcosto;
            }

            $status = substr(trim($data['Status'] ?? 'S'), 0, 2);

            $batchData[] = [
                'id'          => intval($idLimpio), 
                'Orden'       => $data['Orden'] ?? null,
                'SKU'         => $data['SKU'] ?? null,
                'Descripcion' => mb_convert_encoding($data['Descripcion'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'OBS'         => mb_convert_encoding($data['OBS'] ?? 'Modificación masiva por CSV', 'UTF-8', 'ISO-8859-1'),
                'Cantidad'    => isset($data['Cantidad']) ? floatval($data['Cantidad']) : 1.00,
                'COSTOPAGO'   => floatval($data['COSTOPAGO']),
                'Naturaleza'  => $naturaleza,
                'Status'      => $status,
                'updated_at'  => $now,
            ];

            $actualizados++;

            if (count($batchData) >= $batchSize) {
                DB::transaction(function () use ($batchData) {
                    foreach ($batchData as $row) {
                        DB::table('pagotecnico')
                            ->where('id', $row['id'])
                            ->update([
                                'Orden'       => $row['Orden'],
                                'SKU'         => $row['SKU'],
                                'Descripcion' => $row['Descripcion'],
                                'OBS'         => $row['OBS'],
                                'Cantidad'    => $row['Cantidad'],
                                'COSTOPAGO'   => $row['COSTOPAGO'],
                                'Naturaleza'  => $row['Naturaleza'],
                                'Status'      => $row['Status'],
                                'updated_at'  => $row['updated_at']
                            ]);
                    }
                });
                $batchData = [];
                gc_collect_cycles(); 
            }
        }

        if (!empty($batchData)) {
            DB::transaction(function () use ($batchData) {
                foreach ($batchData as $row) {
                    DB::table('pagotecnico')
                        ->where('id', $row['id'])
                        ->update([
                            'Orden'       => $row['Orden'],
                            'SKU'         => $row['SKU'],
                            'Descripcion' => $row['Descripcion'],
                            'OBS'         => $row['OBS'],
                            'Cantidad'    => $row['Cantidad'],
                            'COSTOPAGO'   => $row['COSTOPAGO'],
                            'Naturaleza'  => $row['Naturaleza'],
                            'Status'      => $row['Status'],
                            'updated_at'  => $row['updated_at']
                        ]);
                }
            });
        }

        fclose($file);

        return back()->with('success', "Modificación masiva exitosa: {$actualizados} registros procesados (Usando delimitador '{$delimitador}'), {$omitidos} filas omitidas.");

    } catch (\Exception $e) {
        if (is_resource($file)) {
            fclose($file);
        }
        return back()->with('error', 'Error crítico en la modificación masiva: ' . $e->getMessage());
    }
}




    public function show($id)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }
        $cliente = Cliente::find($id);
        return redirect()->route('clientes.index')->with('success', 'Cliente registrado');
    }
    public function create()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }
        $pagostecnico = Pagotecnico::all();
        return view('pagotecnicos.create', compact('pagostecnico'));
    }

    public function store(StorePersonaRequest $request)
    {
        try {
            DB::beginTransaction();
            $persona = Persona::create($request->validated());
            $persona->cliente()->create([
                'persona_id' => $persona->id
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el cliente.');
        }

        return redirect()->route('clientes.index')->with('success', 'Cliente registrado');
    }

    public function exist(StoreClienteExistenteRequest $request)
    {
        try {
            DB::beginTransaction();

            // Buscar la persona existente
            $persona = Persona::findOrFail($request->persona_id);

            // Verificar si ya existe en clientes
            if ($persona->cliente) {
                return redirect()->back()->with('error', 'La persona ya está registrada como cliente.');
            }

            // Registrar como cliente
            $persona->cliente()->create(['persona_id' => $persona->id]);
            DB::commit();

            return redirect()->route('clientes.index')->with('success', 'Cliente registrado exitosamente.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar cliente existente - Persona ID: ' . $request->persona_id . ' - Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el cliente.');
        }
    }

    public function edit(Cliente $cliente)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $cliente->load('persona.documento');
        $documentos = Documento::all();
        return view('cliente.edit', compact('cliente', 'documentos'));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        try {
            DB::beginTransaction();
            Persona::where('id', $cliente->persona->id)
                ->update($request->validated());
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el cliente.');
        }

        return redirect()->route('clientes.index')->with('success', 'Cliente editado');
    }

    public function obtenerClientes()
    {
        $clientes = Cliente::select('id', 'persona_id')
        ->get();
        return response()->json($clientes);
    }

    public function listaClientes(Request $request)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }
        
        $query = Cliente::with('persona')->orderBy('persona.nombre', 'asc');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('persona', function ($q) use ($search) {
                $q->where('nombre', 'like', "%$search%");
            });
        }

        $clientes = $query->paginate(10); // Paginar los resultados
        return response()->json($clientes);
    }

    public function destroy(string $id)
    {
        try {
            $persona = Persona::findOrFail($id);
            $nuevoEstado = $persona->estado == 1 ? 0 : 1;
            $mensaje = $nuevoEstado == 0 ? 'Cliente desactivado' : 'Cliente reactivado';

            $persona->update(['estado' => $nuevoEstado]);

            return redirect()->route('clientes.index')->with('success', $mensaje);
        } catch (Exception $e) {
            Log::error('Error al cambiar estado del cliente - ID: ' . $id . ' - Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar el estado del cliente.');
        }
    }
}
