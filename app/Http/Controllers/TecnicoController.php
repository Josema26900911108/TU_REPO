<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; // Asegúrate de que esta línea esté presente

use Illuminate\Http\Request;
use App\Http\Requests\UpdateTecnicoRequest;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Documento;
use App\Models\MovimientoMaterial;
use App\Models\Expedientetecnico;
use App\Models\Persona;
use App\Models\Pagotecnico;
use App\Models\Tienda;
use App\Models\Expedientefotograficotecnico;
use App\Models\MovimientoMateriales;
use App\Models\Producto;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Spatie\Permission\Models\Role;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use App\Models\Tecnico;
use App\Models\usuariotienda;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\Paginator;
use PhpParser\Node\Expr\BinaryOp\Mod;
use Yajra\DataTables\DataTables;
use App\Models\Materialmanoobra;
use App\Models\Arbmanoobra;
use App\Models\Treematerialescategoria;
use ZipArchive;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;


class TecnicoController extends Controller
{
    public function __construct()
    {
        // Aplicar middleware de permisos
        $this->middleware('permission:ver-tecnico', ['only' => ['index']]);
        $this->middleware('permission:crear-tecnico', ['only' => ['create', 'store', 'exist']]);
        $this->middleware('permission:editar-tecnico', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-tecnico', ['only' => ['destroy']]);

    }
    public function boot(): void
{
    Paginator::useBootstrap();
}

    public function index()
    {
        DB::connection()->disableQueryLog();

                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

            $sql = "SELECT t.id,
			t.fkTienda,
            td.Nombre as Tienda,
            p.razon_social as tecnico,
            t.especialidad,
            t.codigo FROM
            tecnico as t inner join personas as p
				on p.id=t.fkpersona
            inner join tienda as td
				on td.idTienda=t.fkTienda ";

                if ($Estatus == 'ER') {

                    $sql .= "";

                } else {
                    $sql .= " where t.fkTienda= ".$fkTienda ;

                }
            $parametros=['id'=>''];
            $tecnicos=$this->obtenerdetalles($sql,$parametros);

        return view('tecnico.index', compact('tecnicos'));
    }

public function generarMemoriaFotografica(Request $request)
{
    // 1. Validar la existencia del archivo cargado
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

    // Palabras reservadas solicitadas para la auditoría de nombres
    // Palabras reservadas solicitadas en MAYÚSCULAS
    $palabrasClave = ['ANTENA', 'CONECTIVIDAD', 'MASTIL', 'SWITCH', 'POSTE_ANTES', 'POSTE_DESPUES', 'ANILLO_POSTES', 'ONT', 'OTT'];


    // Consultar el universo fotográfico cruzando con el árbol de tecnología
    $fotografiasUniverso = DB::table('expedientefotograficotecnico as ef')
        ->leftJoin('arbolmaterial as am', 'ef.fkTecnologia', '=', 'am.id')
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
                    return str_contains(strtoupper($f->fotografia), $palabra);
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

            // --- TITULO SUPERIOR DE LA MEMORIA ---
            $sheet->mergeCells('B2:L2');
            $sheet->setCellValue('B2', 'MEMORIA FOTOGRAFICA');
            $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(14)->setName('Arial');
            $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('B3:L3');
            $sheet->setCellValue('B3', strtoupper($palabra) . ' - SERVICIOS ' . strtoupper($techClean));
            $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(11)->setName('Arial');
            $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Bloque Informativo de la Obra (Fila 5 a Fila 8)
            $sheet->mergeCells('B5:L5');
            $sheet->setCellValue('B5', 'DATOS DE LA OBRA:');
            $sheet->getStyle('B5')->getFont()->setBold(true)->setSize(10)->setName('Arial');
            $sheet->getStyle('B5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('000000');
            $sheet->getStyle('B5')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));

            $sheet->setCellValue('B6', 'DIVISION:');             $sheet->setCellValue('C6', strtoupper($techClean));
            $sheet->getStyle('C6')->getFont()->setUnderline(true);
            $sheet->setCellValue('B7', 'NOMBRE DEL COORDINADOR:');  $sheet->setCellValue('C7', 'ERICK RIVAS');
            $sheet->getStyle('C7')->getFont()->setUnderline(true);
            $sheet->setCellValue('B8', 'NOMBRE DEL CONTRATISTA:');  $sheet->setCellValue('C8', 'LGB OCCIDENTE');
            $sheet->getStyle('C8')->getFont()->setUnderline(true);
            
            $sheet->setCellValue('E6', 'AREA:');                 $sheet->setCellValue('F6', 'OCCIDENTE');
            $sheet->getStyle('F6')->getFont()->setUnderline(true);
            $sheet->setCellValue('G6', 'TIPO DE OBRA:');          $sheet->setCellValue('H6', 'INSTALACION DE ANTENAS');
            $sheet->getStyle('H6')->getFont()->setUnderline(true);

            $sheet->setCellValue('G7', 'FECHA INICIO:');          $sheet->setCellValue('H7', '01/04/2026');
            $sheet->getStyle('H7')->getFont()->setUnderline(true);
            $sheet->setCellValue('G8', 'FECHA TERMINACION:');     $sheet->setCellValue('H8', '30/04/2026');
            $sheet->getStyle('H8')->getFont()->setUnderline(true);
            
            $sheet->getStyle('B6:B8')->getFont()->setBold(true)->setSize(9)->setName('Arial');
            $sheet->getStyle('E6')->getFont()->setBold(true)->setSize(9)->setName('Arial');
            $sheet->getStyle('G6:G8')->getFont()->setBold(true)->setSize(9)->setName('Arial');
            
            // Aplicar contorno externo al bloque superior
            $sheet->getStyle('B5:L8')->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

                        // --- GEOMETRÍA HORIZONTAL DE LOS 3 BLOQUES FOTOGRÁFICOS ---
            $bloquesX = [
                ['inicio' => 'B', 'medio' => 'C', 'fin' => 'D'], 
                ['inicio' => 'F', 'medio' => 'G', 'fin' => 'H'], 
                ['inicio' => 'J', 'medio' => 'K', 'fin' => 'L']  
            ];
            
            $fotoIndex = 0;
            $filaBaseFotos = 11; // La primera hilera arranca en la fila 11 exacta de la imagen

            foreach ($fotosFiltradas as $fotoItem) {
                
                $subColIndex = $fotoIndex % 3;
                $lineaMultiplo = floor($fotoIndex / 3);
                
                // Cada bloque de imágenes abarca 16 celdas de alto + 2 de espacio inferior = salto de 18
                $filaImagenInicio = $filaBaseFotos + ($lineaMultiplo * 18);
                $filaImagenFin    = $filaImagenInicio + 15; 
                $filaOrdenTexto   = $filaImagenFin + 2;     // Se posiciona en la fila 28 de la primera iteración

                $colLetras = $bloquesX[$subColIndex];
                $colIni = $colLetras['inicio'];
                $colMed = $colLetras['medio'];
                $colFin = $colLetras['fin'];

                // FUSIONAR CELDAS PARA CONTENER LA IMAGEN
                $sheet->mergeCells("{$colIni}{$filaImagenInicio}:{$colFin}{$filaImagenFin}");

                // Descarga binaria desde el Bucket de Google
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

                    // Configurar el motor de dibujo e inyectarlo centrado en el merge
                    $drawing = new Drawing();
                    $drawing->setName('Evidencia_' . $fotoItem->Orden);
                    $drawing->setPath($tempImagePath);
                    $drawing->setHeight(230); // Alto idóneo para llenar las 16 filas verticales proporcionalmente
                    $drawing->setCoordinates($colIni . $filaImagenInicio);
                    $drawing->setOffsetX(15);
                    $drawing->setOffsetY(10);
                    $drawing->setWorksheet($sheet);

                    $imagenesTemporalesABorrar[] = $tempImagePath;
                }

                // Aplicar el marco de borde negro alrededor de la celda de la imagen
                $sheet->getStyle("{$colIni}{$filaImagenInicio}:{$colFin}{$filaImagenFin}")
                      ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

                // --- DISEÑO PIE DE FOTO: CAJA DE LA ORDEN ---
                // Celda 1: Etiqueta "ORDEN"
                $sheet->setCellValue($colIni . $filaOrdenTexto, 'ORDEN');
                $sheet->getStyle($colIni . $filaOrdenTexto)->getFont()->setBold(true)->setSize(9)->setName('Arial');
                $sheet->getStyle($colIni . $filaOrdenTexto)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($colIni . $filaOrdenTexto)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

                // Celda 2 y 3 fusionadas: Número correlativo de la Orden técnica
                $sheet->mergeCells("{$colMed}{$filaOrdenTexto}:{$colFin}{$filaOrdenTexto}");
                $sheet->setCellValue("{$colMed}{$filaOrdenTexto}", $fotoItem->Orden);
                $sheet->getStyle("{$colMed}{$filaOrdenTexto}")->getFont()->setSize(9)->setName('Arial');
                $sheet->getStyle("{$colMed}{$filaOrdenTexto}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$colMed}{$filaOrdenTexto}:{$colFin}{$filaOrdenTexto}")
                      ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

                $fotoIndex++;
            }

            // --- FORMATEO EXPENDIDO DE ANCHOS DE COLUMNA (Efecto de Calles Separadoras) ---
            $sheet->getColumnDimension('A')->setWidth(4);  // Margen izquierdo
            $sheet->getColumnDimension('B')->setWidth(12); $sheet->getColumnDimension('C')->setWidth(12); $sheet->getColumnDimension('D')->setWidth(12); // Bloque 1
            $sheet->getColumnDimension('E')->setWidth(4);  // Calle intermedia
            $sheet->getColumnDimension('F')->setWidth(12); $sheet->getColumnDimension('G')->setWidth(12); $sheet->getColumnDimension('H')->setWidth(12); // Bloque 2
            $sheet->getColumnDimension('I')->setWidth(4);  // Calle intermedia
            $sheet->getColumnDimension('J')->setWidth(12); $sheet->getColumnDimension('K')->setWidth(12); $sheet->getColumnDimension('L')->setWidth(12); // Bloque 3
            $sheet->getColumnDimension('M')->setWidth(4);  // Margen derecho
            
            $sheetIndex++;
        }

        // Si la tecnología generó reportes válidos, guardar el archivo .xlsx
        if ($sheetIndex > 0) {
            $writer = new Xlsx($spreadsheet);
            $excelPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Memoria_Fotografica_' . $techClean . '.xlsx';
            $writer->save($excelPath);
            $zip->addFile($excelPath, 'Memoria_Fotografica_' . $techClean . '.xlsx');
        }
    }
    $zip->close();

    // Eliminar de la carpeta temporal los binarios residuales de imágenes utilizados para calcular dimensiones
    if (!empty($imagenesTemporalesABorrar)) {
        foreach ($imagenesTemporalesABorrar as $p) {
            if (file_exists($p)) { 
                @unlink($p); 
            }
        }
    }

    // Validación final de existencia física del empaquetado final
    if (!file_exists($zipPath) || filesize($zipPath) <= 22) {
        @unlink($zipPath);
        return back()->with('error', 'No se generaron memorias fotográficas. Ninguna imagen cumplió con las palabras clave.');
    }

    // Enviar el archivo binario comprimido al navegador y purgarlo de XAMPP tras finalizar la transferencia
    return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
}



    public function extraccionMasiva(Request $request)
{
    // 1. Validar la existencia del archivo cargado
    if (!$request->hasFile('excel_ordenes')) {
        return back()->with('error', 'No se recibió ningún archivo en el servidor.');
    }

    $file = $request->file('excel_ordenes');
    $path = $file->getRealPath();
    $ordenesRaw = [];
    
    try {
        $spreadsheet = IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $valorCelda = $worksheet->getCell('A' . $row)->getCalculatedValue();
            $valorCelda = trim(preg_replace('/[\s\x{00a0}]+/u', ' ', $valorCelda));

            if ($valorCelda !== '' && !is_null($valorCelda)) {
                $ordenesRaw[] = (string)$valorCelda;
            }
        }
    } catch (\Exception $e) {
        return back()->with('error', 'Error al leer el formato del archivo Excel: ' . $e->getMessage());
    }

    $ordenes = array_values(array_unique($ordenesRaw));

    if (empty($ordenes)) {
        return back()->with('error', 'El archivo Excel no contiene ninguna orden legible en la primera columna.');
    }

    // 2. Consultar Base de Datos mediante el Triple Cruce de Tablas
    $registrosPagos = DB::table('pagotecnico')->whereIn('Orden', $ordenes)->get();
    
    if ($registrosPagos->isEmpty()) {
        return back()->with('error', 'Ninguna de las órdenes ingresadas en tu archivo existe en la tabla pagotecnico.');
    }

    $ordenesEncontradas = $registrosPagos->pluck('Orden')->unique()->toArray();

    // 1. Extraer los movimientos planos desde la base de datos a máxima velocidad
    // 1. Extraer los movimientos planos desde la base de datos a máxima velocidad
$tiendaId = session('user_fkTienda');

