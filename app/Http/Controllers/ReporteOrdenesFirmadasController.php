<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Carbon\Carbon;
use ZipArchive;
use Barryvdh\DomPDF\Facade\Pdf; // Requiere: composer require barryvdh/laravel-dompdf


class ReporteOrdenesFirmadasController extends Controller
{
    public function generarReporteFirmas(Request $request)
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

        // 2. Extraer expedientes con el campo de firma_cliente incluido
        $expedientes = DB::table('expedientetecnico as ex')
            ->leftJoin('tecnico as t', 'ex.fkTecnico', '=', 't.id')
            ->whereIn( DB::raw('SUBSTRING(ex.Orden, 1, 8)'), $ordenes)
            ->where('ex.fkTienda', $tiendaId)
            ->select([
                'ex.id',  DB::raw('SUBSTRING(ex.Orden, 1, 8) as Orden'), 'ex.virtual', 'ex.Tipo_orden', 
                'ex.NOMBRECLIENTE', 'ex.DIRECCION', 'ex.FECHAINSTALACION',
                'ex.TECNOLOGIA', 'ex.firma_cliente',
                't.nombre as tecnico_nombre', 't.codigo as tecnico_codigo'
            ])
            ->get();

        if ($expedientes->isEmpty()) {
            return back()->with('error', 'No se encontraron expedientes válidos para las órdenes suministradas.');
        }

        $ordenesEncontradas = $expedientes->pluck('Orden')->unique()->toArray();

        // 3. Extraer catálogo y movimientos de materiales cruzados
        $movimientos = DB::table('movimientomateriales as mm')
            ->join('MaterialManoObra as mamo', 'mm.SKU', '=', 'mamo.SKU')
            ->whereIn('mm.fkExpediente', $expedientes->pluck('id'))
            ->where('mamo.CATEGORIA', '!=', 'MANO DE OBRA') // Filtrar solo insumos físicos
            ->select([
                'mm.fkExpediente', 'mm.SKU', 'mm.cantidad', 'mm.serie',
                'mamo.Descripcion', 'mamo.TIPO'
            ])
            ->get();

