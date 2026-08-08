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
            ->whereIn('ex.Orden', $ordenes)
            ->where('ex.fkTienda', $tiendaId)
            ->select([
                'ex.id', 'ex.Orden', 'ex.virtual', 'ex.Tipo_orden', 
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

        // 2. Extraer expedientes con firmas e información general
        $expedientes = DB::table('expedientetecnico as ex')
            ->leftJoin('tecnico as t', 'ex.fkTecnico', '=', 't.id')
            ->whereIn('ex.Orden', $ordenes)
            ->where('ex.fkTienda', $tiendaId)
            ->select([
                'ex.id', 'ex.Orden', 'ex.virtual', 'ex.Tipo_orden', 'ex.Tipo_servicio',
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


}