$movimientosRaw = DB::table('movimientomateriales as mm')
    ->join('expedientetecnico as ex', 'ex.id', '=', 'mm.fkExpediente')
    ->leftJoin('tecnico as t', 'mm.fkTecnico', '=', 't.id')
    // 1. Modificamos el LEFT JOIN para abrir el abanico al código específico o al genérico
    ->leftJoin('MaterialManoObra as mamo', function ($join) use ($tiendaId) {
        $join->on('mm.SKU', '=', 'mamo.SKU')
             ->where(function ($query) use ($tiendaId) {
                 $query->whereColumn('mamo.centrocostoespecifico', '=', 't.codigo') // Coincide Técnico
                       ->orWhere('mamo.centrocostoespecifico', '=', $tiendaId)    // Coincide Tienda
                       ->orWhereNull('mamo.centrocostoespecifico')               // Aplica para todos (NULL)
                       ->orWhere('mamo.centrocostoespecifico', '=', '');         // Aplica para todos (Vacío)
             });
    })
    ->leftJoin('arbolmaterial as abmamo', 'mm.fkTecnologiaarbol', '=', 'abmamo.id')
    ->where('mm.fkTienda', $tiendaId)
    ->whereIn('ex.Orden', $ordenesEncontradas)
    ->select([
        'ex.id as expediente_id',
        'ex.Orden as orden_tecnica',
        'ex.virtual',
        'ex.Status as expediente_status',
        'ex.Tipo_servicio',
        'ex.Tipo_orden',
        'ex.NOMBRECLIENTE',
        'ex.DIRECCION',
        'ex.OBS as expediente_obs',
        'ex.SIGLASCENTRAL',
        'ex.AREA',
        'ex.FECHAINSTALACION',
        'abmamo.nombre as Tecnologia',
        'mm.ESTATUS as movimiento_estatus',
        'mm.SKU',
        'mamo.Descripcion',
        'mamo.TIPO',
        'mamo.CATEGORIA',
        'mm.id as movimiento_id', 
        'mm.serie',
        'mm.MAC1',
        'mm.MAC2',
        'mm.MAC3',
        't.nombre as tecnico_nombre', 
        't.codigo as tecnico_codigo', 
        't.especialidad as tecnico_esp',
        'mm.cantidad',
        // Tu CASE matemático se mantiene idéntico, ya que operará sobre la fila priorizada
        DB::raw("CASE 
            WHEN mamo.SKU IS NULL THEN NULL 
            WHEN mamo.CATEGORIA = 'MANO DE OBRA' THEN mamo.COSTOPAGO 
            ELSE mamo.CATEGORIACOBRO 
        END AS COSTO"),
        DB::raw("CASE 
            WHEN mamo.SKU IS NULL THEN NULL 
            WHEN mamo.unidadmedida = '' OR mamo.unidadmedida IS NULL THEN 'UNIDAD' 
            ELSE mamo.unidadmedida 
        END AS unidadmedida_auditada")
    ])
    // 2. Obligamos a la BD a ordenar poniendo los códigos específicos arriba y los vacíos abajo
    ->orderByRaw("CASE 
        WHEN mamo.centrocostoespecifico = t.codigo THEN 1 
        WHEN mamo.centrocostoespecifico = ? THEN 2 
        ELSE 3 
    END ASC", [$tiendaId])
    ->get()
    // 3. Procesamos por cada movimiento individual para eliminar duplicados del catálogo
    ->groupBy('movimiento_id')
    ->flatMap(function ($movimientoRows) {
        // Al usar unique() sobre los criterios clave, se quedará estrictamente con el primero (el específico)
        // y descartará el genérico sobrante.
        return $movimientoRows->unique(function ($item) {
            return $item->SKU . '-' . $item->TIPO . '-' . $item->unidadmedida_auditada . '-' . $item->CATEGORIA;
        });
    })
    ->values();

    // 2. Colapsar duplicados usando colecciones de Laravel en memoria RAM
    $movimientos = $movimientosRaw->unique('movimiento_id');
 

    // Obtener las evidencias fotográficas ligadas de Google Cloud
    $fotografias = DB::table('expedientefotograficotecnico')->whereIn('Orden', $ordenesEncontradas)->get();

    // Agrupar los datos por tecnología identificada
    $movimientosPorTecnologia = $movimientos->groupBy('Tecnologia');

    // Inicializar el ZIP temporal en el servidor
    $zipFileName = 'Extraccion_Pivot_Tecnologias_' . date('Ymd_His') . '.zip';
    $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFileName; 
    $zip = new ZipArchive;

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return back()->with('error', 'No se pudo inicializar la librería de compresión ZipArchive.');
    }
        // Columnas base comunes para las cabeceras horizontales
    $columnasBaseGenerales = [
        'No', 'Orden', 'virtual', 'Status', 'Tipo_servicio', 'Tipo_orden', 
        'NOMBRECLIENTE', 'DIRECCION', 'OBS', 'SIGLASCENTRAL', 'AREA', 'FECHAINSTALACION', 'TECNICO'
    ];

    // Iterar por cada tecnología para construir sus archivos independientes
    foreach ($movimientosPorTecnologia as $nombreTecnologia => $registrosTecnologia) {
        
        $nombreTecnologiaLimpio = empty($nombreTecnologia) ? 'OTRAS_TECNOLOGIAS' : str_replace(['/', '\\', '?', '*', ':', '[', ']'], '_', $nombreTecnologia);
        $spreadsheet = new Spreadsheet();
        
        // --- CONFIGURACIÓN HOJA 1: MANO DE OBRA ---
        $sheetMO = $spreadsheet->getActiveSheet();
        $sheetMO->setTitle('Mano de Obra');
        
        // Identificar qué conceptos únicos de Mano de Obra existen en ESTA tecnología
        $itemsMO = $registrosTecnologia->where('CATEGORIA', 'MANO DE OBRA');
        $descripcionesMOUnicas = $itemsMO->pluck('Descripcion')->unique()->toArray();
        
        // Ensamblar cabecera horizontal unificada para Mano de Obra
        $cabeceraMOCompleta = array_merge($columnasBaseGenerales, $descripcionesMOUnicas);
        $sheetMO->fromArray($cabeceraMOCompleta, NULL, 'A1');
        
        // Aplicar estilos a la cabecera
        $sheetMO->getStyle('A1:' . $sheetMO->getHighestColumn() . '1')->getFont()->setBold(true);
        $sheetMO->getStyle('A1:' . $sheetMO->getHighestColumn() . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D0E1F9');
        
        // Agrupar filas de mano de obra por cada orden técnica única
        $expedientesMO = $itemsMO->groupBy('orden_tecnica');
        $filaMO = 2;
        
        foreach ($expedientesMO as $orden => $detallesOrden) {
            $primerItem = $detallesOrden->first();
            
            $datosFilaMO = [
                $primerItem->expediente_id,
                $primerItem->orden_tecnica,
                $primerItem->virtual,
                $primerItem->expediente_status,
                $primerItem->Tipo_servicio,
                $primerItem->Tipo_orden,
                $primerItem->NOMBRECLIENTE,
                $primerItem->DIRECCION,
                $primerItem->expediente_obs,
                $primerItem->SIGLASCENTRAL,
                $primerItem->AREA,
                $primerItem->FECHAINSTALACION,
                $primerItem->tecnico_nombre
            ];
            
            // Colocar de forma dinámica la cantidad debajo de la columna del concepto correspondiente
            foreach ($descripcionesMOUnicas as $moColumna) {
                $matchConcepto = $detallesOrden->where('Descripcion', $moColumna)->first();
                $datosFilaMO[] = $matchConcepto ? $matchConcepto->cantidad : 0;
            }
            
            $sheetMO->fromArray($datosFilaMO, NULL, 'A' . $filaMO);
            $filaMO++;
        }
        
        foreach (range('A', $sheetMO->getHighestColumn()) as $col) {
            $sheetMO->getColumnDimension($col)->setAutoSize(true);
        }
        // --- CONFIGURACIÓN HOJA 2: MATERIALES ---
        $sheetMat = $spreadsheet->createSheet();
        $sheetMat->setTitle('Materiales');
        
        // Identificar los SKUs únicos de materiales físicos de esta tecnología
        $itemsMateriales = $registrosTecnologia->where('CATEGORIA', '!=', 'MANO DE OBRA');
        $skusMaterialesUnicos = $itemsMateriales->pluck('SKU')->unique()->toArray();
        
        // Ensamblar cabecera horizontal unificada para Materiales
        $cabeceraMatCompleta = array_merge($columnasBaseGenerales, $skusMaterialesUnicos);
        $sheetMat->fromArray($cabeceraMatCompleta, NULL, 'A1');
        
        // Estilos de la cabecera de materiales
        $sheetMat->getStyle('A1:' . $sheetMat->getHighestColumn() . '1')->getFont()->setBold(true);
        $sheetMat->getStyle('A1:' . $sheetMat->getHighestColumn() . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D1E7DD');
        
        // Agrupar filas de materiales por cada orden técnica única
        $expedientesMat = $itemsMateriales->groupBy('orden_tecnica');
        $filaMat = 2;
        
        foreach ($expedientesMat as $orden => $detallesMateriales) {
            $primerMat = $detallesMateriales->first();
            
            $datosFilaMat = [
                $primerMat->expediente_id,
                $primerMat->orden_tecnica,
                $primerMat->virtual,
                $primerMat->expediente_status,
                $primerMat->Tipo_servicio,
                $primerMat->Tipo_orden,
                $primerMat->NOMBRECLIENTE,
                $primerMat->DIRECCION,
                $primerMat->expediente_obs,
                $primerMat->SIGLASCENTRAL,
                $primerMat->AREA,
                $primerMat->FECHAINSTALACION,
                $primerMat->tecnico_nombre
            ];
            
            // Colocar la cantidad consumida exactamente debajo de la columna del SKU correspondiente
            foreach ($skusMaterialesUnicos as $skuColumna) {
                $matchSku = $detallesMateriales->where('SKU', $skuColumna)->first();
                $datosFilaMat[] = $matchSku ? $matchSku->cantidad : 0;
            }
            
            $sheetMat->fromArray($datosFilaMat, NULL, 'A' . $filaMat);
            $filaMat++;
        }
        
        foreach (range('A', $sheetMat->getHighestColumn()) as $col) {
            $sheetMat->getColumnDimension($col)->setAutoSize(true);
        }
        // --- CONFIGURACIÓN HOJA 3: RESUMEN DE COBROS ---
        $sheetResumen = $spreadsheet->createSheet();
        $sheetResumen->setTitle('Resumen de Cobros');
        
        // Títulos e informativos superiores del cuadro de costos
        $sheetResumen->setCellValue('B2', 'CUADRO DE COSTOS INSTALACIONES SERVICIOS ' . strtoupper($nombreTecnologiaLimpio));
        $sheetResumen->setCellValue('B3', 'REGION: OCCIDENTE');
        $sheetResumen->setCellValue('B4', 'PERIODO DEL ' . (request('fecha_inicio') ? Carbon::parse(request('fecha_inicio'))->format('d/m/Y') : '01/05/2026') . ' AL ' . (request('fecha_fin') ? Carbon::parse(request('fecha_fin'))->format('d/m/Y') : '31/05/2026'));
        $sheetResumen->getStyle('B2:B4')->getFont()->setBold(true);
        
        // Banner Rojo de Sección
        $sheetResumen->mergeCells('B6:G6');
        $sheetResumen->setCellValue('B6', 'REPORTE DE MANO DE OBRA');
        $sheetResumen->getStyle('B6')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        $sheetResumen->getStyle('B6')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');
        $sheetResumen->getStyle('B6')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Encabezados de la tabla liquidación
        $cabeceraTablaResumen = ['No', 'DESCRIPCION', 'UNIDAD', 'CANTIDAD REALIZADA', 'PRECIO MANO DE OBRA/UNIDAD', 'TOTAL DE MANO DE OBRA'];
        $sheetResumen->fromArray($cabeceraTablaResumen, NULL, 'B7');
        
        $sheetResumen->getStyle('B7:G7')->getFont()->setBold(true);
        $sheetResumen->getStyle('B7:G7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        $sheetResumen->getStyle('B7:G7')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        // Agrupar los conceptos de mano de obra para calcular acumulados verticales
        $resumenCobrosConceptos = $itemsMO->groupBy('Descripcion');
        $filaResumen = 8;
        $numNo = 1;
        
        foreach ($resumenCobrosConceptos as $conceptoTexto => $movimientosConcepto) {
            $sumaCantidad = $movimientosConcepto->sum('cantidad');
            $precioUnitario = $movimientosConcepto->first()->COSTO ?? 0;
            $unidadMedida = $movimientosConcepto->first()->unidadmedida_auditada ?? 'UNIDAD';
            
            $sheetResumen->setCellValue('B' . $filaResumen, $numNo);
            $sheetResumen->setCellValue('C' . $filaResumen, $conceptoTexto);
            $sheetResumen->setCellValue('D' . $filaResumen, $unidadMedida);
            $sheetResumen->setCellValue('E' . $filaResumen, $sumaCantidad);
            $sheetResumen->setCellValue('F' . $filaResumen, $precioUnitario);
            
            // Fórmula automática de Excel: Cantidad * Precio Unitario
            $sheetResumen->setCellValue('G' . $filaResumen, "=E{$filaResumen}*F{$filaResumen}");
            
            $sheetResumen->getStyle("B{$filaResumen}:G{$filaResumen}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheetResumen->getStyle("F{$filaResumen}:G{$filaResumen}")->getNumberFormat()->setFormatCode('"Q"#,##0.00');
            
            $filaResumen++;
            $numNo++;
        }
        
        // Bloque dinámico adaptativo de Impuestos y Liquidación
        $fTotalMO  = $filaResumen + 2;
        $fTotalMes = $fTotalMO + 2;
        $fIva      = $fTotalMes + 1;
        $fConIva   = $fIva + 1;
        
        // Inyección de Fórmulas de Cierre Financiero
        $sheetResumen->mergeCells("E{$fTotalMO}:F{$fTotalMO}");
        $sheetResumen->setCellValue("E{$fTotalMO}", 'TOTAL MANO DE OBRA');
        $sheetResumen->setCellValue("G{$fTotalMO}", "=SUM(G8:G" . ($filaResumen - 1) . ")");
        
        $sheetResumen->mergeCells("E{$fTotalMes}:F{$fTotalMes}");
        $sheetResumen->setCellValue("E{$fTotalMes}", 'TOTAL DEL MES');
        $sheetResumen->setCellValue("G{$fTotalMes}", "=G{$fTotalMO}");
        
        $sheetResumen->mergeCells("E{$fIva}:F{$fIva}");
        $sheetResumen->setCellValue("E{$fIva}", 'IVA 12%');
        $sheetResumen->setCellValue("G{$fIva}", "=G{$fTotalMes}*0.12");
        
        $sheetResumen->mergeCells("E{$fConIva}:F{$fConIva}");
        $sheetResumen->setCellValue("E{$fConIva}", 'TOTAL CON IVA');
        $sheetResumen->setCellValue("G{$fConIva}", "=G{$fTotalMes}+G{$fIva}");
        
        $filasTotalesFinales = [$fTotalMO, $fTotalMes, $fIva, $fConIva];
        foreach ($filasTotalesFinales as $f) {
            $sheetResumen->getStyle("E{$f}:G{$f}")->getFont()->setBold(true);
            $sheetResumen->getStyle("E{$f}:G{$f}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheetResumen->getStyle("G{$f}")->getNumberFormat()->setFormatCode('"Q"#,##0.00');
        }
        
        // Dimensionamiento de anchos fijos de la liquidación para calcar la imagen
        $sheetResumen->getColumnDimension('B')->setWidth(6);
        $sheetResumen->getColumnDimension('C')->setWidth(50);
        $sheetResumen->getColumnDimension('D')->setWidth(12);
        $sheetResumen->getColumnDimension('E')->setWidth(22);
        $sheetResumen->getColumnDimension('F')->setWidth(26);
        $sheetResumen->getColumnDimension('G')->setWidth(26);
        
        // 5. Guardar libro Excel de la tecnología actual e insertarlo en la raíz del ZIP
        $writer = new Xlsx($spreadsheet);
        $excelTemporalPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Reporte_' . $nombreTecnologiaLimpio . '.xlsx';
        $writer->save($excelTemporalPath);
        
        $zip->addFile($excelTemporalPath, 'Reporte_Tecnologia_' . $nombreTecnologiaLimpio . '.xlsx');
    }
    // --- SECCIÓN DE EVIDENCIAS FOTOGRÁFICAS ---
    $nombreBucket = 'sistema-pv-imagenes-tienda';
    $fotosContador = 0;

    foreach ($fotografias as $foto) {
        $urlCompleta = $foto->fotografia;
        $pathBucket = $urlCompleta;

        if (str_contains($urlCompleta, $nombreBucket)) {
            $posicionBucket = strpos($urlCompleta, $nombreBucket);
            $pathBucket = substr($urlCompleta, $posicionBucket + strlen($nombreBucket));
            $pathBucket = ltrim($pathBucket, '/');
        } else {
            $pathBucket = ltrim(parse_url($urlCompleta, PHP_URL_PATH), '/');
        }

        if (Storage::disk('gcs_images')->exists($pathBucket)) {
            $imageContent = Storage::disk('gcs_images')->get($pathBucket);
            $nombreArchivoOriginal = pathinfo($pathBucket, PATHINFO_BASENAME);
            
            // Almacenamiento clasificado en subcarpetas internas por Número de Orden
            $nombreArchivoInterno = "fotografias/Orden_{$foto->Orden}/" . $nombreArchivoOriginal;
            
            $zip->addFromString($nombreArchivoInterno, $imageContent);
            $fotosContador++;
        }
    }

    $zip->close();

    // Validar peso y consistencia del entregable
    if (!file_exists($zipPath) || filesize($zipPath) <= 22) { 
        @unlink($zipPath);
        return back()->with('error', 'El proceso concluyó sin datos empaquetables.');
    }

    // Alertas informativas de la bitácora Flash
    $fotosStatus = $fotosContador > 0 ? "Fotografías OK ({$fotosContador} descargadas)" : "Fotografías: No descargado";
    session()->flash('notificacion_extraccion', [
        'pago' => 'Pago Técnico OK',
        'materiales' => 'Reportes de Tecnologías Generados OK',
        'fotos' => $fotosStatus
    ]);

    // Descarga inmediata del archivo binario y purga automática del temporal del servidor
    return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
}

public function extraccionMasiva1(Request $request)
{
    // 1. Validar la existencia del archivo cargado
    if (!$request->hasFile('excel_ordenes')) {
        return back()->with('error', 'No se recibió ningún archivo en el servidor.');
    }

    $file = $request->file('excel_ordenes');
    $path = $file->getRealPath();
    $ordenesRaw = [];
    
    try {
        // 2. Cargar el lector de PhpSpreadsheet para abrir el archivo .xlsx nativo
        $spreadsheet = IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();

        // Recorrer TODAS las filas de la columna A (desde la fila 2 hasta la última)
        for ($row = 2; $row <= $highestRow; $row++) {
            // getCalculatedValue garantiza leer el valor real aunque la celda tenga formato numérico o de texto
            $valorCelda = $worksheet->getCell('A' . $row)->getCalculatedValue();
            
            // Limpieza absoluta de espacios en blanco normales e invisibles (caracteres no-rompibles)
            $valorCelda = trim(preg_replace('/[\s\x{00a0}]+/u', ' ', $valorCelda));

            if ($valorCelda !== '' && !is_null($valorCelda)) {
                $ordenesRaw[] = (string)$valorCelda; // Forzar a tipo String para evitar fallos en base de datos
            }
        }
    } catch (\Exception $e) {
        return back()->with('error', 'Error al leer el formato del archivo Excel: ' . $e->getMessage());
    }

    // Quitar duplicados y limpiar el índice del arreglo
    $ordenes = array_values(array_unique($ordenesRaw));

    if (empty($ordenes)) {
        return back()->with('error', 'El archivo Excel no contiene ninguna orden legible en la primera columna.');
    }

    // 3. Consultar Base de Datos mediante el Triple Cruce de Tablas usando la lista completa
    $registrosPagos = DB::table('pagotecnico')->whereIn('Orden', $ordenes)->get();
    
    if ($registrosPagos->isEmpty()) {
        return back()->with('error', 'Ninguna de las órdenes ingresadas en tu archivo existe en la tabla pagotecnico.');
    }

    // Volver a mapear las órdenes que sí se encontraron para amarrar los expedientes y fotos
    $ordenesEncontradas = $registrosPagos->pluck('Orden')->unique()->toArray();

    // Obtener los IDs de la tabla puente 'expedientetecnico' usando las órdenes validadas
// Extraer materiales incluyendo la Orden del expediente y el ID del árbol de materiales (tecnología)
$movimientos = DB::table('movimientomateriales as mm')
    ->join('expedientetecnico as ex', 'ex.id', '=', 'mm.fkExpediente')
    ->leftJoin('MaterialManoObra as mamo', 'mm.SKU', '=', 'mamo.SKU')
    ->leftJoin('arbolmaterial as abmamo', 'mm.fkTecnologiaarbol', '=', 'abmamo.id')
    ->leftJoin('tecnico as t', 'mm.fkTecnico', '=', 't.id')
    ->where('mm.fkTienda', session('user_fkTienda'))
    ->whereIn('ex.Orden', $ordenesEncontradas)
    // 1. Añadimos distinct() para obligar a MySQL a limpiar los duplicados del JOIN
    ->distinct() 
    ->select([
        'ex.id as expediente_id',
        'ex.Orden as orden_tecnica',
        'ex.virtual',
        'ex.Status as expediente_status',
        'ex.Tipo_servicio',
        'ex.Tipo_orden',
        'ex.NOMBRECLIENTE',
        'ex.DIRECCION',
        'ex.OBS as expediente_obs',
        'ex.SIGLASCENTRAL',
        'ex.AREA',
        'ex.FECHAINSTALACION',
        'abmamo.nombre as Tecnologia',
        'mm.ESTATUS as movimiento_estatus',
        'mm.SKU',
        'mamo.Descripcion',
        'mamo.TIPO',
        'mamo.CATEGORIA',
        // 2. IMPORTANTE: Si la duplicación persiste, remueve 'mm.id' o aplica un MAX(mm.id)
        'mm.id as movimiento_id', 
        'mm.serie',
        'mm.MAC1',
        'mm.MAC2',
        't.nombre as tecnico_nombre', 't.codigo as tecnico_codigo', 't.especialidad as tecnico_esp', // Datos adicionales del técnico para enriquecer el reporte
        'mm.MAC3',
        // COSTO basado en la auditoría
        DB::raw("CASE 
            WHEN mamo.SKU IS NULL THEN NULL 
            WHEN mamo.CATEGORIA = 'MANO DE OBRA' THEN mamo.COSTOPAGO 
            ELSE mamo.CATEGORIACOBRO 
        END AS COSTO"),
        // EVALUACIÓN DE UNIDAD DE MEDIDA DESDE EL CATÁLOGO MAESTRO (mamo)
        DB::raw("CASE 
            WHEN mamo.SKU IS NULL THEN NULL 
            WHEN mamo.unidadmedida = '' OR mamo.unidadmedida IS NULL THEN 'UNIDAD' 
            ELSE mamo.unidadmedida 
        END AS unidadmedida_auditada")
    ])
    ->get();





    
    // Obtener TODAS las evidencias fotográficas del grupo de órdenes
    $fotografias = DB::table('expedientefotograficotecnico')->whereIn('Orden', $ordenesEncontradas)->get();

    // Variables de control de estados para la notificación Flash
    $pagoTecnicoStatus = 'Pago Técnico OK';
    $movimientosStatus = $movimientos->count() > 0 ? 'Movimiento Materiales OK' : 'Movimiento Materiales: No descargado (Sin registros)';
    $fotosContador = 0;

    // 4. Crear el archivo ZIP temporal
    $zipFileName = 'Extraccion_Masiva_GCS_' . date('Ymd_His') . '.zip';
    $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFileName; 

    $zip = new ZipArchive;

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        
        // --- ARCHIVO 1: CSV de Reporte Órdenes de Pago ---
        $csvPagosHandle = fopen('php://memory', 'r+');
        fprintf($csvPagosHandle, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($csvPagosHandle, ['id', 'Orden', 'SKU', 'Descripcion', 'Cantidad', 'COSTOPAGO', 'Naturaleza', 'Status']);
        foreach ($registrosPagos as $p) {
            fputcsv($csvPagosHandle, [$p->id, $p->Orden, $p->SKU, $p->Descripcion, $p->Cantidad, $p->COSTOPAGO, $p->Naturaleza, $p->Status]);
        }
        rewind($csvPagosHandle);
        $zip->addFromString('reporte_ordenes.csv', stream_get_contents($csvPagosHandle));
        fclose($csvPagosHandle);

        // --- ARCHIVO 2: CSV de Reporte de Materiales ---
            if ($movimientos->count() > 0) {
            $csvMatHandle = fopen('php://memory', 'r+');
             fprintf($csvMatHandle, chr(0xEF).chr(0xBB).chr(0xBF));
   // 1. Cabecera expandida con toda la estructura de columnas solicitada
   // 1. Cabecera incluyendo las nuevas columnas del Técnico
    fputcsv($csvMatHandle, [
        'id_expediente', 'Orden', 'virtual', 'Status', 'Tipo_servicio', 'Tipo_orden', 
        'NOMBRECLIENTE', 'DIRECCION', 'OBS', 'SIGLASCENTRAL', 'AREA', 'FECHAINSTALACION', 
        'Tecnologia', 'Estatus_Mov', 'SKU', 'Descripcion', 'TIPO', 'COSTO', 
        'CATEGORIA', 'id_movimiento', 'serie', 'MAC1', 'MAC2', 'MAC3',
        'Tecnico_Nombre', 'Tecnico_Codigo', 'Tecnico_Especialidad', 'unidadmedida' // Nuevas columnas en cabecera
    ]);
    
    // 2. Volcado de datos mapeando los nuevos alias del Técnico
    foreach ($movimientos as $m) {
        fputcsv($csvMatHandle, [
            $m->expediente_id,
            $m->orden_tecnica,
            $m->virtual,
            $m->expediente_status,
            $m->Tipo_servicio,
            $m->Tipo_orden,
            $m->NOMBRECLIENTE,
            $m->DIRECCION,
            $m->expediente_obs,
            $m->SIGLASCENTRAL,
            $m->AREA,
            $m->FECHAINSTALACION,
            $m->Tecnologia,
            $m->movimiento_estatus,
            $m->SKU,
            $m->Descripcion,
            $m->TIPO,
            $m->COSTO,
            $m->CATEGORIA,
            $m->movimiento_id,
            $m->serie,
            $m->MAC1,
            $m->MAC2,
            $m->MAC3,
            $m->tecnico_nombre, // Variable agregada
            $m->tecnico_codigo, // Variable agregada
            $m->tecnico_esp,    // Variable agregada
            $m->unidadmedida_auditada
        ]);
    }
    
            rewind($csvMatHandle);
            $zip->addFromString('reporte_movimientos.csv', stream_get_contents($csvMatHandle));
            fclose($csvMatHandle);
        }

        // --- SECCIÓN 3: Descarga de Evidencias desde Google Cloud Storage (gcs_images) ---
        $nombreBucket = 'sistema-pv-imagenes-tienda';

        foreach ($fotografias as $foto) {
            $urlCompleta = $foto->fotografia;
            $pathBucket = $urlCompleta;

            if (str_contains($urlCompleta, $nombreBucket)) {
                $posicionBucket = strpos($urlCompleta, $nombreBucket);
                $pathBucket = substr($urlCompleta, $posicionBucket + strlen($nombreBucket));
                $pathBucket = ltrim($pathBucket, '/');
            } else {
                $pathBucket = ltrim(parse_url($urlCompleta, PHP_URL_PATH), '/');
            }

            if (Storage::disk('gcs_images')->exists($pathBucket)) {
                $imageContent = Storage::disk('gcs_images')->get($pathBucket);
                $nombreArchivoOriginal = pathinfo($pathBucket, PATHINFO_BASENAME);
                
                // Clasificación interna organizada en subcarpetas por número de Orden
                $nombreArchivoInterno = "fotografias/Orden_{$foto->Orden}/" . $nombreArchivoOriginal;
                
                $zip->addFromString($nombreArchivoInterno, $imageContent);
                $fotosContador++;
            }
        }

        $zip->close();
    } else {
        return back()->with('error', 'Error del sistema: No se pudo inicializar la librería de compresión ZipArchive.');
    }

    if (!file_exists($zipPath) || filesize($zipPath) <= 22) { 
        @unlink($zipPath);
        return back()->with('error', 'El archivo final se generó vacío.');
    }

    $fotosStatus = $fotosContador > 0 ? "Fotografías OK ({$fotosContador} descargadas)" : "Fotografías: No descargado";
    
    session()->flash('notificacion_extraccion', [
        'pago' => $pagoTecnicoStatus,
        'materiales' => $movimientosStatus,
        'fotos' => $fotosStatus
    ]);

    return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
}




        public function bucket($id)
    {
        DB::connection()->disableQueryLog();
        try {
                            if(!Auth::check()){
            return redirect()->route('login');
        }

            DB::beginTransaction();

            $fkTienda = session('user_fkTienda');
            $tecnicos=Tecnico::where('id',$id)->get();
            $expediente=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$id)->get();

            DB::commit();

            return view('buckettecnico.index', compact('tecnicos','expediente'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }


    }

    
    public function show($id)
    {
        // Lógica para mostrar un cliente específico
        $cliente = Cliente::find($id);
        return redirect()->route('clientes.index')->with('success', 'Cliente registrado');
    }
    public function edit($id){
                try{
                                    if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');
        $rol = Role::all();
        $documentos=Documento::all();
        $tecnico=Tecnico::where('id',$id)
        ->first();

$tienda = DB::table('usuario_tienda as us')
    ->select('ti.Nombre', 'ti.idTienda')
    ->join('users as u', 'u.id', '=', 'us.fkUsuario')
    ->join('tecnico as t', 't.fkTienda', '=', 'us.fkTienda')
    ->join('tienda as ti', 'ti.idTienda', '=', 't.fkTienda')
    ->where('t.id', $id)
    ->distinct()
    ->get();

// Verificar si hay resultados
if ($tienda->isEmpty()) {
    // Manejar el caso cuando no hay tiendas
    $tienda = collect(); // Crear colección vacía
}


    $users=DB::table('users as u')
    ->join('usuario_tienda as ut', 'ut.fkUsuario','=','u.id','left')
    ->where('fkTienda',$fkTienda)
    ->get();


        return view('tecnico.edit', compact('tecnico','rol','documentos','users','tienda','id'));
                }catch(Exception $e){
                return response()->json(['error' => $e->getMessage()], 400);

        }
    }
    public function create()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');
        $rol = Role::all();
        $documentos=Documento::all();


    $users=DB::table('users as u')
    ->join('usuario_tienda as ut', 'ut.fkUsuario','=','u.id','left')
    ->where('fkTienda',$fkTienda)
    ->get();


        if ($Estatus == 'ER') {
                    $tecnico = Tienda::all();
                } else {
                    $tecnico = Tienda::where('idTienda',$fkTienda)->get();
                };



        return view('tecnico.create', compact('tecnico','rol','documentos','users'));
    }

public function prepararimagen($request){

    $request->validate([
        'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);
    
    $file = $request->file('image');
    $manager = new ImageManager(new Driver());

    $image = $manager->read($file->getPathname())
                     ->resize(800, 800, function ($constraint) {
                         $constraint->aspectRatio();
                         $constraint->upsize();
                     });

    // Convert to WebP y definir la ruta virtual del bucket
    $filename = 'tecnico_' . time() . '.webp';
    $path = 'tecnicos/' . $filename; // Se guardará en gs://sistema-pv-imagenes-tienda/tecnicos/
    $webpEncoder = new WebpEncoder(quality: 80);

    // 1. Guardar la imagen procesada directamente en Google Cloud Storage
    Storage::disk('gcs_images')->put($path, (string) $image->encode($webpEncoder));

    // 2. OBLIGATORIO: Retornamos la ruta para que tu controlador la guarde en la BD
    return $path;
}


    public function store(Request $request)
    {
$lockKey = 'tecnico_create' . auth()->id();
    if (!Cache::add($lockKey, true, 10)) {
        return redirect()->back()->with('error', 'La venta ya se está procesando. Por favor, espera.');
    }

        try {

            DB::beginTransaction();
         // Procesar imagen y convertir a BLOB
        $file = $request->file('image');
        $manager = new ImageManager(new Driver());

        $image = $manager->read($file->getPathname())
                         ->resize(800, 800, function ($constraint) {
                             $constraint->aspectRatio();
                             $constraint->upsize();
                         });

        // Convertir a WebP y obtener como cadena binaria
        $webpEncoder = new WebpEncoder(quality: 80);
        $imageBlob = $image->encode($webpEncoder);

        // Crear persona
        $persona = Persona::create([
            'razon_social' => $request->razon_social,
            'direccion' => $request->direccion,
            'tipo_persona' => $request->tipo_persona,
            'estado' => 1,
            'documento_id' => $request->documento_id,
            'numero_documento' => $request->numero_documento,
            'created_at' => now()
        ]);


    //creacion de tecnico
            $persona->tecnico()->create([
                'fkpersona' => $persona->id,
                'nombre' => $persona->razon_social,
                'fkTienda' => $request->tienda,
                'codigo' => $request->numero_eta,
                'especialidad' => $request->especialidad,
                'logo' => $imageBlob
            ]);

            //Encriptar contraseña
            $fieldHash = Hash::make($request->password);
            //Modificar el valor de password en nuestro request
            $request->merge(['password' => $fieldHash]);

            //Crear usuario
            $user = User::create(array_merge([
                'fkTienda' => $request->tienda,
                'logo'=>$imageBase64??null,
                'name' => $request->razon_social,
                'email' => $request->email,
                'password'=> $request->password,
                'created_at'=>now()
                ]));

            //Asignar su rol
            $user->assignRole($request->role);

            usuariotienda::create(array_merge([
                'fkUsuario'=>$user->id,
                'fkTienda'=>$request->tienda,
                'Estatus'=>$request->Estatus,
                'FechaIngreso'=>now(),
                'created_at'=>now()
            ]));

            DB::commit();
            Cache::forget($lockKey);
            return redirect()->route('tecnico.lista')->with('success', 'Tecnico registrado');

        } catch (Exception $e) {
            Cache::forget($lockKey);
            DB::rollBack();
            Log::error('Error al registrar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el cliente.');
        }

        

    }

    public function exist(Request $request)
{
    if (!Auth::check()) {
        return response()->json(['error' => 'Sesión expirada.'], 401);
    }

    // 1. Validación correcta de Laravel antes de abrir transacciones
    $request->validate([
        'idtecnico' => 'required',
        'tienda'    => 'required',
        'email'     => 'nullable|email|unique:users,email,' . $request->user, // Evita colisiones de correo
    ], [
        'email.unique' => 'El correo electrónico ya existe en el sistema, por favor elige uno nuevo.'
    ]);

    try {
        DB::beginTransaction();

        // 2. Buscar la Persona (esta sí debe existir obligatoriamente)
        $idpersona = Tecnico::where('id', $request->idtecnico)->value('fkpersona');
        $persona = Persona::findOrFail($idpersona);

        // 3. BUSCAR O CREAR el técnico vinculado a esa persona
        $tecnico = Tecnico::updateOrCreate(
            ['fkpersona' => $persona->id], 
            [
                'nombre'       => $persona->razon_social, 
                'fkTienda'     => $request->tienda,
                'codigo'       => $request->numero_eta,
                'especialidad' => $request->especialidad,
                'fkuser'       => $request->user,
                'updated_at'   => now()
            ]
        );

        // 4. PROCESAR IMAGEN: Únicamente si el archivo fue enviado y es válido
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            
            $file = $request->file('image');
            $manager = new ImageManager(new Driver());

            // Lectura mediante RealPath e Intervention Image V3 API
            $image = $manager->read($file->getRealPath());
            
            // Redimensionar proporcionalmente a 300x300 (Reemplaza de forma nativa a upsize y aspectRatio)
            $image->resizeDown(300, 300);

            // Codificación a WebP con calidad al 50%
            $encoded = $image->toWebp(50);

            // Convertimos el buffer codificado de Intervention V3 a Base64 para guardarlo en la columna 'logo'
            $imageBase64 = 'data:image/webp;base64,' . base64_encode((string)$encoded);

            // Actualizamos el campo logo con el nuevo WebP Base64 optimizado
            $tecnico->update(['logo' => $imageBase64]);
        }
        // 💡 ELSE SILENCIOSO: Si no se envía imagen, el campo 'logo' conserva intacto su valor previo en la DB.

        DB::commit();

        return redirect()->route('tecnico.lista')->with('success', 'Técnico registrado correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error en Exist: ' . $e->getMessage());
        return response()->json(['error' => 'Error interno en el servidor: ' . $e->getMessage()], 500);
    }
}



    public function obtenerdetalless(Request $request){

        try {
            DB::connection()->disableQueryLog();
                            if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $param = $request->input('parametros');


$materiales = MovimientoMaterial::join('treematerialescategoria as tmc', 'tmc.sku', '=', 'movimientomateriales.SKU')
    ->join('expedientetecnico as et', 'et.id', '=', 'movimientomateriales.fkExpediente')
    ->where('et.id', $param)
    ->select([
        'movimientomateriales.serie',
        'tmc.nombre as Descripcion',
        'tmc.sku as sku',
        'movimientomateriales.id as id',  
        
        // RESTRUCTURACIÓN: Cambiamos el nombre del alias para romper la confusión de objetos
        DB::raw("COALESCE(NULLIF(movimientomateriales.fkTecnologiaarbol, ''), 0) AS fkTecnologiaarbol"),
        
        DB::raw("IFNULL(movimientomateriales.cantidad, 1) as cantidad")
    ])
    ->get();


    
    return response()->json($materiales);
            } catch (Exception $e) {
            dd($e);
            DB::rollBack();
        }
    }

    public function AutomataValidarMamoOrdenTecnico(Request $request)
{
    DB::connection()->disableQueryLog();

    if(!Auth::check()) return response()->json(['error' => 'No autorizado'], 401);
    $procesados = []; 
    $rastro = [];
    $orden = $request->input('Orden');
    
    // Captura de datos virtuales del frontend
    $skuNuevo = trim($request->input('SKU_Nuevo'));
    $cantidadNueva = (float)$request->input('Cantidad_Nueva');

    // Carga de ítems consolidados actuales en base de datos
    $items = DB::table('ETA')->select('CENTRO', 'SKU', DB::raw('SUM(cantidad) as Cantidad'))
                             ->where('fkTienda', session('user_fkTienda'))         
                             ->where('Orden', $orden)->groupBy('SKU', 'CENTRO')->get();

    $itemsSimulados = $items->toArray();
    $skuEncontradoEnOrden = false;

    // Si el SKU ya está reportado en la orden, sumamos la cantidad temporalmente
    foreach ($itemsSimulados as $key => $item) {
        if (trim($item->SKU) == $skuNuevo) {
            $itemsSimulados[$key]->Cantidad += $cantidadNueva;
            $skuEncontradoEnOrden = true;
        }
    }

    // Si es un material nuevo que no se ha guardado en DB, simulamos su fila con el centro de la orden
    if (!$skuEncontradoEnOrden) {
        $centroBase = DB::table('ETA')
        ->where('fkTienda', session('user_fkTienda'))
        ->where('Orden', $orden)->value('CENTRO') ?? "'G888";
        $itemsSimulados[] = (object)[
            'CENTRO' => $centroBase,
            'SKU' => $skuNuevo,
            'Cantidad' => $cantidadNueva
        ];
    }

    // Ejecución del autómata con la lista combinada (DB + Simulado)
    foreach ($itemsSimulados as $item) {
        $this->ejecutarLogicaInterna($orden, $item, $procesados, $rastro);
    }

    $validaciones = $this->quitarDuplicadosPorOrdenYSKU($procesados);
    
    // Evaluamos el resultado del autómata únicamente para el SKU que se está interactuando
    foreach ($validaciones as $val) {
        if (trim($val->SKU) == $skuNuevo) {
            
            $calculado = (float)($val->valor_calculado ?? 0);
            $minimo = (float)($val->minimo_calculado ?? 0);
            $maximo = (float)($val->maximo_calculado ?? 0);
            $nombreMaterial = $val->nombre_material ?? "Material técnico";

            // Validación de Exceso
            if ($maximo > 0 && $calculado > $maximo) {
                $diff = $calculado - $maximo;
                return response()->json([
                    'sugerencia' => [
                        'status' => 'exceso',
                        'mensaje' => "El sistema detectó que estás reportando de más para '{$nombreMaterial}'. El tope máximo según la norma del centro es de {$maximo} unidades. Estás excedido por {$diff}."
                    ]
                ], 200);
            }

            // Validación de Faltante
            if ($minimo > 0 && $calculado < $minimo) {
                $diff = $minimo - $calculado;
                return response()->json([
                    'sugerencia' => [
                        'status' => 'falta',
                        'mensaje' => "Atención: Según las reglas de cubicación para '{$nombreMaterial}', faltan insumos obligatorios para cerrar la instalación. El mínimo técnico es de {$minimo} unidades (te hacen falta {$diff})."
                    ]
                ], 200);
            }
        }
    }

    // Si pasa todas las reglas del árbol jerárquico de validación
    return response()->json(['sugerencia' => null], 200);
}
    public function validarMaterialesTecnicos(Request $request) {
        
    $materialesInput = $request->input('materiales', []);
    $procesados = [];
    $rastro = [];

    foreach ($materialesInput as $item) {
        // Convertimos a objeto para que sea compatible con tu lógica de ejecutarLogicaInterna
        $objItem = (object)[
            'SKU' => $item['sku'],
            'Cantidad' => $item['cantidad'],
            'CENTRO' => 'TEMP' // Opcional si no filtras por centro aquí
        ];
        
        $this->ejecutarLogicaInterna(0, $objItem, $procesados, $rastro);
    }

    return response()->json(['validaciones' => array_values($procesados)]);
}


    public function inventariotecnicoorden($tecbucket)
    {
        DB::connection()->disableQueryLog();

        try {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $orden = Expedientetecnico::where('id', $tecbucket)
            ->where(function($query) {
                $query->where('Estatus', 'I')
                    ->orWhere('Estatus', 'S')
                    ->orWhere('Estatus', 'A')
                    ->orWhere('Estatus', 'O')
                    ->orWhere('Estatus', 'C');
            })
            ->first();


        $tecnico = Tecnico::where('id',$orden->fkTecnico)->first();


        return view('buckettecnico.edit', compact('tecbucket', 'orden','tecnico'));
      }  catch (Exception $e) {         
            return response()->json(['error, es posible que esta orden ya no cuente con registro para modificar' => $e->getMessage()], 400);
        }
    }

            public function fillEstructura()
    {
try {
        $fkTienda = session('user_fkTienda');
        $pdo = DB::getPdo();
        $sqlll='
        SELECT DISTINCT am.nombre, am.id, am.SKU FROM arbolmaterial as amo
        inner join (select ams.id, ams.SKU, ams.nombre from arbolmaterial as ams where isnull(ams.padre_id)) AS am on am.id=amo.padre_id
        where fkTienda=:id
        ';
        $stmt = $pdo->prepare($sqlll);

        $stmt->execute(['id' => $fkTienda]);


        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);


    return response()->json($detallecomprobante);

            } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
            DB::rollBack();
        }

    }
    public function fetch2(Request $request)
    {
        $id = $request->input('id');
        // Inicializamos el ID de la categoría padre como NULL para empezar desde el nodo raíz.
        $data = $this->get_node_data($id);

        // Codificamos los datos en formato JSON para enviarlos al frontend.
        echo json_encode(array_values($data));
    }

    function get_node_data($parent_category_id)
    {
        // Obtenemos las arbolmateriales contables que tienen como padre el ID dado
        $result = DB::table('arbolmaterial')
            ->where('padre_id', $parent_category_id) // Buscamos por padre_id
            ->where('fkTienda',session('user_fkTienda'))
            ->get();

        $output = []; // Inicializamos el arreglo de salida

        // Iteramos sobre los resultados y construimos el árbol de nodos
        foreach ($result as $row) {
            $sub_array = [];
            $sub_array['nodeId'] = $row->id; // Usamos nodeId para cada nodo
            $sub_array['Cid'] = $row->id; // Usamos nodeId para cada nodo
            $sub_array['padre_id'] = $row->padre_id; // Usamos nodeId para cada nodo
            $sub_array['cuenta_id'] = $row->SKU; // Usamos nodeId para cada nodo
            $sub_array['text'] = $row->SKU."-".$row->nombre; // Mostrar el nombre de la cuenta
            $sub_array['nombre'] = $row->nombre; // Mostrar el nombre de la cuenta
            $sub_array['aplicafotografia'] = $row->aplicafotografia; // Mostrar el nombre de la cuenta
            $sub_array['Tipo_servicio'] = $row->Tipo_servicio; // Mostrar el nombre de la cuenta
            $sub_array['nodes'] = $this->get_node_data($row->id); // Recursión para obtener los hijos
            $sub_array['idpivote'] = $row->idpivote; // Recursión para obtener los hijos
            $output[] = $sub_array; // Agregar al array de salida
        }

        return $output;
    }

    public function fillEstructuraMO($id)
    {
        DB::connection()->disableQueryLog();

    try {
        $fkTienda = session('user_fkTienda');
        $pdo = DB::getPdo();
        $sqlll='
        SELECT DISTINCT am.nombre, am.id, am.SKU FROM arbolmaterial as am where am.padre_id=:id and am.fkTienda=:id2
        ';
        $stmt = $pdo->prepare($sqlll);

        $stmt->execute(['id' => $id, 'id2' => $fkTienda]);


        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);


    return response()->json($detallecomprobante);

            } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
            DB::rollBack();
        }

    }

public function InventarioLista(request $request)
{
    DB::connection()->disableQueryLog();

    try {
        $fkTienda = session('user_fkTienda');
        $pdo = DB::getPdo();
        $idPadre = $request->input('id1'); 
        $idtecnico = $request->input('id2');
        
        $sqlll = "
WITH RECURSIVE nodo_padre AS (
    SELECT id, padre_id, nombre, sku, aplicafotografia as apf, Tipo_servicio as TP
    FROM arbolmanoobra
    WHERE id = ? AND fkTienda = ?    
    UNION ALL    
    SELECT a.id, a.padre_id, a.nombre, a.sku, a.aplicafotografia as apf, a.Tipo_servicio as TP
    FROM arbolmanoobra a
    INNER JOIN nodo_padre np ON a.padre_id = np.id
    WHERE a.fkTienda = ?
),
cte_hijos AS ( 
    SELECT id, padre_id, TRIM(nombre) as nombre, TRIM(sku) as sku_hijo, apf, TP 
    FROM nodo_padre 
    WHERE id <> ?
)
SELECT DISTINCT
    am.nombre, 
    am.SKU, -- Corregido a mayúsculas para coincidir con la estructura de la tabla
    am.limite, 
    am.minimo, 
    am.fkTienda, 
    am.padre_id, 
    r.apf, 
    r.TP, 
    am_padre.nombre AS categoria_nombre
FROM cte_hijos AS r
JOIN treematerialescategoria AS am 
    ON TRIM(am.SKU) = r.sku_hijo -- Corregido am.sku a am.SKU
    AND am.fkTienda = ?
LEFT JOIN treematerialescategoria AS am_padre 
    ON am.padre_id = am_padre.id;";

        $stmt = $pdo->prepare($sqlll);
        $stmt->execute([$idPadre, $fkTienda, $fkTienda, $idPadre, $fkTienda]);
        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Detectar si hay algún registro de tipo MO
        $contieneMO = collect($detallecomprobante)->contains('TP', 'MO');

        if ($contieneMO) {
            // Caso MO: Evitamos duplicados limpiando combinaciones idénticas de SKU
            $final = [];
            $skusProcesadosMO = [];

            foreach ($detallecomprobante as $value) {
                if (in_array($value['sku'], $skusProcesadosMO)) {
                    continue; // Saltar si ya agregamos este SKU de Mano de Obra
                }
                $skusProcesadosMO[] = $value['sku'];

                $final[] = [
                    'id'               => 0,
                    'serie'            => '',
                    'categoria_nombre' => $value['nombre'], 
                    'sku'              => $value['sku'],
                    'cantidad'         => $value['limite']
                ];
            }
        } else {
            // Caso Materiales: Buscamos en MovimientoMaterial agrupando por Serie y SKU
            $skus = collect($detallecomprobante)->pluck('sku')->toArray();
            
            $final = MovimientoMaterial::join('treematerialescategoria as tmc', 'tmc.sku', '=', 'movimientomateriales.SKU')
                ->where('movimientomateriales.fkTienda', $fkTienda)
                ->where('fkTecnico', $idtecnico)
                ->whereIn('movimientomateriales.SKU', $skus)
                ->where('movimientomateriales.STATUS', 'I')
                ->select(
                    DB::raw('MAX(movimientomateriales.id) as id'), 
                    'movimientomateriales.serie',
                    'movimientomateriales.CENTRO',
                    'tmc.nombre as categoria_nombre',
                    'tmc.SKU as sku',
                    
                    // 📊 Suma directa de la cantidad física de los movimientos
                    DB::raw('SUM(IFNULL(movimientomateriales.cantidad, 1)) as cantidad')
                )
                ->groupBy(
                    'movimientomateriales.serie', 
                    'movimientomateriales.CENTRO', 
                    'tmc.nombre', 
                    'tmc.sku'
                )
                // ⚠️ IMPORTANTE: Se cambió a 'cantidad' > 0 ya que hereda el alias simple anterior
                ->having('cantidad', '>', 0) 
                ->get();
        }

        return response()->json(is_array($final) ? $final : $final->toArray());

    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


    public function update(UpdateTecnicoRequest $request, Tecnico $tecnico)
    {
        try {
            if (!Auth::check()) {
                return redirect()->route('login');
            }

            DB::beginTransaction();
            $tecnico->load('persona');

            $id = $tecnico->fkpersona;
            Persona::where('id', $id)
                ->update([
                    'razon_social' => $request->name
                ]);

            Tecnico::where('id', $tecnico->id)
                ->update(array_merge($request->validated(), ['nombre' => $request->name]));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }

        return redirect()->route('tecnico.lista')->with('success', 'Tecnico editado');
    }

public function operartrabajo(Request $request, Tecnico $tecnico, Expedientetecnico $expediente)
{
    try {

      DB::connection()->disableQueryLog();


        if (!Auth::check()) {
            return redirect()->route('login');
        }

        DB::beginTransaction();

        // 1. RECUPERACIÓN DE ARRAYS DEL FORMULARIO
        $iditemsInput    = $request->input('arrayiditem', []);
        $skusInput       = $request->input('arraysku', []);
        $cantidadesInput = $request->input('arraycantidad', []);
        $seriesInput     = $request->input('arrayserie', []);
        $nombresInput    = $request->input('arraynameProducto', []);
        $id_tecnico      = $request->input('id_tecnico');
        $eliminadosInput = $request->input('arrayEliminados', []); 
        $iditemsTecnologia = $request->input('arrayidTecnologia', []);
        $fkTienda      = session('user_fkTienda') ?? $expediente->fkTienda;
        $nombreUsuario = session('nombreUsuario') ?? Auth::user()->name ?? 'SISTEMA';
        $ahora         = now();
        $centroTecnico = Tecnico::where('id', $id_tecnico)->value('codigo') ?? 'N/A';

        // =================================================================
        // SECCIÓN A: PROCESAR ELEMENTOS BORRADOS (DEVOLUCIÓN FIFO)
        // =================================================================
        if (!empty($eliminadosInput)) {
            $salidasAEliminar = MovimientoMaterial::whereIn('id', $eliminadosInput)
                ->where('fkExpediente', $expediente->id)
                ->where('TIPOMOVIMIENTO', 'INSTALADO')
                ->get();

            foreach ($salidasAEliminar as $salida) {
                // Revertir el stock al registro de entrada de donde salió originalmente
                $origen = MovimientoMaterial::where('fkTecnico', $id_tecnico)
                    ->where('SKU', $salida->SKU)
                    ->where('serie', $salida->serie)
                    ->where('TIPOMOVIMIENTO', '!=', 'INSTALADO')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($origen) {
                    $origen->increment('cantidad', floatval($salida->cantidad), [
                        'Status'         => 'I',
                        'ESTATUS'        => 'DISPONIBLE',
                        'Modificado_el'  => $ahora,
                        'Modificado_por' => $nombreUsuario
                    ]);
                }

                $salida->delete(); 

                // Eliminar el pago asociado a este registro borrado
                Pagotecnico::where('Orden', $expediente->Orden)
                    ->where('fkTecnico', $id_tecnico)
                    ->where('SKU', $salida->SKU)
                    ->delete();
            }
        }
        // =================================================================
        // SECCIÓN B: PROCESAR ITEMS ACTUALES (AGREGAR NUEVO O MANTIENE)
        // =================================================================
        foreach ($skusInput as $contar => $sku) {
            $cantidadRequerida = floatval($cantidadesInput[$contar] ?? 1);
            $serie             = ($seriesInput[$contar] ?? null) ?: '-';
            $iditem            = $iditemsInput[$contar] ?? 0;
            $skuActual         = strtoupper(trim($sku));
            
            // Excluir Mano de Obra explícita por texto en SKU
            if (empty($skuActual) || str_contains($skuActual, 'MO') || str_contains($skuActual, 'MANO')) {
                continue; 
            }

            // Verificar si el registro ya existe intacto en este expediente
            $yaExisteEnBD = MovimientoMaterial::where('fkExpediente', $expediente->id)
                ->where('SKU', $skuActual)
                ->where('serie', $serie)
                ->where('cantidad', $cantidadRequerida)
                ->where('TIPOMOVIMIENTO', 'INSTALADO')
                ->first();

            if ($yaExisteEnBD) {
                continue; // Saltar al siguiente ítem si no fue modificado
            }

            // Identificar el tipo de ítem de forma segura
            $tipoItem = DB::table('movimientomateriales')
                ->where('SKU', $skuActual)
                ->where('fkTecnico', $id_tecnico)
                ->where('fkTienda', $fkTienda)
                ->where('fkExpediente', $expediente->id)
                ->value('TIPO') ?? 'MO';

            // MANO DE OBRA PURA DIRECTA: Se registra de manera independiente
            if ($iditem == 0 && $tipoItem === 'MO') {
                $manoObra = MovimientoMaterial::firstOrNew([
                    'fkExpediente'   => $expediente->id,
                    'fkTecnico'      => $id_tecnico,
                    'SKU'            => $skuActual,
                    'TIPO'           => 'MO',
                    'serie'          => $serie,
                    'fkTecnologiaarbol' => $iditemsTecnologia[$contar] ?? null,
                ]);

                if (!$manoObra->exists) {
                    $manoObra->Creado_el  = $ahora;
                    $manoObra->Creado_por = $nombreUsuario;
                }

// 1. Obtenemos el código alfanumérico del técnico usando su ID
$tecnicoCodigo = DB::table('tecnico')
    ->where('id', $id_tecnico)
    ->value('codigo') ?? ''; // Si no tiene código, se asume vacío para que no rompa la consulta

// 2. Buscamos el registro de MaterialManoObra con la jerarquía de prioridades
$registroManoObra = Materialmanoobra::where('SKU', $skuActual)
    ->where(function ($query) use ($fkTienda, $tecnicoCodigo) {
        $query->where('centrocostoespecifico', '=', $tecnicoCodigo) // Prioridad 1: Técnico
              ->orWhere('centrocostoespecifico', '=', $fkTienda)    // Prioridad 2: Tienda
              ->orWhereNull('centrocostoespecifico')               // Prioridad 3: Genérico (NULL)
              ->orWhere('centrocostoespecifico', '=', '');         // Prioridad 3: Genérico (Vacío)
    })
    // Ordenamos para que el modelo prioritario quede arriba y sea el que tome first()
    ->orderByRaw("CASE 
        WHEN centrocostoespecifico = ? AND ? != '' THEN 1
        WHEN centrocostoespecifico = ? THEN 2
        ELSE 3 
    END ASC", [$tecnicoCodigo, $tecnicoCodigo, $fkTienda])
    ->first(); // Tomamos el registro específico más óptimo

// 3. Extraemos los valores de forma segura (si no encuentra el SKU, asume 0 y 'UNIDAD')
$costoFinal = $registroManoObra ? $registroManoObra->COSTOPAGO : 0;
$unidadFinal = ($registroManoObra && !empty($registroManoObra->unidadmedida)) ? $registroManoObra->unidadmedida : 'UNIDAD';

// 4. Llenamos el modelo con los datos auditados
$manoObra->fill([
    'fkTienda'       => $fkTienda,
    'cantidad'       => $cantidadRequerida,
    'CENTRO'         => 'CF',
    'ESTATUS'        => 'INSTALADO',
    'almacen'        => 'INSTALACION',
    'TIPOMOVIMIENTO' => 'INSTALADO',
    'Naturaleza'     => 'H',
    'Status'         => 'S', 
    'Lote'           => 'A000',
    'MAC1'           => '-', 
    'MAC2'           => '-', 
    'MAC3'           => '-', 
    'COSTO'          => $costoFinal,
    'unidadmedida'   => $unidadFinal,
    'Modificado_el'  => $ahora,
    'Modificado_por' => $nombreUsuario,
]);


                $manoObra->save();
                continue; // Avanza al siguiente SKU del bucle general
            }
            // -------------------------------------------------------------
            // B.1. BÚSQUEDA EN CASCADA DEL NOMBRE DEL PRODUCTO
            // -------------------------------------------------------------
            $nombreProducto = null;
            $productoExistente = Producto::where('codigo', $skuActual)->where('fkTienda', $fkTienda)->select('nombre')->first();

            if ($productoExistente) {
                $nombreProducto = $productoExistente->nombre;
            } else {
                $materialExiste = Materialmanoobra::where('SKU', $skuActual)->where('fkTienda', $fkTienda)->select('Descripcion')->first();
                if ($materialExiste) {
                    $nombreProducto = $materialExiste->Descripcion;
                } else {
                    $arbMaterialExiste = Arbmanoobra::where('SKU', $skuActual)->where('fkTienda', $fkTienda)->select('nombre')->first();
                    if ($arbMaterialExiste) {
                        $nombreProducto = $arbMaterialExiste->nombre;
                    } else {
                        $treeMateriales = Treematerialescategoria::where('SKU', $skuActual)->where('fkTienda', $fkTienda)->select('nombre')->first();
                        if ($treeMateriales) {
                            $nombreProducto = $treeMateriales->nombre;
                        }
                    }
                }
            }

            // Asegurar la existencia del producto en la tabla maestra
            $producto = Producto::firstOrCreate(
                ['codigo' => $skuActual],
                [
                    'nombre'           => mb_convert_encoding($nombreProducto ?? $nombresInput[$contar] ?? "Producto $skuActual", 'UTF-8', 'ISO-8859-1'),
                    'fkTienda'         => $fkTienda, 
                    'estado'           => 1, 
                    'marca_id'         => 1, 
                    'presentacione_id' => 1,
                    'stock'            => 0, 
                    'precio_base'      => 0,
                    'ClaveVista'       => 'AT',
                    'stock_minimo'     => 1, 
                    'perecedero'       => 0
                ]
            );

                 // =================================================================
            // B.2. BIFURCACIÓN DE PROCESAMIENTO: MANO DE OBRA VS MATERIALES
            // =================================================================
            $serieLimpia = preg_replace('/\s+/', '', strtoupper($serie));
            $esSeriado   = !in_array($serieLimpia, ['-', '0', 'N/A', 'NA', ''], true);
            $docRef      = 'INS-' . $expediente->Orden . ';' . $ahora->format('dmY:H:i:s') . ';' . $serie;
            $POSICION    = str_pad($contar, 4, '0', STR_PAD_LEFT);

            if ($tipoItem === 'MO') {
                // -------------------------------------------------------------
                // CASO A: MANO DE OBRA (Se registra directo, no consume stock)
                // -------------------------------------------------------------
                $manoObraInstalada = MovimientoMaterial::firstOrNew([
                    'fkExpediente'   => $expediente->id,
                    'fkTecnico'      => $id_tecnico,
                    'SKU'            => $skuActual,
                    'TIPO'           => 'MO',
                    'serie'          => $serie,
                    'fkTecnologiaarbol' => $iditemsTecnologia[$contar] ?? null,
                    'TIPOMOVIMIENTO' => 'INSTALADO',
                ]);

                if (!$manoObraInstalada->exists) {
                    $manoObraInstalada->Creado_el  = $ahora;
                    $manoObraInstalada->Creado_por = $nombreUsuario;
                }
// 1. Obtenemos el código alfanumérico del técnico de forma rápida y segura
$tecnicoCodigo = DB::table('tecnico')
    ->where('id', $id_tecnico)
    ->value('codigo') ?? '';

// 2. Buscamos el registro en el catálogo respetando la estricta jerarquía de prioridades
$costoUnidad = Materialmanoobra::where('SKU', $skuActual)
    ->where(function ($query) use ($fkTienda, $tecnicoCodigo) {
        $query->where('centrocostoespecifico', '=', $tecnicoCodigo) // Prioridad 1: Técnico
              ->orWhere('centrocostoespecifico', '=', $fkTienda)    // Prioridad 2: Tienda
              ->orWhereNull('centrocostoespecifico')               // Prioridad 3: Genérico (NULL)
              ->orWhere('centrocostoespecifico', '=', '');         // Prioridad 3: Genérico (Vacío)
    })
    ->select('CATEGORIACOBRO', 'COSTOPAGO', 'Descripcion', 'TIPO', 'unidadmedida', 'centrocostoespecifico')
    // Ordenamos prioritariamente: Técnico (1), Tienda (2), Genérico/Otros (3)
    ->orderByRaw("CASE 
        WHEN centrocostoespecifico = ? AND ? != '' THEN 1
        WHEN centrocostoespecifico = ? THEN 2
        ELSE 3 
    END ASC", [$tecnicoCodigo, $tecnicoCodigo, $fkTienda])
    ->latest() // Si hay colisión exacta en el mismo nivel de prioridad, toma el más reciente
    ->first();

// 3. Cálculo seguro del costo basado en el tipo de registro y la protección contra nulos
$costoFinal = 0;
if ($costoUnidad) {
    $costoFinal = ($costoUnidad->CATEGORIA === 'MANO DE OBRA') 
        ? $costoUnidad->COSTOPAGO 
        : ($costoUnidad->CATEGORIACOBRO ?? 0);
}

// 4. Llenamos y guardamos el modelo con los datos auditados
$manoObraInstalada->fill([
    'fkTienda'       => $fkTienda,
    'cantidad'       => $cantidadRequerida,
    'CENTRO'         => 'CF',
    'ESTATUS'        => 'INSTALADO',
    'almacen'        => 'TRANSITO_INSTALACION',
    'Naturaleza'     => 'H',
    'Status'         => 'S', 
    'Lote'           => 'A000',
    'MAC1'           => '-', 
    'MAC2'           => '-', 
    'MAC3'           => '-', 
    'COSTO'          => $costoFinal,
    'unidadmedida'   => $costoUnidad->unidadmedida ?? 'UNIDAD', // Tomamos la unidad del catálogo o 'UNIDAD' por defecto
    'Modificado_el'  => $ahora,
    'Modificado_por' => $nombreUsuario,
]);

$manoObraInstalada->save();
            



            } else {
                // -------------------------------------------------------------
                // CASO B: MATERIALES (Usa inventario real y lógica FIFO)
                // -------------------------------------------------------------
                $entradasDisponibles = MovimientoMaterial::where('fkTecnico', $id_tecnico)
                    ->where('SKU', $skuActual)
                    ->where('TIPOMOVIMIENTO', '!=', 'INSTALADO')
                    ->where('cantidad', '>', 0)
                    ->where('Status', 'I') 
                    ->where('TIPO', 'MA') 
                    ->when($esSeriado, function ($query) use ($serie) {
                        return $query->where('serie', trim($serie));
                    })
                    ->orderBy('created_at', 'asc')
                    ->get();

                $porDescontar = $cantidadRequerida;

                foreach ($entradasDisponibles as $entrada) {
                    if ($porDescontar <= 0) {
                        break;
                    }

                    $cantidadAExtraer = min($entrada->cantidad, $porDescontar);

                    // Determinar si es un misceláneo con stock remanente en la entrada
                    if ($entrada->cantidad > $cantidadAExtraer && !$esSeriado) {
                        // Restar stock parcial manteniendo el registro disponible
                        $entrada->decrement('cantidad', $cantidadAExtraer, [
                            'Modificado_el'  => $ahora,
                            'Modificado_por' => $nombreUsuario
                        ]);
                    } else {
                        // Agotar por completo el registro de entrada
                        $entrada->decrement('cantidad', $cantidadAExtraer);
                        $entrada->refresh();
                        $entrada->update([
                            'Status'         => 'A',
                            'ESTATUS'        => 'AGOTADO',
                            'Modificado_el'  => $ahora,
                            'Modificado_por' => $nombreUsuario
                        ]);
                    }



                    // Registrar o Clonar el movimiento del Técnico a INSTALADO
                    if ($entrada->getOriginal('cantidad') > $cantidadAExtraer && !$esSeriado) {
                        // Insertar nuevo renglón histórico de lo instalado para el misceláneo
                        DB::table('movimientomateriales')->insert([
                            'fkExpediente'   => $expediente->id,
                            'fkTecnico'      => $id_tecnico,
                            'fkTienda'       => $fkTienda,
                            'SKU'            => $skuActual,
                            'serie'          => $serie,
                            'cantidad'       => $cantidadAExtraer,
                            'TIPO'           => $entrada->TIPO,
                            'ESTATUS'        => 'TRANSITO_INSTALACION',
                            'Status'         => 'I',
                            'Modificado_el'  => $ahora,
                            'fkTecnologiaarbol' => $iditemsTecnologia[$contar] ?? null,
                            'Modificado_por' => $nombreUsuario,
                            'created_at'     => $ahora,
                            'updated_at'     => $ahora
                        ]);
                    } else {
                        // Marcar el registro existente como consumado (Seriado o Misceláneo agotado)
                        DB::table('movimientomateriales')
                            ->where('id', $entrada->id)
                            ->update([
                                'fkExpediente'   => $expediente->id,
                                'ESTATUS'        => 'AGOTADO',
                                'Status'         => 'A',
                                'Modificado_el'  => $ahora,
                                'fkTecnologiaarbol' => $iditemsTecnologia[$contar] ?? null,
                                'Modificado_por' => $nombreUsuario,
                                'updated_at'     => $ahora
                            ]);
                    }

                    // Asignar el material de forma definitiva a la Planta Externa / Cliente
                    DB::table('movimientomateriales')->updateOrInsert(
                        [
                            'serie'    => $serie,
                            'SKU'      => $skuActual,
                            'fkTienda' => $fkTienda,
                            'fkTecnologiaarbol' => $iditemsTecnologia[$contar] ?? null,
                        ],
                        [
                            'almacen'         => 'CLIENTE_FINAL',
                            'Lote'            => 'N/A',
                            'COSTO' => ($costoUnidad->TIPO === 'MANO DE OBRA') ? $costoUnidad->COSTOPAGO : ($costoUnidad->CATEGORIACOBRO ?? 0),
                            'TIPO'            => $tipoItem,
                            'ESTATUS'         => 'INSTALADO',
                            'Status'          => 'S',
                            'Naturaleza'      => 'H',
                            'CENTRO'          => $centroTecnico,
                            'cantidad'        => $cantidadAExtraer,
                            'unidadmedida'    => $costoUnidad->unidadmedida ?? 'UNIDAD',
                            'TIPOMOVIMIENTO'  => 'CONSUMO_INSTALACION',
                            'Modificado_el'   => $ahora->format('Y-m-d'),
                            'Modificado_por'  => $nombreUsuario,
                            'updated_at'      => $ahora
                        ]
                    );


                    $porDescontar -= $cantidadAExtraer;
                } // Fin del bucle foreach ($entradasDisponibles)
            } // Fin de la bifurcación de Tipo de Ítem (MO vs MA)

        } // <<< AQUÍ TERMINA DE MANERA CORRECTA EL FOREACH GENERAL DE SKUS >>>

                    // -------------------------------------------------------------
            // B.4. PROCESAMIENTO DE IMÁGENES / EVIDENCIAS
            // -------------------------------------------------------------
            $photos = $request->input("items.{$contar}.photos", []);
            $names  = $request->input("items.{$contar}.names", []);
            $iditemsTecnologia = $request->input("items.{$contar}.fkTecnologia", []);

            if (!empty($photos) && is_array($photos)) {
                foreach ($photos as $i => $photoBase64) {
                    if (preg_match('/^data:image\/(\w+);base64,/', $photoBase64, $typeMatch)) {
                        $extension = strtolower($typeMatch[1]); 
                        $fileData = base64_decode(substr($photoBase64, strpos($photoBase64, ',') + 1));

                        if ($fileData) {
                            $nombreFotoLetras = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $names[$i] ?? 'foto');
                            $nombreProductoLetras = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombreProducto ?? $skuActual);
                            $idtec=$iditemsTecnologia[$i] ?? '0';

                            $nombreLimpio = preg_replace('/[^A-Za-z0-9\-]/', '_', $nombreFotoLetras);
                            $productoNombreLimpio = preg_replace('/[^A-Za-z0-9\-]/', '_', $nombreProductoLetras);
                            $nombreLimpio = preg_replace('/_+/', '_', $nombreLimpio);
                            $productoNombreLimpio = preg_replace('/_+/', '_', $productoNombreLimpio);

                            $fileName = trim($nombreLimpio, '_') . "_" . trim($productoNombreLimpio, '_') . "_" . uniqid() . ".{$extension}";
                            $gcsPath = "fotos/ordenes/{$expediente->Orden}/{$fileName}";

                            Storage::disk('gcs_images')->put($gcsPath, $fileData, 'public');
                            $urlFotografia = Storage::disk('gcs_images')->url($gcsPath);
             
                            Expedientefotograficotecnico::create([
                                'fkTienda'   => $fkTienda,
                                'Orden'      => $expediente->Orden,
                                'fotografia' => $urlFotografia, 
                                'fkTecnologia' => $idtec,
                            ]);
                            unset($fileData); 
                        }
                    }
                }
            }

        // =================================================================
        // SECCIÓN C: FINALIZACIÓN Y AUDITORÍA DEL EXPEDIENTE
        // =================================================================
        if ($request->input('estatus') === 'S') {
        foreach ($skusInput as $contar => $sku) {
            $cantidadRequerida = floatval($cantidadesInput[$contar] ?? 1);
            $serie             = ($seriesInput[$contar] ?? null) ?: '-';
            $iditem            = $iditemsInput[$contar] ?? 0;
            
            $skuActual         = strtoupper(trim($sku));
            $docRef      = 'INS-' . $expediente->Orden . ';' . $ahora->format('dmY:H:i:s') . ';' . $serie;
            $POSICION    = str_pad($contar+1, 4, '0', STR_PAD_LEFT);

                        // Identificar el tipo de ítem de forma segura
            $tipoItem = DB::table('movimientomateriales')
                ->where('SKU', $skuActual)
                ->where('fkTecnico', $id_tecnico)
                ->where('fkTienda', $fkTienda)
                ->where('fkExpediente', $expediente->id)
                ->value('TIPO') ?? 'MO';
            
            // Excluir Mano de Obra explícita por texto en SKU


            $producto = Producto::where('codigo', $skuActual)->where('fkTienda', $fkTienda)->first();
                        

                                            // Cálculo seguro del costo a pagar de Mano de Obra (Evita duplicados)
// 1. Obtenemos el código alfanumérico del técnico de forma rápida
$tecnicoCodigo = DB::table('tecnico')
    ->where('id', $id_tecnico)
    ->value('codigo') ?? '';

// 2. Buscamos el registro en el catálogo respetando la estricta jerarquía de prioridades
$costoUnidad = Materialmanoobra::where('SKU', $skuActual)
    ->where(function ($query) use ($fkTienda, $tecnicoCodigo) {
        $query->where('centrocostoespecifico', '=', $tecnicoCodigo) // Prioridad 1: Técnico
              ->orWhere('centrocostoespecifico', '=', $fkTienda)    // Prioridad 2: Tienda
              ->orWhereNull('centrocostoespecifico')               // Prioridad 3: Genérico (NULL)
              ->orWhere('centrocostoespecifico', '=', '');         // Prioridad 3: Genérico (Vacío)
    })
    ->select('CATEGORIACOBRO', 'COSTOPAGO', 'Descripcion', 'TIPO', 'unidadmedida', 'centrocostoespecifico','CATEGORIA')
    // Ordenamos prioritariamente: Técnico (1), Tienda (2), Genérico (3)
    ->orderByRaw("CASE 
        WHEN centrocostoespecifico = ? AND ? != '' THEN 1
        WHEN centrocostoespecifico = ? THEN 2
        ELSE 3 
    END ASC", [$tecnicoCodigo, $tecnicoCodigo, $fkTienda])
    ->latest()
    ->first();

// 3. Historial de Movimiento de Servicio (Solo si el estatus es 'MO')
if ($tipoItem === "MO") {

    // Calculamos el precio unitario base de forma segura protegiendo contra nulos
    $precioUnitario = 0;
    if ($costoUnidad) {
        $precioUnitario = ($costoUnidad->CATEGORIA === 'MANO DE OBRA') 
            ? $costoUnidad->COSTOPAGO 
            : ($costoUnidad->CATEGORIACOBRO ?? 0);
    }

    // Ejecutamos el updateOrCreate con el cálculo matemático corregido
    Pagotecnico::updateOrCreate(
        [
            'Orden'     => $expediente->Orden,
            'SKU'       => $skuActual,
            'fkTienda'  => $fkTienda,
            'fkTecnico' => $id_tecnico,
            'Naturaleza' => 'H',
        ], 
        [
            'Descripcion' => $producto->nombre ?? $costoUnidad->Descripcion ?? "Servicio $skuActual",
            'OBS'         => 'Pago por servicio tecnico (Mano de Obra)',
            'Cantidad'    => $cantidadRequerida,
            // Multiplicación limpia y segura entre la cantidad y el precio unitario obtenido
            'COSTOPAGO'   => $cantidadRequerida * $precioUnitario,
            'Status'      => 'S',
        ]
    );  
}


                        $updateData = [
                            'Status'           => 'A',
                            'ESTATUS'          => 'C',
                            'AUTORIZA'         => $id_tecnico,
                            'FECHAINSTALACION' => $ahora,
                        ];      

                        DB::table('movimientomateriales')
                            ->where('fkExpediente', $expediente->id)
                            ->where('fkTecnico', $id_tecnico)
                            ->update([
                                'ESTATUS'        => 'INSTALADO_CERRADO',
                                'Status'         => 'A',
                                'ALMACEN'        => 'CLIENTE_FINAL',
                                'Modificado_el'  => $ahora,
                                'Modificado_por' => $nombreUsuario,
                                'updated_at'     => $ahora
                        ]);                                               

                if (empty($tipoItem) || str_contains($tipoItem, 'MO') || str_contains($tipoItem, 'MANO')) {
                    continue; 
                }

                        // Registrar Historial de Salida Negativa (Clase 251)
                        MovimientoMateriales::create([
                            'fkTienda'               => $fkTienda,
                            'fkMateriales'           => $producto->id,
                            'contrata'               => $id_tecnico,
                            'clase_movimiento'       => '221',
                            'cantidad'               => $cantidadRequerida * -1,
                            'referencia'             => "CONSUMO INSTALACION | EXPEDIENTE: " . $expediente->id . " | SERIE: $serie",
                            'tipo_movimiento'        => 'CONSUMO_INSTALACION',
                            'documento_material'     => $docRef,
                            'posicion_documento'     => $POSICION,
                            'fecha_contabilizacion'  => $ahora->format('Y-m-d'),
                            'almacen'                => 'CLIENTE_FINAL',
                            'centro'                 => $centroTecnico,
                            'unidad_medida_base'     => $costoUnidad->unidadmedida ?? 'UNIDAD',
                            'centro_sap'              => session('centro'),
                            'origen_uso'             => 'consumo_instalacion',
                            'texto_clase_movimiento' => 'Salida por instalación a cliente final'
                        ]);                   
       

        }
        } else {
            $updateData = [
                'Status'           => 'S',
                'ESTATUS'          => 'I',
                'AUTORIZA'         => $id_tecnico,
                'FECHAINSTALACION' => $ahora,
            ];
        }

        if ($request->filled('obs') && trim($request->input('obs')) !== '') {
            $nuevaObs = 'OBS TECNICO: ' . trim($request->input('obs'));
            
            // Si ya existe una observación previa, se concatena con ' || '; de lo contrario, se asigna limpia
            $updateData['OBS'] = !empty($expediente->OBS) 
                ? $expediente->OBS . ' || ' . $nuevaObs 
                : $nuevaObs;
        }

        $expediente->update($updateData);

        DB::commit();

        // Forzamos que la variable sea el ID numérico limpio enviado por el request
        $idBucketDestino = intval($id_tecnico); 

        // Calculamos la URL exacta de forma explícita
        $urlDestino = route('tecnico.bucket', ['usbucket' => $idBucketDestino]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => 'success', 
                'message'  => 'Orden actualizada con éxito vía FIFO.',
                'redirect' => $urlDestino // Enviará: verbtecnico/{id}/ver-bucket
            ], 200);
        }
        
        return redirect()->to($urlDestino)
            ->with('success', 'Orden actualizada con éxito vía FIFO.');
        
    } catch (Exception $e) {
        DB::rollBack();
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => 'error', 
                'message' => $e->getMessage()
            ], 500);
        }
        
        return redirect()->back()
            ->withInput()
            ->with('error', 'Ocurrió un error en el proceso: ' . $e->getMessage());
    }
}


    public function fetchrelacionTecnico(Request $request)
{
    try{

    DB::connection()->disableQueryLog();
    
                        if(!Auth::check()){
            return redirect()->route('login');
        }

                    $Estatus = session('user_estatus');
                    $fkTienda = session('user_fkTienda');
                     $idtecnico = Tecnico::where('fkuser', Auth()->id())->value('id');
                    $fechain=$request->input('fechain');
                    $fechafin=$request->input('fechafin');

                    if(isset($fechain) or isset($fechafin)){
                if ($Estatus == 'ER') {

            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->where('ESTATUS','I')
            ->paginate(10);

                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->where('ESTATUS','I')
            ->paginate(10);
                };
                    }else{
                if ($Estatus == 'ER') {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$request->input('id'))->paginate(10);
                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)->paginate(10);
                };
                    }





    if ($request->ajax()) {
        return view('buckettecnico.table.tabla', compact('relacion'))->render();
    }
    }catch(Exception $e){
    return view('tecnico.index', compact('relacion','Error: '.$e->getMessage()));
    }


}

public function fetchrelacionS(Request $request)
{
    DB::connection()->disableQueryLog();
    try{
                        if(!Auth::check()){
            return redirect()->route('login');
        }

                    $Estatus = session('user_estatus');
                    $fkTienda = session('user_fkTienda');
                    $idtecnico= $request->input('id');
                    $fechain=$request->input('fechainS');
                    $fechafin=$request->input('fechafinS');

                    if(isset($fechain) or isset($fechafin)){
                if ($Estatus == 'ER') {

            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)
            ->where('Status','S')
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->paginate(25);

                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)
            ->where('Status','S')
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->paginate(25);
                };
                    }else{
                if ($Estatus == 'ER') {

            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)
            ->where('Status','S')
            ->where('fkTecnico',$idtecnico)->paginate(25);

                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)
            ->where('Status','S')
            ->where('fkTecnico',$idtecnico)->paginate(25);
                };
                    }





    if ($request->ajax()) {
        return view('buckettecnico.table.tablaexpediente', compact('relacion'))->render();
    }
    }catch(Exception $e){
    return view('tecnico.index', compact('relacion','Error: '.$e->getMessage()));
    }


}