        // Inicializar ZIP contenedor de Hojas de Liquidación
        $zipFileName = 'Ordenes_Liquidacion_Firmadas_' . date('Ymd_His') . '.zip';
        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFileName;
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'No se pudo inicializar la compresión ZipArchive.');
        }

        $nombreBucket = 'sistema-pv-imagenes-tienda';
        $archivosExcelBorrar = [];
        $firmasTemporalesBorrar = [];
        // 4. Construcción individual por cada expediente (Formato Página 6)
        foreach ($expedientes as $exp) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Orden ' . $exp->Orden);
            $sheet->setShowGridLines(true);

            // Estilos globales de estructura
            $borderThin = ['borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
            $bgGris = ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAEAEA']]];

            // --- ENCABEZADO DE ORDEN DE SERVICIO ---
            $sheet->mergeCells('B2:G2');
            $sheet->setCellValue('B2', 'ORDEN DE SERVICIO CLARO TV - HOJA DE LIQUIDACIÓN');
            $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('B2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Bloque de datos informativos generales
            $sheet->setCellValue('B4', 'NÚMERO DE ORDEN:')->setCellValue('C4', $exp->Orden);
            $sheet->setCellValue('B5', 'NÚMERO VIRTUAL:')->setCellValue('C5', $exp->virtual);
            $sheet->setCellValue('B6', 'TIPO DE ORDEN:')->setCellValue('C6', $exp->Tipo_orden ?? 'DA');
            $sheet->setCellValue('B7', 'TECNOLOGÍA:')->setCellValue('C7', $exp->TECNOLOGIA ?? 'SATELITAL DTH');

            $sheet->setCellValue('E4', 'CLIENTE:')->setCellValue('F4', $exp->NOMBRECLIENTE);
            $sheet->setCellValue('E5', 'DIRECCIÓN:')->setCellValue('F5', $exp->DIRECCION);
            $sheet->setCellValue('E6', 'FECHA INSTALACIÓN:')->setCellValue('F6', $exp->FECHAINSTALACION ? Carbon::parse($exp->FECHAINSTALACION)->format('d/m/Y H:i') : 'N/A');
            $sheet->setCellValue('E7', 'TÉCNICO:')->setCellValue('F7', "[" . $exp->tecnico_codigo . "] " . $exp->tecnico_nombre);

            $sheet->getStyle('B4:B7')->getFont()->setBold(true);
            $sheet->getStyle('E4:E7')->getFont()->setBold(true);
            $sheet->getStyle('B4:G7')->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            // --- TABLA DE MATERIALES UTILIZADOS ---
            $sheet->setCellValue('B9', 'CODIGO')->setCellValue('C9', 'DESCRIPCIÓN MATERIAL')->setCellValue('D9', 'CANT.')->setCellValue('E9', 'NÚMERO DE SERIE / ESTADO');
            $sheet->mergeCells('E9:G9');
            $sheet->getStyle('B9:G9')->getFont()->setBold(true);
            $sheet->getStyle('B9:G9')->applyFromArray($bgGris);
            $sheet->getStyle('B9:G9')->applyFromArray($borderThin);

            $materialesOrden = $movimientos->where('fkExpediente', $exp->id);
            $filaInsumo = 10;

            if ($materialesOrden->isEmpty()) {
                $sheet->setCellValue('B10', 'N/A')->setCellValue('C10', 'Sin materiales registrados en la orden.')->setCellValue('D10', 0)->setCellValue('E10', 'N/A');
                $sheet->mergeCells('E10:G10');
                $sheet->getStyle('B10:G10')->applyFromArray($borderThin);
                $filaInsumo = 11;
            } else {
                foreach ($materialesOrden as $mat) {
                    $sheet->setCellValue('B' . $filaInsumo, $mat->SKU);
                    $sheet->setCellValue('C' . $filaInsumo, $mat->Descripcion);
                    $sheet->setCellValue('D' . $filaInsumo, $mat->cantidad);
                    
                    // Colocar serie o indicar si es material misceláneo sin serie
                    $serieTexto = trim($mat->serie);
                    if ($serieTexto === '' || strtoupper($serieTexto) === 'N/A' || strtoupper($serieTexto) === '0' || is_null($mat->serie)) {
                        $serieTexto = 'MATERIAL MISCELÁNEO / ACUMULADO';
                    }
                    $sheet->setCellValue('E' . $filaInsumo, $serieTexto);
                    
                    $sheet->mergeCells("E{$filaInsumo}:G{$filaInsumo}");
                    $sheet->getStyle("B{$filaInsumo}:G{$filaInsumo}")->applyFromArray($borderThin);
                    $filaInsumo++;
                }
            }
            // --- SECCIÓN DE CONFORMIDAD Y FIRMAS ---
            $filaDeclaracion = $filaInsumo + 1;
            $sheet->mergeCells("B{$filaDeclaracion}:G{$filaDeclaracion}");
            $sheet->setCellValue("B{$filaDeclaracion}", "HAGO CONSTAR QUE EL DÍA DE HOY SE INSTALÓ EN MI DOMICILIO EL SERVICIO ESPECIFICADO, QUEDANDO CONFORME Y SATISFECHO CON EL TRABAJO REALIZADO Y LOS MATERIALES DESCRITOS.");
            $sheet->getStyle("B{$filaDeclaracion}")->getFont()->setItalic(true)->setSize(9);
            $sheet->getStyle("B{$filaDeclaracion}")->getAlignment()->setWrapText(true);

            // Dibujar líneas y etiquetas de firmas
            $filaLineaFirmas = $filaDeclaracion + 4;
            $filaEtiquetaFirmas = $filaLineaFirmas + 1;

            // Cuadro Técnico
            $sheet->getStyle("B{$filaLineaFirmas}:C{$filaLineaFirmas}")->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->setCellValue("B{$filaEtiquetaFirmas}", "Firma del Técnico: " . $exp->tecnico_nombre);
            $sheet->getStyle("B{$filaEtiquetaFirmas}")->getFont()->setSize(9)->setBold(true);

            // Cuadro Cliente
            $sheet->getStyle("E{$filaLineaFirmas}:G{$filaLineaFirmas}")->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->setCellValue("E{$filaEtiquetaFirmas}", "Firma y Aceptación del Cliente");
            $sheet->getStyle("E{$filaEtiquetaFirmas}")->getFont()->setSize(9)->setBold(true);

            // 5. Descarga e inyección dinámica de la firma desde GCS
            if (!empty($exp->firma_cliente)) {
                $urlFirma = $exp->firma_cliente;
                $pathBucketFirma = $urlFirma;

                // Normalizar ruta remota del objeto en la nube
                if (str_contains($urlFirma, $nombreBucket)) {
                    $posBucket = strpos($urlFirma, $nombreBucket);
                    $pathBucketFirma = substr($urlFirma, $posBucket + strlen($nombreBucket));
                    $pathBucketFirma = ltrim($pathBucketFirma, '/');
                } else {
                    $pathBucketFirma = ltrim(parse_url($urlFirma, PHP_URL_PATH), '/');
                }

                // Si la firma existe en el disco de GCS, la procesamos
                if (Storage::disk('gcs_images')->exists($pathBucketFirma)) {
                    $binaryFirma = Storage::disk('gcs_images')->get($pathBucketFirma);
                    $tempFirmaPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'firma_' . $exp->Orden . '_' . time() . '.png';
                    file_put_contents($tempFirmaPath, $binaryFirma);

                    if (file_exists($tempFirmaPath)) {
                        $drawing = new Drawing();
                        $drawing->setName('Firma Cliente');
                        $drawing->setDescription('Firma de conformidad capturada en dispositivo móvil');
                        $drawing->setPath($tempFirmaPath);
                        $drawing->setHeight(65); // Ajustar tamaño para que calce arriba de la línea
                        
                        // Posicionar el gráfico de la firma exactamente sobre el espacio del cliente
                        $drawing->setCoordinates('E' . ($filaLineaFirmas - 2)); 
                        $drawing->setWorksheet($sheet);

                        // Registrar archivo local para purga posterior
                        $firmasTemporalesBorrar[] = $tempFirmaPath;
                    }
                }
            }

            // Autoajustar columnas para garantizar legibilidad óptima
            foreach (range('B', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Guardar archivo temporal de la orden actual
            $writer = new Xlsx($spreadsheet);
            $excelOrdenPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Orden_Liquidacion_' . $exp->Orden . '.xlsx';
            $writer->save($excelOrdenPath);

            // Añadir al ZIP
            $zip->addFile($excelOrdenPath, 'Orden_Liquidacion_' . $exp->Orden . '.xlsx');
            $archivosExcelBorrar[] = $excelOrdenPath;
        }

        $zip->close();

        // 6. Limpieza preventiva de archivos del sistema operativo
        if (!empty($archivosExcelBorrar)) {
            foreach ($archivosExcelBorrar as $fileEx) {
                if (file_exists($fileEx)) { 
                    @unlink($fileEx); 
                }
            }
        }
        if (!empty($firmasTemporalesBorrar)) {
            foreach ($firmasTemporalesBorrar as $fileFi) {
                if (file_exists($fileFi)) { 
                    @unlink($fileFi); 
                }
            }
        }

        // 7. Descarga del entregable comprimido
        if (file_exists($zipPath) && filesize($zipPath) > 22) {
            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'No se pudieron generar las hojas de liquidación firmadas.');
    }

        public function generarExpedientePdf1(Request $request)
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

        // 2. Extraer expedientes con firmas e información general
        $expedientes = DB::table('expedientetecnico as ex')
            ->leftJoin('tecnico as t', 'ex.fkTecnico', '=', 't.id')
            ->whereIn('SUBSTRING(ex.Orden, 1, 8)', $ordenes)
            ->where('ex.fkTienda', $tiendaId)
            ->select([
                'ex.id', 'SUBSTRING(ex.Orden, 1, 8) as Orden', 'ex.virtual', 'ex.Tipo_orden', 'ex.Tipo_servicio',
                'ex.NOMBRECLIENTE', 'ex.DIRECCION', 'ex.FECHAINSTALACION', 'ex.OBS',
                'ex.TECNOLOGIA', 'ex.firma_cliente', 'ex.SIGLASCENTRAL', 'ex.AREA',
                't.nombre as tecnico_nombre', 't.codigo as tecnico_codigo'
            ])
            ->get();

        if ($expedientes->isEmpty()) {
            return back()->with('error', 'No se encontraron expedientes válidos para las órdenes.');
        }

        $expedientesIds = $expedientes->pluck('id')->toArray();

        // 3. Extraer catálogo de Materiales Físicos (Excluyendo Mano de Obra)
        $materialesRaw = DB::table('movimientomateriales as mm')
            ->join('MaterialManoObra as mamo', 'mm.SKU', '=', 'mamo.SKU')
            ->whereIn('mm.fkExpediente', $expedientesIds)
            ->where('mamo.CATEGORIA', '!=', 'MANO DE OBRA')
            ->select(['mm.fkExpediente', 'mm.SKU', 'mm.cantidad', 'mm.serie', 'mamo.Descripcion'])
            ->get();

        // 4. Extraer catálogo de Mano de Obra para Cuadro de Costos (Página 4)
        $manoObraRaw = DB::table('movimientomateriales as mm')
            ->join('MaterialManoObra as mamo', 'mm.SKU', '=', 'mamo.SKU')
            ->whereIn('mm.fkExpediente', $expedientesIds)
            ->where('mamo.CATEGORIA', '=', 'MANO DE OBRA')
            ->select([
                'mm.cantidad', 
                'mamo.Descripcion',
                'mamo.unidadmedida',
                DB::raw("CASE WHEN mamo.unidadmedida = '' OR mamo.unidadmedida IS NULL THEN 'UNIDAD' ELSE mamo.unidadmedida END AS unidad_auditada"),
                DB::raw("CAST(mamo.CATEGORIACOBRO AS DECIMAL(10,2)) as precio_unitario")
            ])
            ->get();

        // Agrupar y resumir conceptos de Mano de Obra para las páginas globales 1 y 4
        $resumenManoObra = [];
        $totalManoObra = 0;

        foreach ($manoObraRaw->groupBy('Descripcion') as $descripcion => $items) {
            $cantidadTotal = $items->sum('cantidad');
            $precio = $items->first()->precio_unitario ?? 0;
            $subtotal = $cantidadTotal * $precio;
            $totalManoObra += $subtotal;

            $resumenManoObra[] = [
                'descripcion' => $descripcion,
                'unidad' => $items->first()->unidad_auditada,
                'cantidad' => $cantidadTotal,
                'precio' => $precio,
                'total' => $subtotal
            ];
        }

        // Cálculos Fiscales Finales
        $iva = $totalManoObra * 0.12;
        $totalConIva = $totalManoObra + $iva;

        // Convertir firmas de GCS a Base64 para que DomPDF pueda renderizarlas en línea sin bloqueos de red
        $nombreBucket = 'sistema-pv-imagenes-tienda';
        $expedientesProcesados = [];
                foreach ($expedientes as $exp) {
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

                // Descargar el binario de la firma de GCS e incrustarlo en línea como Data URI
                if (Storage::disk('gcs_images')->exists($pathBucketFirma)) {
                    $binaryFirma = Storage::disk('gcs_images')->get($pathBucketFirma);
                    $type = pathinfo($pathBucketFirma, PATHINFO_EXTENSION);
                    $type = empty($type) ? 'png' : $type;
                    $firmaBase64 = 'data:image/' . $type . ';base64,' . base64_encode($binaryFirma);
                }
            }

            // Filtrar los materiales específicos de esta orden para asignárselos en la vista
            $materialesDeEstaOrden = $materialesRaw->where('fkExpediente', $exp->id);

            $expedientesProcesados[] = [
                'id' => $exp->id,
                'Orden' => $exp->Orden,
                'virtual' => $exp->virtual,
                'Tipo_orden' => $exp->Tipo_orden,
                'Tipo_servicio' => $exp->Tipo_servicio,
                'NOMBRECLIENTE' => $exp->NOMBRECLIENTE,
                'DIRECCION' => $exp->DIRECCION,
                'FECHAINSTALACION' => $exp->FECHAINSTALACION,
                'OBS' => $exp->OBS,
                'SIGLASCENTRAL' => $exp->SIGLASCENTRAL,
                'AREA' => $exp->AREA,
                'TECNOLOGIA' => $exp->TECNOLOGIA,
                'tecnico_nombre' => $exp->tecnico_nombre,
                'tecnico_codigo' => $exp->tecnico_codigo,
                'firma_base64' => $firmaBase64,
                'materiales' => $materialesDeEstaOrden
            ];
        }

        // 5. Consolidar el listado general de materiales consumidos en todo el mes (Para la Página 2 y 5)
        $resumenMaterialesGlobal = [];
        foreach ($materialesRaw->groupBy('SKU') as $sku => $items) {
            $resumenMaterialesGlobal[] = [
                'sku' => $sku,
                'descripcion' => $items->first()->Descripcion ?? 'MATERIAL',
                'cantidad' => $items->sum('cantidad')
            ];
        }

        // Pack de variables unificado para pasarle a la plantilla Blade
        $dataReporte = [
            'fecha_reporte' => Carbon::now()->format('d/m/Y'),
            'periodo_mes' => 'DEL 01 AL 31 DE MAYO 2026',
            'resumenManoObra' => $resumenManoObra,
            'totalManoObra' => $totalManoObra,
            'iva' => $iva,
            'totalConIva' => $totalConIva,
            'materialesGlobales' => $resumenMaterialesGlobal,
            'expedientes' => $expedientesProcesados
        ];

        // 6. Cargar la vista HTML, aplicar orientación horizontal (Landscape) y forzar descarga
        $pdf = Pdf::loadView('reportes.expediente_completo_pdf', $dataReporte);
        $pdf->setPaper('letter', 'landscape'); // Formato horizontal exacto al reporte del operador

        return $pdf->download('Expediente_Consolidado_Firmado_' . date('Ymd_His') . '.pdf');
    }

    public function generarExpedientePdf2(Request $request)
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

        // 3. Extraer expedientes e información general cruzados con técnicos y usuarios compañeros
        $expedientesRaw = DB::table('expedientetecnico as ex')
            ->leftJoin('tecnico as t', 'ex.fkTecnico', '=', 't.id')
            ->leftJoin('users as u', 't.nombre', '=', 'u.name') // Left Join para resguardar que aparezcan los clientes siempre
            ->whereIn( DB::raw('SUBSTRING(ex.Orden, 1, 8)'), $ordenes)
            ->where('ex.fkTienda', $tiendaId)
            ->select([
                'ex.id',  DB::raw('SUBSTRING(ex.Orden, 1, 8) as Orden'), 'ex.virtual', 'ex.Tipo_orden', 'ex.Tipo_servicio',
                'ex.NOMBRECLIENTE', 'ex.DIRECCION', 'ex.FECHAINSTALACION', 'ex.OBS',
                'ex.firma_cliente', 'ex.SIGLASCENTRAL', 'ex.AREA',
                't.nombre as tecnico_nombre', 't.codigo as tecnico_codigo',
                'u.firma as firma_usuario' // Extraemos la firma del usuario técnico
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

            // Separar insumos físicos de Mano de Obra (Tus condiciones originales estables)
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

            // Procesar los expedientes e inyectar firmas digitales de GCS y base de datos
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

                // Dar formato e inyectar prefijo Base64 a la firma del usuario técnico si viene limpia
                $firmaTecnicoUserBase64 = null;
                if (!empty($exp->firma_usuario)) {
                    $firmaLimpia = trim($exp->firma_usuario);
                    $firmaTecnicoUserBase64 = str_starts_with($firmaLimpia, 'data:image') ? $firmaLimpia : 'data:image/png;base64,' . $firmaLimpia;
                }

                // Resguardamos tus mapeos exactos inyectando la nueva llave al final
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
                    'firma_base64' => $firmaBase64, // Firma de tu cliente estable original
                    'firma_tecnico_user' => $firmaTecnicoUserBase64, // Nueva firma del usuario/técnico
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
        // 🌟 CÁLCULO DINÁMICO DEL RANGO DE FECHAS REALES DE LAS ÓRDENES TRABAJADAS
        $coleccionFechas = $expedientesRaw->pluck('FECHAINSTALACION')->filter()->map(function($fecha) {
            return Carbon::parse($fecha);
        });

        if ($coleccionFechas->isNotEmpty()) {
            $fechaMinima = $coleccionFechas->min();
            $fechaMaxima = $coleccionFechas->max();
            
            // Si corresponden al mismo mes y año, se formatea simplificado, si no, completo
            if ($fechaMinima->format('m-Y') === $fechaMaxima->format('m-Y')) {
                $meseses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
                $nombreMes = $meseses[$fechaMinima->month - 1];
                $periodoDinamico = "DEL {$fechaMinima->day} AL {$fechaMaxima->day} DE " . strtoupper($nombreMes) . " DEL " . $fechaMinima->year;
            } else {
                $periodoDinamico = "DEL " . $fechaMinima->format('d/m/Y') . " AL " . $fechaMaxima->format('d/m/Y');
            }
        } else {
            $periodoDinamico = "PERIODO DE ÓRDENES PROCESADAS";
        }

        // 6. Configurar, compilar y descargar los datos estructurados en DomPDF
        $dataReporte = [
            'fecha_reporte' => Carbon::now()->format('d/m/Y'),
            'representante' => $tienda->representante ?? 'Distribuidor Autorizado',
            'periodo_mes'   => $periodoDinamico, // Inyección dinámica del rango de fechas reales
            'logo_tienda'   => $logoBase64Completo,
            'firma_representante' => $tienda->firma_representante ? 'data:image/png;base64,' . trim($tienda->firma_representante) : null,
            'nombre_tienda' => $tienda->Nombre ?? 'Distribuidor Autorizado',
            'tecnologias'   => $tecnologiasAgrupadas
        ];

        try {
            $pdf = Pdf::loadView('reportes.expediente_completo_pdf', $dataReporte)
                ->setPaper('letter', 'portrait')
                ->setOptions([
                    'isRemoteEnabled' => true,      
                    'isHtml5ParserEnabled' => true  
                ]);

            return $pdf->download('Expediente_Masivo_Tecnologias_' . date('Ymd_His') . '.pdf');

        } catch (\Exception $e) {
            \Log::error('Fallo controlado al compilar binario masivo DomPDF: ' . $e->getMessage(), [
                'linea' => $e->getLine()
            ]);

            return back()->with('error', 'El reporte se procesó con éxito pero falló la descarga del PDF: ' . $e->getMessage());
        }
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

// 2. Extraer el logotipo guardado en Base64 puro de la tienda
$tienda = DB::table('tienda')->where('idTienda', $tiendaId)->first();
$logoBase64Completo = null;
if ($tienda && !empty($tienda->logo)) {
    $logoBase64Completo = 'data:image/png;base64,' . trim($tienda->logo);
}

// 3. Extraer expedientes únicos aplicando DISTINCT para evitar filas duplicadas
$expedientesRaw = DB::table('expedientetecnico as ex')
    ->leftJoin('tecnico as t', 'ex.fkTecnico', '=', 't.id')
    ->leftJoin('users as u', 't.nombre', '=', 'u.name')
    ->whereIn(DB::raw('SUBSTRING(ex.Orden, 1, 8)'), $ordenes)
    ->where('ex.fkTienda', $tiendaId)
    ->select([
        'ex.id',  
        DB::raw('SUBSTRING(ex.Orden, 1, 8) as Orden'), 
        'ex.virtual', 'ex.Tipo_orden', 'ex.Tipo_servicio',
        'ex.NOMBRECLIENTE', 'ex.DIRECCION', 'ex.FECHAINSTALACION', 'ex.OBS',
        'ex.firma_cliente',
         'ex.SIGLASCENTRAL', 'ex.AREA',
        't.nombre as tecnico_nombre', 't.codigo as tecnico_codigo',
        'u.firma as firma_usuario'
    ])
    ->distinct() 
    ->get();

if ($expedientesRaw->isEmpty()) {
    return back()->with('error', 'No se encontraron expedientes válidos.');
}

$expedientesIds = $expedientesRaw->pluck('id')->toArray();
$nombreBucket = 'sistema-pv-imagenes-tienda';



// 4. Fuente Primaria: Extraer movimientos forzando la sustitución del fkExpediente por el firmado
$materialesMovimientos = DB::table('movimientomateriales as mm')
    ->join('MaterialManoObra as mamo', 'mm.SKU', '=', 'mamo.SKU')
    ->join('expedientetecnico as et_mov', 'mm.fkExpediente', '=', 'et_mov.id') 
    ->leftJoin('arbolmaterial as abmamo', 'mm.fkTecnologiaarbol', '=', 'abmamo.id')
    
    /* 🚀 INYECCIÓN DEL JOIN DE CONTROL PARA SUSTITUIR EL EXPEDIENTE EN CALIENTE */
    ->join(DB::raw("(
        SELECT 
            SUBSTRING(REGEXP_REPLACE(ex_f.Orden, '[^0-9]', ''), 1, 8) AS orden_limpia,
            COALESCE(
                MAX(CASE WHEN ex_f.firma_cliente IS NOT NULL THEN ex_f.id END),
                MAX(ex_f.id)
            ) AS id_firmado
        FROM expedientetecnico ex_f
        GROUP BY SUBSTRING(REGEXP_REPLACE(ex_f.Orden, '[^0-9]', ''), 1, 8)
    ) as exp_firma"), 'exp_firma.orden_limpia', '=', DB::raw("SUBSTRING(REGEXP_REPLACE(et_mov.Orden, '[^0-9]', ''), 1, 8)"))
    
    ->where('mamo.CATEGORIA', '!=', 'MANO DE OBRA')    
    ->whereIn(DB::raw("SUBSTRING(REGEXP_REPLACE(et_mov.Orden, '[^0-9]', ''), 1, 8)"), $ordenes)
    ->where('mm.fkTienda', $tiendaId)
    ->select([
        'exp_firma.id_firmado as fkExpediente', // 🚀 Todos los registros de la orden adoptan el ID firmado (ej: 40)
        'mm.SKU', 
        DB::raw('SUM(mm.cantidad) as cantidad'), 
        'mm.serie', 
        DB::raw('MIN(mamo.Descripcion) as Descripcion'), 
        'mamo.TIPO', 
        'mamo.CATEGORIA', 
        'mamo.unidadmedida',
        'abmamo.nombre as TecnologiaCatalogo',
        DB::raw("SUBSTRING(REGEXP_REPLACE(et_mov.Orden, '[^0-9]', ''), 1, 8) as codigo_orden"),
        DB::raw("CAST(mamo.CATEGORIACOBRO AS DECIMAL(10,2)) as precio_unitario")
    ])
    ->groupBy([
        'exp_firma.id_firmado', // 🛡️ Requerido para agrupar bajo el mismo expediente líder
        'mm.SKU',
        'mm.serie',
        'mamo.TIPO',
        'mamo.CATEGORIA',
        'mamo.unidadmedida',
        'abmamo.nombre',
        DB::raw("SUBSTRING(REGEXP_REPLACE(et_mov.Orden, '[^0-9]', ''), 1, 8)"),
        'mamo.CATEGORIACOBRO'
    ])
    ->get();




// Mapa de referencia rápida en memoria
$mapaSkvTecnologia = $materialesMovimientos
    ->whereNotNull('TecnologiaCatalogo')
    ->pluck('TecnologiaCatalogo', 'SKU')
    ->toArray();

// 5. Fuente Secundaria: Extraer registros desde pagotecnico
$materialesPagoTecnicoRaw = DB::table('pagotecnico as pt')
    ->join('MaterialManoObra as mamo', 'pt.SKU', '=', 'mamo.SKU')
    ->join('expedientetecnico as et', 'et.Orden', '=', 'pt.Orden')
    ->join('movimientomateriales as mm', 'et.id', '=', 'mm.fkExpediente')
    ->join('tecnico as t', 'et.fkTecnico', '=', 't.id')
    ->leftJoin('arbolmaterial as abmamo', 'mm.fkTecnologiaarbol', '=', 'abmamo.id')
    ->where('et.ESTATUS', 'C')
    ->where('mamo.categoria', 'MANO DE OBRA')
    ->whereIn(DB::raw('SUBSTRING(pt.Orden, 1, 8)'), $ordenes)
    ->where('pt.fkTienda', $tiendaId)    
    ->select([
        'mm.fkExpediente', 
        't.nombre',
        't.codigo as CENTRO_CODIGO',
        'pt.Orden', 
        'pt.SKU', 
        'mamo.Descripcion', 
        'pt.cantidad', 
        'mamo.CATEGORIACOBRO as COSTO', 
        DB::raw('(pt.cantidad * mamo.CATEGORIACOBRO) as Subtotal'),
        'mamo.TIPO', 
        'abmamo.nombre as TecnologiaCatalogo',
        'mamo.CATEGORIA', 
        'mamo.unidadmedida', 
        DB::raw('SUBSTRING(pt.Orden, 1, 8) as OrdenSub'), 
        DB::raw('NULL as serie'), 
        DB::raw('CAST(mamo.CATEGORIACOBRO AS DECIMAL(10,2)) as precio_unitario')
    ])
    ->distinct()        
    ->groupBy([
        'mm.fkExpediente',
        't.nombre',
        't.codigo',
        'pt.Orden',
        'pt.SKU',
        'mamo.Descripcion',
        'pt.cantidad',
        'mamo.CATEGORIACOBRO',
        'mamo.TIPO',
        'abmamo.nombre',
        'mamo.CATEGORIA',
        'mamo.unidadmedida',
        DB::raw('SUBSTRING(pt.Orden, 1, 8)')
    ])
    ->get();


$todosMateriales = collect();

// 1. Insertar la voz de mando completa (movimientomateriales)
foreach ($materialesMovimientos as $item) {
    $todosMateriales->push($item);
}

// Procesar y asignar virtualmente fkExpediente y TecnologiaCatalogo a los registros de pagotecnico
$materialesPagoTecnico = collect();
foreach ($materialesPagoTecnicoRaw as $pago) {
    $expedienteAsociado = $expedientesRaw->firstWhere('Orden', $pago->OrdenSub);
    
    if ($expedienteAsociado) {
        $pago->fkExpediente = $expedienteAsociado->id;
        
        // 🚀 1. TRATAMIENTO DE PALABRAS CLAVE: Analizamos la descripción para evitar mezclas erróneas
        $descripcionMayuscula = mb_strtoupper($pago->Descripcion);
        
        if (str_contains($descripcionMayuscula, 'DTH')) {
            $pago->TecnologiaCatalogo = 'DTH';
        } elseif (str_contains($descripcionMayuscula, 'WTTX') || str_contains($descripcionMayuscula, 'WTTX')) {
            $pago->TecnologiaCatalogo = 'WTTx';
        } elseif (str_contains($descripcionMayuscula, 'GPON') || str_contains($descripcionMayuscula, 'FIBRA')) {
            $pago->TecnologiaCatalogo = 'GPON';
        } elseif (str_contains($descripcionMayuscula, 'HFC') || str_contains($descripcionMayuscula, 'COAXIAL')) {
            $pago->TecnologiaCatalogo = 'HFC';
        } else {
            // 🚀 2. Si no contiene palabras clave evidentes, recurrimos a las soluciones por descarte previas
            $llaveCompuesta = $pago->OrdenSub . '_' . $pago->SKU;
            
            if (isset($mapaSkvTecnologia[$llaveCompuesta])) {
                $pago->TecnologiaCatalogo = $mapaSkvTecnologia[$llaveCompuesta];
            } else {
                // Buscamos qué tecnologías usó esta orden específica originalmente en los movimientos físicos
                $tecnologiasDeLaOrden = $todosMateriales
                    ->where('codigo_orden', $pago->OrdenSub)
                    ->where('TecnologiaCatalogo', '!=', 'OTRAS_TECNOLOGIAS')
                    ->pluck('TecnologiaCatalogo')
                    ->unique();

                if ($tecnologiasDeLaOrden->count() === 1) {
                    $pago->TecnologiaCatalogo = $tecnologiasDeLaOrden->first();
                } elseif ($tecnologiasDeLaOrden->count() > 1) {
                    // Si la orden realmente maneja dos tecnologías físicas válidas, clonamos para segmentar
                    foreach ($tecnologiasDeLaOrden as $tecnologiaIndividual) {
                        $clonPago = clone $pago;
                        $clonPago->TecnologiaCatalogo = $tecnologiaIndividual;
                        $materialesPagoTecnico->push($clonPago);
                    }
                    continue; 
                } else {
                    $pago->TecnologiaCatalogo = $mapaSkvTecnologia[$pago->SKU] ?? 'OTRAS_TECNOLOGIAS';
                }
            }
        }
        
        $materialesPagoTecnico->push($pago);
    }
}



// Consolidador global unificado aplicando las reglas de la Voz de Mando
$todosMateriales = collect();

// Insertar de manera prioritaria la fuente obligatoria (movimientomateriales)
foreach ($materialesMovimientos as $item) {
    $todosMateriales->push($item);
}

// Incorporar los registros faltantes de pagotecnico si no existen previamente bajo la dupla Expediente + SKU
foreach ($materialesPagoTecnico as $itemPago) {
    $existeEnMovimientos = $todosMateriales->contains(function ($itemExistente) use ($itemPago) {
        return $itemExistente->fkExpediente == $itemPago->fkExpediente && $itemExistente->SKU == $itemPago->SKU;
    });

    if (!$existeEnMovimientos) {
        $todosMateriales->push($itemPago);
    }
}


// 6. Agrupar la información por cada Tecnología ÚNICA obtenida del Árbol de Materiales
$tecnologiasAgrupadas = [];
$materialesPorTecnologia = $todosMateriales->groupBy(function($item) {
    return empty($item->TecnologiaCatalogo) ? 'OTRAS_TECNOLOGIAS' : $item->TecnologiaCatalogo;
});

foreach ($materialesPorTecnologia as $nombreTecnologia => $materialesTec) {
    $idsDeEstaTecnologia = $materialesTec->pluck('codigo_orden')->unique()->toArray();
    $listaExpedientes = $expedientesRaw->whereIn('Orden', $idsDeEstaTecnologia);

    if ($listaExpedientes->isEmpty()) {
        continue;
    }

    // Separar insumos físicos de Mano de Obra
    $materialesFisicosTec = $materialesTec->where('CATEGORIA', '!=', 'MANO DE OBRA');
    $manoObraTec = $materialesTec->where('CATEGORIA', '==', 'MANO DE OBRA');

    // Procesar Cuadro Financiero de Mano de Obra agrupado por descripción sin duplicar
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

    // Consolidador global de materiales físicos agrupados por SKU sin duplicar
    $materialesGlobales = [];
    foreach ($materialesFisicosTec->groupBy('SKU') as $sku => $itemsMat) {
        $materialesGlobales[] = [
            'sku' => $sku,
            'descripcion' => $itemsMat->first()->Descripcion,
            'cantidad' => $itemsMat->sum('cantidad')
        ];
    }
    // Procesar los expedientes pertenecientes a esta tecnología e inyectar firmas digitales
$expedientesProcesados = [];
foreach ($listaExpedientes as $exp) {
    
    /* 🚀 1. OBTENER LOS MATERIALES DEL EXPEDIENTE ACTUAL */
    $materialesDelExpediente = $materialesFisicosTec->where('fkExpediente', $exp->id);

    /* 🛡️ FILTRO CRUCIAL: Si el expediente no tiene ítems de materiales asignados, se ignora por completo */
    if ($materialesDelExpediente->isEmpty()) {
        continue; // Salta al siguiente expediente inmediatamente
    }

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

    $firmaTecnicoUserBase64 = null;
    if (!empty($exp->firma_usuario)) {
        $firmaLimpia = trim($exp->firma_usuario);
        $firmaTecnicoUserBase64 = str_starts_with($firmaLimpia, 'data:image') ? $firmaLimpia : 'data:image/png;base64,' . $firmaLimpia;
    }

    /* 🚀 2. SOLO SE AGREGA AL ARREGLO SI SUPERÓ LA VALIDACIÓN DE MATERIALES */
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
        'firma_tecnico_user' => $firmaTecnicoUserBase64, 
        'materiales' => $materialesDelExpediente // Asigna la variable previamente filtrada
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
} // Cierre del foreach principal de tecnologías
// 7. Cálculo dinámico del rango de fechas reales de las órdenes trabajadas
$coleccionFechas = $expedientesRaw->pluck('FECHAINSTALACION')->filter()->map(function($fecha) {
    return Carbon::parse($fecha);
});

if ($coleccionFechas->isNotEmpty()) {
    $fechaMinima = $coleccionFechas->min();
    $fechaMaxima = $coleccionFechas->max();
    
    if ($fechaMinima->format('m-Y') === $fechaMaxima->format('m-Y')) {
        $meseses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        $nombreMes = $meseses[$fechaMinima->month - 1];
        $periodoDinamico = "DEL {$fechaMinima->day} AL {$fechaMaxima->day} DE " . strtoupper($nombreMes) . " DEL " . $fechaMinima->year;
    } else {
        $periodoDinamico = "DEL " . $fechaMinima->format('d/m/Y') . " AL " . $fechaMaxima->format('d/m/Y');
    }
} else {
    $periodoDinamico = "PERIODO DE ÓRDENES PROCESADAS";
}

// 8. Configurar, compilar y descargar los datos estructurados en DomPDF
$dataReporte = [
    'fecha_reporte' => Carbon::now()->format('d/m/Y'),
    'representante' => $tienda->representante ?? 'Distribuidor Autorizado',
    'periodo_mes'   => $periodoDinamico, 
    'logo_tienda'   => $logoBase64Completo,
    'firma_representante' => $tienda->firma_representante ? 'data:image/png;base64,' . trim($tienda->firma_representante) : null,
    'nombre_tienda' => $tienda->Nombre ?? 'Distribuidor Autorizado',
    'tecnologias'   => $tecnologiasAgrupadas
];

try {
    $pdf = Pdf::loadView('reportes.expediente_completo_pdf', $dataReporte)
        ->setPaper('letter', 'portrait')
        ->setOptions([
            'isRemoteEnabled' => true,      
            'isHtml5ParserEnabled' => true  
        ]);

    return $pdf->download('Expediente_Masivo_Tecnologias_' . date('Ymd_His') . '.pdf');

} catch (\Exception $e) {
    \Log::error('Fallo controlado al compilar binario masivo DomPDF: ' . $e->getMessage(), [
        'linea' => $e->getLine()
    ]);

    return back()->with('error', 'El reporte se procesó con éxito pero falló la descarga del PDF: ' . $e->getMessage());
}

}

}