public function fetchrelacionP(Request $request)
{
    DB::connection()->disableQueryLog();
    try {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $Estatus   = session('user_estatus');
        // Nota: Asegúrate si en tu sesión es user_fkTienda o user_fktienda (en tus anteriores prompts usaste minúscula)
        $fkTienda  = session('user_fkTienda') ?? session('user_fktienda'); 
        $idtecnico = $request->input('id');
        $fechain   = $request->input('fechainP');
        $fechafin  = $request->input('fechafinP');

        $query = Pagotecnico::with(['arbolmanoobra' => function($q) {
                $q->select('SKU', 'nombre as descripcion'); // Asegúrate de incluir la FK/PK para la relación en el select si falla
            }])
            ->where('fkTecnico', $idtecnico)
            ->whereNotNull('fkTecnico')
            ->whereHas('arbolmanoobra', function($q) {
                $q->where('Tipo_servicio', 'MO');
            });

        // CORRECCIÓN: Agrupación del OR para no romper los filtros de Tienda, Técnico y Fechas
        $query->where(function($q) {
            $q->where('Naturaleza', 'H')
              ->orWhere('Status', 'S');
        });

        if ($Estatus !== 'ER') {
            $query->where('fkTienda', $fkTienda);
        }

        if ($fechain && $fechafin) {
            $inicio = Carbon::parse($fechain)->startOfDay();
            $fin = Carbon::parse($fechafin)->endOfDay();
            $query->whereBetween('created_at', [$inicio, $fin]);
        }

        $relacion = $query->paginate(10);

        return view('buckettecnico.table.tablapago', compact('relacion'))->render();

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function fetchrelacionC(Request $request)
{
    DB::connection()->disableQueryLog();
    try {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $Estatus   = session('user_estatus');
        $fkTienda  = session('user_fkTienda') ?? session('user_fktienda');
        $idtecnico = $request->input('id');
        $fechain   = $request->input('fechainC');
        $fechafin  = $request->input('fechafinC');

        // Se eliminaron las condiciones repetidas de fkTecnico y fkTienda
        $query = Pagotecnico::where('fkTecnico', $idtecnico)
            ->whereNotNull('fkTecnico')
            ->where('Status', 'S')
            ->where('Naturaleza', 'D');

        if ($Estatus !== 'ER') {
            $query->where('fkTienda', $fkTienda);
        }

        if ($fechain && $fechafin) {
            $inicio = Carbon::parse($fechain)->startOfDay();
            $fin = Carbon::parse($fechafin)->endOfDay();
            $query->whereBetween('created_at', [$inicio, $fin]);
        }

        $relacion = $query->paginate(10);

        return view('buckettecnico.table.tablacobro', compact('relacion'))->render();

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}





    public function fetchrelacioninv(Request $request)
{
    DB::connection()->disableQueryLog();
    try{
                        if(!Auth::check()){
            return redirect()->route('login');
        }

                    $Estatus = session('user_estatus');
                    $fkTienda = session('user_fkTienda');
                    $idtecnico= $request->input('id');


   $relacion = MovimientoMaterial::with(['treematerialcategoria' => function($query) {
                // Solo traer columnas necesarias
                $query->select('SKU', 'nombre as descripcion');
            }])
            ->where('fkTienda', $fkTienda)
            ->where('ESTATUS', 'DISPONIBLE')
            ->where('fkTecnico', $idtecnico)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('count', 15));


    if ($request->ajax()) {
        return view('buckettecnico.table.tablainv', compact('relacion'))->render();
    }
    }catch(Exception $e){
    return view('tecnico.index', compact('relacion','Error: '.$e->getMessage()));
    }


}

public function exportar(Request $request)
{
DB::connection()->disableQueryLog();
                if(!Auth::check()){
            return redirect()->route('login');
        }


        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

                $request->validate([
                    'fechaincio' => 'required|date',
                    'fechafin' => 'required|date|after_or_equal:fechaincio',
                    ]);

                $inicio = Carbon::parse($request->fechaincio)->startOfDay();
                $fin = Carbon::parse($request->fechafin)->endOfDay();

                  if ($Estatus == 'ER') {

                $datos = Expedientetecnico::whereBetween('FECHAINSTALACION', [$inicio, $fin])
                ->get();

                } else {
                $datos = Expedientetecnico::where('fkTienda', $fkTienda)
                ->whereBetween('FECHAINSTALACION', [$inicio, $fin])
                ->get();
                }



    // Encabezado del CSV
    $csv = "fkTienda,Orden,virtual,Status,Tipo_servicio,Tipo_orden,NOMBRECLIENTE,DIRECCION,OBS,SIGLASCENTRAL,AREA,FECHAINSTALACION,created_at,updated_at,fkTecnico,AUTORIZA,ESTATUS,TECNOLOGIA\n";

    // Agregar datos
    foreach ($datos as $item) {

        $csv .= implode(",", [
            $item->fkTienda,
            $item->Orden,
            $item->virtual,
            $item->Status,
            $item->Tipo_servicio,
            $item->Tipo_orden,
            $item->NOMBRECLIENTE,
            $item->DIRECCION,
            $item->OBS,
            $item->SIGLASCENTRAL,
            $item->AREA,
            $item->FECHAINSTALACION,
            $item->created_at,
            $item->updated_at,
            $item->fkTecnico,
            $item->Autoriza,
            $item->ESTATUS,
            $item->TECNOLOGIA
        ]) . "\n";
    }

    // Retornar respuesta para descarga
    $nombreArchivo = 'tecnicosordenes_export_' . now()->format('Ymd_His') . '.csv';

    return Response::make($csv, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$nombreArchivo\"",
    ]);
}

public function exportarPagoTecnico(Request $request, $naturaleza) 
{
    DB::connection()->disableQueryLog();
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    // Validar que la naturaleza sea estrictamente D o H
    if (!in_array($naturaleza, ['D', 'H'])) {
        abort(404, 'Naturaleza no válida.');
    }

    $fkTienda = session('user_fktienda');
    $estatus = session('user_estatus');

    $request->validate([
        'fechaincio' => 'required|date',
        'fechafin' => 'required|date|after_or_equal:fechaincio',
    ]);

    $inicio = Carbon::parse($request->fechaincio)->startOfDay();
    $fin = Carbon::parse($request->fechafin)->endOfDay();

    // Construcción de la consulta para la tabla pagotecnico
    $query = DB::table('pagotecnico')
        ->select([
            'id', 'Orden', 'SKU', 'Descripcion', 'OBS', 'Cantidad', 
            'COSTOPAGO', 'created_at', 'updated_at', 'fkTienda', 
            'fkTecnico', 'Naturaleza', 'Status'
        ])
        ->where('Naturaleza', $naturaleza)
        ->whereBetween('created_at', [$inicio, $fin]); // O cambia a updated_at si es preferible

    // Filtrado por tienda si el usuario no tiene estatus 'er'
    if ($estatus !== 'er') {
        $query->where('fkTienda', $fkTienda);
    }

    $datos = $query->get();
    $nombreArchivo = 'pagotecnico_' . strtolower($naturaleza) . '_export_' . now()->format('Ymd_His') . '.csv';

    // Generar el contenido del CSV de forma segura en memoria
    $handle = fopen('php://memory', 'r+');
    
    // UTF-8 BOM para soporte de acentos en Excel
    fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

    // Encabezados del CSV
    fputcsv($handle, [
        'id', 'Orden', 'SKU', 'Descripcion', 'OBS', 'Cantidad', 
        'COSTOPAGO', 'created_at', 'updated_at', 'fkTienda', 
        'fkTecnico', 'Naturaleza', 'Status'
    ]);

// 1. Cargamos todos los códigos de técnicos en un mapa en memoria (Colección asociativa)
// Esto reduce cientos de consultas SQL a una sola consulta ultrarápida antes del bucle
$tecnicosCodigos = DB::table('tecnico')
    ->whereIn('id', collect($datos)->pluck('fkTecnico')->filter()->unique())
    ->pluck('codigo', 'id')
    ->toArray();

// 2. Procesamos e insertamos las filas en el archivo CSV
foreach ($datos as $item) {
    
    // Obtenemos el código del técnico desde nuestro mapa en memoria
    $tecnicoCodigo = $tecnicosCodigos[$item->fkTecnico] ?? '';

    // 3. Buscamos el costo aplicando de forma idéntica la jerarquía de prioridades
    $costoUnidad = Materialmanoobra::where('SKU', $item->SKU)
        ->where(function ($query) use ($fkTienda, $tecnicoCodigo) {
            $query->where('centrocostoespecifico', '=', $tecnicoCodigo) // Prioridad 1: Técnico
                  ->orWhere('centrocostoespecifico', '=', $fkTienda)    // Prioridad 2: Tienda
                  ->orWhereNull('centrocostoespecifico')               // Prioridad 3: Global (NULL)
                  ->orWhere('centrocostoespecifico', '=', '');         // Prioridad 3: Global (Vacío)
        })
        ->select('CATEGORIACOBRO', 'COSTOPAGO', 'TIPO', 'centrocostoespecifico')
        ->orderByRaw("CASE 
            WHEN centrocostoespecifico = ? AND ? != '' THEN 1
            WHEN centrocostoespecifico = ? THEN 2
            ELSE 3 
        END ASC", [$tecnicoCodigo, $tecnicoCodigo, $fkTienda])
        ->latest()
        ->first();

    // 4. Determinamos el costo final validando de forma segura contra nulos
    $costoFinal = $item->COSTOPAGO; // Valor por defecto si no existe en el catálogo
    if ($costoUnidad) {
        $costoFinal = ($costoUnidad->CATEGORIA === 'MANO DE OBRA') 
            ? $costoUnidad->COSTOPAGO 
            : ($costoUnidad->CATEGORIACOBRO ?? $item->COSTOPAGO);
    }

    // 5. Escribimos la fila directamente en el puntero del archivo CSV
    fputcsv($handle, [
        $item->id, 
        $item->Orden, 
        $item->SKU, 
        $item->Descripcion, 
        $item->OBS, 
        $item->Cantidad, 
        $costoFinal, // Costo auditado con la prioridad correcta
        $item->created_at, 
        $item->updated_at, 
        $item->fkTienda, 
        $item->fkTecnico, 
        $item->Naturaleza, 
        $item->Status
    ]);
}


    // Leer el contenido generado
    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    // Retornar la respuesta tal como la necesitas
    return Response::make($csv, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$nombreArchivo\"",
        'Pragma' => 'no-cache',
        'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        'Expires' => '0',
    ]);
}


    public function bucketlista()
    {
        DB::connection()->disableQueryLog();
            if(!Auth::check()){
                return redirect()->route('login');
            }
        try {
            DB::beginTransaction();

                    $Estatus = session('user_estatus');
                    $fkTienda = session('user_fkTienda');

            $idtecnico = Tecnico::where('fkuser', Auth()->id())
            ->value('id');
            $tecnicos = Tecnico::where('fkuser', Auth()->id())
            ->get();


                if ($Estatus == 'ER') {
                    $tecnicos=Tecnico::where('fkTienda',$fkTienda)->get();
                    $expediente=Expedientetecnico::where('fkTienda',$fkTienda)
                    ->where('ESTATUS','A')
                    ->get();
                    $tecnico=null;
                } else {
                    $tecnico=null;
                    $tecnico=Tecnico::where('fkTienda',$fkTienda)
                    ->where('id',$idtecnico)->first();
                    $expediente=Expedientetecnico::where('fkTienda',$fkTienda)
                    ->where('ESTATUS','A')
                    ->where('fkTecnico',$idtecnico)->get();
                };

            DB::commit();

            return view('buckettecnico.index', compact('tecnicos','tecnico','expediente','Estatus'));

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }


    }
public function importarMAMO(Request $request)
{
      DB::connection()->disableQueryLog();

    if (!Auth::check()) return redirect()->route('login');

    $fkTienda = session('user_fkTienda');
    $idDestino = $request->input('id'); // Técnico que recibe las órdenes
    $nombreUsuario = session('nombreUsuario');

    $request->validate([
        'archivo' => 'required|file|mimes:csv,txt',
    ]);

    $file = fopen($request->file('archivo')->getRealPath(), 'r');
    $encabezado = fgetcsv($file); 

    DB::beginTransaction();
    try {
        $fila = 1;
        while (($linea = fgetcsv($file)) !== false) {
            $fila++;
            $data = array_combine($encabezado, $linea);

            // 1. VALIDACIÓN DE CAMPOS CRÍTICOS
            if (empty($data['Orden']) || empty($data['virtual'])) continue;

            $orden = trim($data['Orden']);
            $virtual = trim($data['virtual']);
            $ahora = now();

            // 2. TRATAMIENTO DE FECHA
            $fechaInst = null;
            if (!empty($data['FECHAINSTALACION'])) {
                try {
                    $fechaInst = Carbon::createFromFormat('d/m/Y', $data['FECHAINSTALACION'])->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $fechaInst = $ahora; 
                }
            }

            // 3. LOGICA DE REASIGNACIÓN (Trazabilidad)
            // Buscamos si la orden ya existe y está activa con otro técnico
            $expedientePrevio = DB::table('expedientetecnico')
                ->where('orden', $orden)
                ->where('virtual', $virtual)
                ->where('fkTienda', $fkTienda)
                ->where('Estatus', '!=', 'RE') // Evitamos los ya procesados
                ->first();

            if ($expedientePrevio) {
                // Si el técnico es el mismo, solo actualizamos datos y saltamos
                if ($expedientePrevio->fkTecnico == $idDestino) {
                    DB::table('expedientetecnico')->where('id', $expedientePrevio->id)->update([
                        'status' => $data['Status'] ?? $expedientePrevio->status,
                        'updated_at' => $ahora
                    ]);
                    continue;
                }

                // Si es un técnico diferente, "cerramos" el expediente anterior
                DB::table('expedientetecnico')
                    ->where('id', $expedientePrevio->id)
                    ->update([
                        'Estatus' => 'RE',
                        'obs' => ($expedientePrevio->OBS . " | Reasignada a técnico ID: $idDestino por $nombreUsuario"),
                        'updated_at' => $ahora
                    ]);
            }

            // 4. INSERTAR LA ORDEN PARA EL NUEVO TÉCNICO
            // Usamos insert para mantener el historial de quién ha tenido la orden
            DB::table('expedientetecnico')->insert([
                'orden'            => $orden,
                'virtual'          => $virtual,
                'fkTienda'         => $fkTienda,
                'fkTecnico'        => $idDestino,
                'status'           => $data['Status'] ?? 'PENDIENTE',
                'tipo_servicio'    => mb_convert_encoding($data['Tipo_servicio'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'tipo_orden'       => mb_convert_encoding($data['Tipo_orden'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'nombrecliente'    => mb_convert_encoding($data['NOMBRECLIENTE'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'direccion'        => mb_convert_encoding($data['DIRECCION'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'obs'              => mb_convert_encoding($data['OBS'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'SIGLASCENTRAL'    => $data['SIGLASCENTRAL'] ?? '',
                'area'             => $data['AREA'] ?? '',
                'FECHAINSTALACION' => $fechaInst,
                'autoriza'         => $data['AUTORIZA'] ?? '',
                'Estatus'          => $data['ESTATUS'] ?? 'AC',
                'TECNOLOGIA'       => $data['TECNOLOGIA'] ?? '',
                'created_at'       => $ahora,
                'updated_at'       => $ahora,
            ]);
        }

        fclose($file);
        DB::commit();
        return back()->with('success', 'Expedientes técnicos procesados y reasignados correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        if (isset($file)) fclose($file);
        Log::error('Error al importar Expediente: ' . $e->getMessage());
        return back()->with('error', 'Error en fila ' . $fila . ': ' . $e->getMessage());
    }
}


public function importarInvTecnico(Request $request)
{
    DB::connection()->disableQueryLog();

    if (!Auth::check()) return redirect()->route('login');

    $fkTienda = session('user_fkTienda');
    $idDestino = $request->input('id'); 
    $nombreUsuario = session('nombreUsuario');
    $CentroDestino=Tecnico::where('id', $idDestino)->value('codigo') ?? 'N/A';
    
    $request->validate(['archivoinv' => 'required|file|mimes:csv,txt']);
    $file = fopen($request->file('archivoinv')->getRealPath(), 'r');
    $encabezado = fgetcsv($file); 

    DB::beginTransaction();
    try {
        $fila = 1;
        $instaladosContador = 0;

        while (($linea = fgetcsv($file)) !== false) {
            $fila++;
            $data = array_combine($encabezado, $linea);
            if (empty($data['SKU']) || empty($data['cantidad'])) continue;

            $sku = trim($data['SKU']);
            $serie = trim($data['serie'] ?? '');
            $cantidad = floatval($data['cantidad']);
            $docRef = 'IMP-' . $CentroDestino . ';' . now()->format('dmY:H:i:s') . ';' . $serie;
            $ahora = now();

            // 1. OBTENER O CREAR PRODUCTO
            $producto = Producto::firstOrCreate(
                ['codigo' => $sku],
                [
                    'nombre' => mb_convert_encoding($data['descripcion'] ?? "Producto $sku", 'UTF-8', 'ISO-8859-1'),
                    'fkTienda' => $fkTienda, 'estado' => 1, 'marca_id' => 1, 'presentacione_id' => 1,
                    'stock' => 0, 'precio_base' => 0, 'stock_minimo' => 1, 'perecedero' => 0
                ]
            );

            // 2. IMPEDIR TRASPASO SI ESTÁ INSTALADO
            $stockActual = DB::table('movimientomateriales')
                ->where('serie', $serie)
                ->where('SKU', $sku)
                ->where('fkTienda', $fkTienda)
                ->where('Status', 'A')
                ->first();

            if ($stockActual && $stockActual->ESTATUS == 'INSTALADO') {
                $instaladosContador++;
                continue; 
            }

            // 3. BUSCAR ÚLTIMO DUEÑO (Historial)
            $ultimoMov = MovimientoMateriales::where('fkMateriales', $producto->id)
                ->where('referencia', 'LIKE', "%$serie%")
                ->where('fkTienda', $fkTienda)
                ->orderBy('id', 'desc')->first();

            $idOrigen = $ultimoMov ? $ultimoMov->contrata : null;
            $CentroOrigen=Tecnico::where('id', $idOrigen)->value('codigo') ?? 'N/A';
            if ($idOrigen == $idDestino) continue;

            // 4. REGISTRAR SALIDA DEL ANTERIOR
            if ($idOrigen) {
                MovimientoMateriales::create([
                    'fkTienda' => $fkTienda, 'fkMateriales' => $producto->id, 'contrata' => $idOrigen,
                    'clase_movimiento' => '221', 'cantidad' => $cantidad * -1,
                    'referencia' => "SALIDA SERIE: $serie | TRASPASO A $idDestino",
                    'tipo_movimiento' => 'TRASPASO_SALIDA', 'documento_material' => $docRef,
                    'posicion_documento' => '0001', 'fecha_contabilizacion' => $ahora->format('Y-m-d'),
                    'almacen' => $CentroOrigen, 'centro' => $data['CENTRO'] ?? 'G817',
                    'unidad_medida_base' => $data['unidadmedida'] ?? 'PZA'
                ]);

                DB::table('movimientomateriales')
                    ->where('serie', $serie)
                    ->where('SKU', $sku)
                    ->where('fkTecnico', $idOrigen)
                    ->update([
                        'ESTATUS' => 'TRASLADADO',
                        'Status' => 'I',
                        'updated_at' => $ahora
                    ]);
            }

            // 5. REGISTRAR ENTRADA EN HISTORIAL (Destino)
            MovimientoMateriales::create([
                'fkTienda' => $fkTienda, 'fkMateriales' => $producto->id, 'contrata' => $idDestino,
                'clase_movimiento' => '641' ? '641' : '101', 'cantidad' => $cantidad,
                'referencia' => "ENTRADA SERIE: $serie | ORIGEN: " . ($idOrigen ?? 'BODEGA'),
                'tipo_movimiento' => 'TRASPASO_ENTRADA', 'documento_material' => $docRef,
                'posicion_documento' => '0001', 'fecha_contabilizacion' => $ahora->format('Y-m-d'),
                'centro' => $data['CENTRO'] ?? 'G817', 'almacen' => $CentroDestino,
                'unidad_medida_base' => $data['unidadmedida'] ?? 'PZA'
            ]);

                                $costoUnidad = Materialmanoobra::where('SKU', $sku)
                                    ->where('fkTienda', $fkTienda)
                                    ->select('CATEGORIACOBRO','COSTOPAGO', 'Descripcion', 'TIPO', 'unidadmedida') // Agrega aquí las columnas que ocupes
                                    ->latest()
                                    ->first();                 
            // 6. ASIGNAR STOCK AL NUEVO TÉCNICO (Blindado contra Error 1364)
            DB::table('movimientomateriales')->updateOrInsert(
                [
                    'serie' => $serie,
                    'SKU' => $sku,
                    'fkTecnico' => $idDestino,
                    'fkTienda' => $fkTienda,
                ],
                [
                    'almacen' => $data['almacen'] ?? 'A000',
                    'Lote' => $data['Lote'] ?? 'N/A',
                    'MAC1' => $data['MAC1'] ?? '', // <-- Evita error si el CSV no lo trae
                    'MAC2' => $data['MAC2'] ?? '',
                    'MAC3' => $data['MAC3'] ?? '',
                    'COSTO' =>  ($costoUnidad->TIPO === 'MANO DE OBRA') ? $costoUnidad->COSTOPAGO : ($costoUnidad->CATEGORIACOBRO ?? $data['COSTO']),
                    'TIPO' => $data['TIPO'] ?? 'MA',
                    'ESTATUS' => 'DISPONIBLE',
                    'Status' => 'I',
                    'Naturaleza'=> 'E',
                    'CENTRO' => $data['CENTRO'] ?? 'G817',
                    'cantidad' => $cantidad,
                    'unidadmedida' => $data['unidadmedida'] ?? 'PZA',
                    'TIPOMOVIMIENTO' => 'TRASPASO_ENTRADA',
                    'Modificado_el' => $ahora->format('Y-m-d'),
                    'Modificado_por' => $nombreUsuario,
                    'Creado_el' => $ahora->format('Y-m-d'),
                    'Creado_por' => $nombreUsuario,
                    'updated_at' => $ahora
                ]
            );
        }

        fclose($file);
        DB::commit();
        
        $msg = "Inventario procesado.";
        if($instaladosContador > 0) $msg .= " Se omitieron $instaladosContador series ya instaladas.";
        
        return back()->with('success', $msg);

    } catch (\Exception $e) {
        DB::rollBack();
        if (isset($file)) fclose($file);
        return back()->with('error', 'Error en fila ' . $fila . ': ' . $e->getMessage());
    }
}



        public function descargarFormeta()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=Formato Expediente Ruta.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columnas = ['Orden','virtual','Status','Tipo_servicio','Tipo_orden','NOMBRECLIENTE','DIRECCION','OBS','SIGLASCENTRAL','AREA','FECHAINSTALACION','AUTORIZA','ESTATUS','TECNOLOGIA'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        $fkTienda = session('user_fkTienda') ?? 0;
        // Línea de ejemplo opcional:
        fputcsv($file, [23450285,1005749,'I','DT',"DA",'JUAN PEREZ','Canton camoja, Huehuetanango, Huehuetenango',"ORDEN QUE SOLO SE AGREGAN CAJAS ADICIONALES",'HUE0301','OC3',"15/06/2025",'1T','I','WTTx']);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

        public function descargarinventario()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=Formato Expediente inventario.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columnas = ['serie','SKU','almacen','Lote','MAC1','MAC2','MAC3','ESTATUS','COSTO','CENTRO','TIPO','unidadmedida','TIPOMOVIMIENTO','Naturaleza','Status', 'cantidad'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        $fkTienda = session('user_fkTienda') ?? 0;
        // Línea de ejemplo opcional:
        fputcsv($file, ['fajJSJJDF4013896',4013896,'ALMA','A000',"N/A",'N/A','N/A',"A",350,'G817',"MA/MO",'UNIDAD',231,'D','I',1]);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

      public function pagocobro($id)
    {
        DB::connection()->disableQueryLog();
            try {
                                if(!Auth::check()){
            return redirect()->route('login');
        }

            DB::beginTransaction();


            $tecnico=Tecnico::where('id',$id)->first();

            DB::commit();
            return redirect()->route('buckettecnico.index')->with('success', 'Puede filtrar por fechas');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }

    }

       public function produccion($id)
    {
        try {

                        if(!Auth::check()){
            return redirect()->route('login');
        }
            DB::beginTransaction();
            $tecnico=Tecnico::where('id',$id)->first();

            DB::commit();
            return redirect()->route('buckettecnico.index')->with('success', 'Puede filtrar por fechas');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }

    }


    public function obtenerClientes()
    {
        $clientes = Cliente::select('id', 'persona_id')
        ->get();
        return response()->json($clientes);
    }
public function ejecutarconsulta(Request $request)
    {
        DB::connection()->disableQueryLog();
        try{
        $comprobanteId=$request->idcomprobante;

                $pdo = DB::getPdo();
        $stmt = $pdo->prepare("
    SELECT
        cc.id as idcuentacontable,
        dc.formula,
        cc.nombre,
        dc.Naturaleza,
        dc.valorminimo as resultado
    FROM
        dbsistemaventa.detalle_comprobantes as dc
    INNER JOIN
        cuentas_contables as cc
    ON
        cc.id = dc.fkCuentaContable
    WHERE
        dc.fkComprobante = :id
");

$stmt->execute(['id' => $comprobanteId]);


        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);


return response()->json($detallecomprobante);

        // Consultar los detalles del comprobante

}catch(Exception $e){

            return response()->json([
            'error' => 'Error al ejecutar la consulta',
            'detalle' => $e->getMessage()
        ], 500);
}

    }

    public function fetchrelacion(Request $request)
{
    DB::connection()->disableQueryLog();
    try{
                        if(!Auth::check()){
            return redirect()->route('login');
        }

                    $Estatus = session('user_estatus');
                    $fkTienda = session('user_fkTienda');
                    $idtecnico= $request->input('id');
                    $fechain=$request->input('fechain');
                    $fechafin=$request->input('fechafin');

                    if(isset($fechain) or isset($fechafin)){
                if ($Estatus == 'ER') {

            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$request->input('id'))
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->where('ESTATUS','I')
            ->paginate(10);

                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->where('ESTATUS','I')
            ->paginate(10);
                };
                    }else{
                if ($Estatus == 'ER') {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$request->input('id'))->paginate(10);
                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)->paginate(10);
                };
                    }





    if ($request->ajax()) {
        return view('buckettecnico.table.tabla', compact('relacion'))->render();
    }
    }catch(Exception $e){
    return view('tecnico.index', compact('relacion','Error: '.$e->getMessage()));
    }


}

    public function obtenerdetalles(string $sql, array $parametros)
    {
        DB::connection()->disableQueryLog();
        try{

                $pdo = DB::getPdo();
        $stmt = $pdo->prepare($sql);
        if($parametros['id']==''){
        $stmt->execute();
        }else{
        $stmt->execute($parametros);
        };

        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);


        return $detallecomprobante;

        // Consultar los detalles del comprobante

}catch(Exception $e){

    $detallecomprobante[0]="Error: ".$e->getMessage();
}

    }

    public function listaClientes(Request $request)
    {
        DB::connection()->disableQueryLog();

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
    DB::connection()->disableQueryLog();

    if(!Auth::check()){
        return redirect()->route('login');
    }

    // Buscamos al técnico y su usuario relacionado directamente
    // Nota: $id aquí debe ser el ID del Técnico o de la Persona según tu tabla
    $tecnico = Tecnico::where('id', $id)->first();

    if (!$tecnico || !$tecnico->fkuser) {
        return back()->with('error', 'No se encontró el usuario asociado a este técnico.');
    }

    try {
        DB::beginTransaction();

        // 1. Desactivar técnico
        $tecnico->update(['especialidad' => 'INACTIVO']); 

        // 2. Desactivar usuario
        $user = User::findOrFail($tecnico->fkuser);
        $user->status = 0; 
        $user->save();

        // 3. Quitar roles (Spatie)
        $user->roles()->detach();

        DB::commit();
        return redirect()->route('tecnico.lista')->with('success', 'Técnico y usuario desactivados correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error al procesar la baja: ' . $e->getMessage());
    }
}

}
