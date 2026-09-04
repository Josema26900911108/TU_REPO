<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdenTrabajo;
use Illuminate\Support\Facades\DB;
use Exception;

class ConciliacionController extends Controller
{
    /**
     * Muestra la tabla comparativa con las diferencias de montos y cantidades
     */
public function index()
{
    // 🚀 OBLIGAMOS A LARAVEL A TRAER SÓLO LOS REGISTROS DE LA PÁGINA ACTUAL (Ej: 25 filas)
    $reporte = DB::table(DB::raw("(
        SELECT 
            base_cruce.Orden,
            base_cruce.SKU,
            IFNULL(cat.Descripcion, 'Sin Descripción en Catálogo') AS Descripcion_Mamo,
            base_cruce.Cantidad_OT,
            base_cruce.Cantidad_PT,
            (base_cruce.Cantidad_OT - base_cruce.Cantidad_PT) AS Diferencia_Cantidad,
            base_cruce.Costo_OT,
            base_cruce.Costo_PT,
            (base_cruce.Costo_OT - base_cruce.Costo_PT) AS Diferencia_Costo,
            base_cruce.Subtotal_OT,
            base_cruce.Subtotal_PT,
            (base_cruce.Subtotal_OT - base_cruce.Subtotal_PT) AS Diferencia_Subtotal,
            CASE 
                WHEN ABS(base_cruce.Cantidad_OT - base_cruce.Cantidad_PT) < 0.01 
                     AND ABS(base_cruce.Subtotal_OT - base_cruce.Subtotal_PT) < 0.01 THEN '✅ Conciliado Perfecto'
                WHEN base_cruce.Cantidad_PT = 0 THEN '⚠️ No Registrado en PagoTécnico'
                WHEN base_cruce.Cantidad_OT = 0 THEN '⚠️ No Registrado en Mano de Obra'
                ELSE '❌ Discrepancia en Valores'
            END AS Estado_Conciliacion
        FROM (
            SELECT 
                Orden_Llave AS Orden,
                SKU_Llave AS SKU,
                SUM(Cant_OT) AS Cantidad_OT,
                SUM(Cant_PT) AS Cantidad_PT,
                MAX(Cos_OT) AS Costo_OT,
                MAX(Cos_PT) AS Costo_PT,
                SUM(Sub_OT) AS Subtotal_OT,
                SUM(Sub_PT) AS Subtotal_PT
            FROM (
                /* --- BLOQUE A: ARCHIVO IMPORTADO (EXTERNO) --- */
                SELECT 
                    LEFT(REGEXP_REPLACE(TRIM(Orden_Trabajo), '[^0-9]', ''), 8) AS Orden_Llave,
                    TRIM(Id_Servicio) AS SKU_Llave,
                    Cantidad AS Cant_OT, 0 AS Cant_PT,
                    COSTO AS Cos_OT, 0 AS Cos_PT,
                    SUBTOTAL AS Sub_OT, 0 AS Sub_PT
                FROM ordenes_trabajo

                UNION ALL

                /* --- BLOQUE B: SISTEMA PROPIO (INTERNO) --- */
                SELECT 
                    LEFT(REGEXP_REPLACE(TRIM(pt.Orden), '[^0-9]', ''), 8) AS Orden_Llave,
                    TRIM(pt.SKU) AS SKU_Llave,
                    0 AS Cant_OT, pt.Cantidad AS Cant_PT,
                    0 AS Cos_OT, IFNULL(mamo.CATEGORIACOBRO, pt.COSTOPAGO) AS Cos_PT,
                    0 AS Sub_OT, (pt.Cantidad * IFNULL(mamo.CATEGORIACOBRO, pt.COSTOPAGO)) AS Sub_PT
                FROM pagotecnico pt
                LEFT JOIN (
                    SELECT SKU, MAX(CATEGORIACOBRO) AS CATEGORIACOBRO 
                    FROM MaterialManoObra 
                    GROUP BY SKU
                ) mamo ON TRIM(pt.SKU) = TRIM(mamo.SKU)
                /* 🚀 FILTRO AGREGADO: Solo incluir registros con estatus S o C */
                WHERE pt.Status IN ('S', 'C')
            ) universo_plano
            GROUP BY Orden_Llave, SKU_Llave
        ) base_cruce
        LEFT JOIN (
            SELECT SKU, MAX(Descripcion) AS Descripcion
            FROM MaterialManoObra
            GROUP BY SKU
        ) cat ON TRIM(base_cruce.SKU) = TRIM(cat.SKU)
    ) as reporte_unificado"))
    ->orderBy('Estado_Conciliacion', 'desc')
    ->orderBy('Orden', 'asc')
    ->paginate(25);

    return view('Conciliacion.index', compact('reporte'));
}



/**
 * Procesa la carga masiva del archivo CSV y guarda las órdenes en la sesión
 */
public function importarCSV(Request $request)
{
    $request->validate([
        'archivo_csv' => 'required|file|mimes:csv,txt'
    ]);

    set_time_limit(0);
    ini_set('memory_limit', '512M');

    try {
        $path = $request->file('archivo_csv')->getRealPath();
        $file = fopen($path, 'r');

        // Omitir la cabecera
        $cabecera = fgetcsv($file, 0, ","); 
        
        $loteDatos = [];
        $ordenesProcesadas = []; // 🚀 Array para capturar las órdenes únicas del archivo
        $contador = 0;

        while (($fila = fgetcsv($file, 0, ",")) !== FALSE) {
            $idFila = $fila[0];
            $ordenOriginal = $fila[1];
            
            // Limpiamos la orden para guardarla en nuestro buscador de sesión
            $ordenLimpia = substr(preg_replace('/[^0-9]/', '', trim($ordenOriginal)), 0, 8);
            if (!empty($ordenLimpia)) {
                $ordenesProcesadas[$ordenLimpia] = true; // Usar llaves evita duplicados en memoria
            }

            $loteDatos[] = [
                'id'               => $idFila,
                'Orden_Trabajo'    => $ordenOriginal,
                'Descripcion'      => $fila[2] ?? null,
                'Id_Contratista'   => $fila[3] ?? null,
                'Id_Servicio'      => $fila[4], // SKU
                'Cantidad'         => floatval($fila[5] ?? 0),
                'Centro_Mano_Obra' => $fila[6] ?? null,
                'COSTO'            => floatval($fila[7] ?? 0),
                'SUBTOTAL'         => floatval($fila[8] ?? 0),
                'TECNO'            => $fila[9] ?? null,
                'created_at'       => now(),
                'updated_at'       => now()
            ];

            $contador++;

            if (count($loteDatos) === 500) {
                $this->ejecutarUpsert($loteDatos);
                $loteDatos = [];
            }
        }

        if (count($loteDatos) > 0) {
            $this->ejecutarUpsert($loteDatos);
        }

        fclose($file);

        // 🚀 GUARDAR EN SESIÓN: Almacenamos la lista de órdenes limpias del archivo actual
        session(['ultimas_ordenes_importadas' => array_keys($ordenesProcesadas)]);

        return redirect()->back()->with('success', "Se cargaron y actualizaron {$contador} registros exitosamente. Ya puedes descargar el reporte específico.");

    } catch (Exception $e) {
        return redirect()->back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
    }
}

/**
 * Exporta EXCLUSIVAMENTE las órdenes que se subieron en la última sesión de importación
 */
public function exportarConciliacion(Request $request)
{
    set_time_limit(0);
    ini_set('memory_limit', '512M');

    try {
        // 🚀 RECUPERAR DE SESIÓN: Obtenemos el filtro de órdenes
        $ordenesFiltro = session('ultimas_ordenes_importadas', []);

        if (empty($ordenesFiltro)) {
            return redirect()->back()->with('error', 'No hay registros recientes en la sesión para exportar. Por favor, sube un archivo CSV primero.');
        }

        // Convertimos el array de órdenes a un formato seguro para la consulta SQL (Ej: '26258260','5455')
        $listaOrdenesSql = implode(',', array_map(function($item) {
            return "'" . addslashes($item) . "'";
        }, $ordenesFiltro));

        // Ejecutamos la consulta inyectando el filtro WHERE en el cruce final
            // 🚀 CONSULTA ACTUALIZADA CON FILTRO S/C Y DESCRIPCIÓN MAESTRA
        $reporte = DB::select("
            SELECT 
                base_cruce.Orden,
                base_cruce.SKU,
                IFNULL(cat.Descripcion, 'Sin Descripción en Catálogo') AS Descripcion_Mamo,
                base_cruce.Cantidad_OT,
                base_cruce.Cantidad_PT,
                (base_cruce.Cantidad_OT - base_cruce.Cantidad_PT) AS Diferencia_Cantidad,
                base_cruce.Costo_OT,
                base_cruce.Costo_PT,
                (base_cruce.Costo_OT - base_cruce.Costo_PT) AS Diferencia_Costo,
                base_cruce.Subtotal_OT,
                base_cruce.Subtotal_PT,
                (base_cruce.Subtotal_OT - base_cruce.Subtotal_PT) AS Diferencia_Subtotal,
                CASE 
                    WHEN ABS(base_cruce.Cantidad_OT - base_cruce.Cantidad_PT) < 0.01 
                         AND ABS(base_cruce.Subtotal_OT - base_cruce.Subtotal_PT) < 0.01 THEN '✅ Conciliado Perfecto'
                    WHEN base_cruce.Cantidad_PT = 0 THEN '⚠️ No Registrado en PagoTécnico'
                    WHEN base_cruce.Cantidad_OT = 0 THEN '⚠️ No Registrado en Mano de Obra'
                    ELSE '❌ Discrepancia en Valores'
                END AS Estado_Conciliacion
            FROM (
                SELECT 
                    Orden_Llave AS Orden,
                    SKU_Llave AS SKU,
                    SUM(Cant_OT) AS Cantidad_OT,
                    SUM(Cant_PT) AS Cantidad_PT,
                    MAX(Cos_OT) AS Costo_OT,
                    MAX(Cos_PT) AS Costo_PT,
                    SUM(Sub_OT) AS Subtotal_OT,
                    SUM(Sub_PT) AS Subtotal_PT
                FROM (
                    /* --- BLOQUE A: ARCHIVO IMPORTADO (EXTERNO) --- */
                    SELECT 
                        LEFT(REGEXP_REPLACE(TRIM(Orden_Trabajo), '[^0-9]', ''), 8) AS Orden_Llave,
                        TRIM(Id_Servicio) AS SKU_Llave,
                        Cantidad AS Cant_OT, 0 AS Cant_PT,
                        COSTO AS Cos_OT, 0 AS Cos_PT,
                        SUBTOTAL AS Sub_OT, 0 AS Sub_PT
                    FROM ordenes_trabajo

                    UNION ALL

                    /* --- BLOQUE B: SISTEMA PROPIO (FILTRADO S y C) --- */
                    SELECT 
                        LEFT(REGEXP_REPLACE(TRIM(pt.Orden), '[^0-9]', ''), 8) AS Orden_Llave,
                        TRIM(pt.SKU) AS SKU_Llave,
                        0 AS Cant_OT, pt.Cantidad AS Cant_PT,
                        0 AS Cos_OT, IFNULL(mamo.CATEGORIACOBRO, pt.COSTOPAGO) AS Cos_PT,
                        0 AS Sub_OT, (pt.Cantidad * IFNULL(mamo.CATEGORIACOBRO, pt.COSTOPAGO)) AS Sub_PT
                    FROM pagotecnico pt
                    LEFT JOIN (
                        SELECT SKU, MAX(CATEGORIACOBRO) AS CATEGORIACOBRO 
                        FROM MaterialManoObra 
                        GROUP BY SKU
                    ) mamo ON TRIM(pt.SKU) = TRIM(mamo.SKU)
                    /* 🚀 FILTRO AGREGADO: Solo incluir registros con estatus S o C */
                    WHERE pt.Status IN ('S', 'C')
                ) universo_plano
                GROUP BY Orden_Llave, SKU_Llave
            ) base_cruce
            /* 🚀 LEFT JOIN CONTRA EL CATÁLOGO PARA EXTRAER EL NOMBRE REAL */
            LEFT JOIN (
                SELECT SKU, MAX(Descripcion) AS Description
                FROM MaterialManoObra
                GROUP BY SKU
            ) cat ON TRIM(base_cruce.SKU) = TRIM(cat.SKU)
            WHERE base_cruce.Orden IN ($listaOrdenesSql) -- 🚀 FILTRO ESTRICTO DEL ARCHIVO IMPORTADO
            ORDER BY Estado_Conciliacion DESC, base_cruce.Orden ASC
        ");

       // 1. Cabeceras con la columna de Descripción en Quetzales (Q)
        fputcsv($file, [
            'Orden (8 Dígitos)', 'SKU / Servicio', 'Descripción Catálogo',
            'Cant. Importada (OT)', 'Cant. PagoTécnico (PT)', 'Diferencia Cantidad',
            'Costo Importado (OT) (Q)', 'Costo PagoTécnico (MAMO) (Q)', 'Diferencia Costo (Q)',
            'Subtotal Importado (OT) (Q)', 'Subtotal PagoTécnico (PT) (Q)', 'Diferencia Subtotal (Q)', 
            'Estado Conciliación'
        ]);

        // 2. Inyección de la variable en el ciclo foreach
        foreach ($reporte as $row) {
            fputcsv($file, [
                $row->Orden,
                $row->SKU,
                $row->Descripcion_Mamo, // 🚀 Campo de descripción inyectado de forma segura
                number_format($row->Cantidad_OT, 2, '.', ''),
                number_format($row->Cantidad_PT, 2, '.', ''),
                number_format($row->Diferencia_Cantidad, 2, '.', ''),
                number_format($row->Costo_OT, 2, '.', ''),
                number_format($row->Costo_PT, 2, '.', ''),
                number_format($row->Diferencia_Costo, 2, '.', ''),
                number_format($row->Subtotal_OT, 2, '.', ''),
                number_format($row->Subtotal_PT, 2, '.', ''),
                number_format($row->Diferencia_Subtotal, 2, '.', ''),
                $row->Estado_Conciliacion
            ]);
        }

        rewind($file);
        $csvContent = stream_get_contents($file);
        fclose($file);

        $fileName = 'Reporte_Conciliacion_Archivo_Actual_' . date('Y-m-d_H-i') . '.csv';

        return response($csvContent, 200, [
            "Content-Type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"$fileName\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ]);

    } catch (Exception $e) {
        return redirect()->back()->with('error', 'Error al exportar el archivo actual: ' . $e->getMessage());
    }
}

public function extraccionMasivaCSV(Request $request)
{
    $request->validate([
        'csv_ordenes' => 'required|file|mimes:csv,txt'
    ]);

    set_time_limit(0);
    ini_set('memory_limit', '512M');

    try {
        $path = $request->file('csv_ordenes')->getRealPath();
        $fileInput = fopen($path, 'r');

        // Leer cabecera del archivo del usuario
        $cabeceraInput = fgetcsv($fileInput, 0, ",");
        
        $ordenesFiltro = [];
        
        // Recorremos el archivo del usuario para capturar los números de órdenes que solicita
        while (($fila = fgetcsv($fileInput, 0, ",")) !== FALSE) {
            if (!empty($fila[0])) { // Asumiendo que la orden viene en la primera columna
                $ordenLimpia = substr(preg_replace('/[^0-9]/', '', trim($fila[0])), 0, 8);
                if (!empty($ordenLimpia)) {
                    $ordenesFiltro[$ordenLimpia] = true;
                }
            }
        }
        fclose($fileInput);

        if (empty($ordenesFiltro)) {
            return redirect()->back()->with('error', 'El archivo subido no contiene números de orden válidos.');
        }

        // Preparar lista segura para el IN SQL
        $listaOrdenesSql = implode(',', array_map(function($item) {
            return "'" . addslashes($item) . "'";
        }, array_keys($ordenesFiltro)));

         // Ejecutar la consulta relacional con tabuladores de CATEGORIACOBRO, filtro S/C y descripción
        $reporte = DB::select("
            SELECT 
                base_cruce.Orden,
                base_cruce.SKU,
                IFNULL(cat.Descripcion, 'Sin Descripción en Catálogo') AS Descripcion_Mamo,
                base_cruce.Cantidad_OT,
                base_cruce.Cantidad_PT,
                (base_cruce.Cantidad_OT - base_cruce.Cantidad_PT) AS Diferencia_Cantidad,
                base_cruce.Costo_OT,
                base_cruce.Costo_PT,
                (base_cruce.Costo_OT - base_cruce.Costo_PT) AS Diferencia_Costo,
                base_cruce.Subtotal_OT,
                base_cruce.Subtotal_PT,
                (base_cruce.Subtotal_OT - base_cruce.Subtotal_PT) AS Diferencia_Subtotal,
                CASE 
                    WHEN ABS(base_cruce.Cantidad_OT - base_cruce.Cantidad_PT) < 0.01 
                         AND ABS(base_cruce.Subtotal_OT - base_cruce.Subtotal_PT) < 0.01 THEN 'Conciliado Perfecto'
                    WHEN base_cruce.Cantidad_PT = 0 THEN 'No Registrado en PagoTécnico'
                    WHEN base_cruce.Cantidad_OT = 0 THEN 'No Registrado en Mano de Obra'
                    ELSE 'Discrepancia en Valores'
                END AS Estado_Conciliacion
            FROM (
                SELECT 
                    Orden_Llave AS Orden,
                    SKU_Llave AS SKU,
                    SUM(Cant_OT) AS Cantidad_OT,
                    SUM(Cant_PT) AS Cantidad_PT,
                    MAX(Cos_OT) AS Costo_OT,
                    MAX(Cos_PT) AS Costo_PT,
                    SUM(Sub_OT) AS Subtotal_OT,
                    SUM(Sub_PT) AS Subtotal_PT
                FROM (
                    /* --- BLOQUE A: ARCHIVO IMPORTADO (EXTERNO) --- */
                    SELECT 
                        LEFT(REGEXP_REPLACE(TRIM(Orden_Trabajo), '[^0-9]', ''), 8) AS Orden_Llave,
                        TRIM(Id_Servicio) AS SKU_Llave,
                        Cantidad AS Cant_OT, 0 AS Cant_PT,
                        COSTO AS Cos_OT, 0 AS Cos_PT,
                        SUBTOTAL AS Sub_OT, 0 AS Sub_PT
                    FROM ordenes_trabajo

                    UNION ALL

                    /* --- BLOQUE B: SISTEMA PROPIO (FILTRADO S y C) --- */
                    SELECT 
                        LEFT(REGEXP_REPLACE(TRIM(pt.Orden), '[^0-9]', ''), 8) AS Orden_Llave,
                        TRIM(pt.SKU) AS SKU_Llave,
                        0 AS Cant_OT, pt.Cantidad AS Cant_PT,
                        0 AS Cos_OT, IFNULL(mamo.CATEGORIACOBRO, pt.COSTOPAGO) AS Cos_PT,
                        0 AS Sub_OT, (pt.Cantidad * IFNULL(mamo.CATEGORIACOBRO, pt.COSTOPAGO)) AS Sub_PT
                    FROM pagotecnico pt
                    LEFT JOIN (
                        SELECT SKU, MAX(CATEGORIACOBRO) AS CATEGORIACOBRO 
                        FROM MaterialManoObra 
                        GROUP BY SKU
                    ) mamo ON TRIM(pt.SKU) = TRIM(mamo.SKU)
                    /* 🚀 FILTRO APLICADO: Solo estatus S y C */
                    WHERE pt.Status IN ('S', 'C')
                ) universo_plano
                GROUP BY Orden_Llave, SKU_Llave
            ) base_cruce
            /* 🚀 LEFT JOIN AGREGADO: Acoplamiento seguro de la descripción */
            LEFT JOIN (
                SELECT SKU, MAX(Descripcion) AS Descripcion
                FROM MaterialManoObra
                GROUP BY SKU
            ) cat ON TRIM(base_cruce.SKU) = TRIM(cat.SKU)
            WHERE base_cruce.Orden IN ($listaOrdenesSql)
            ORDER BY Estado_Conciliacion DESC, base_cruce.Orden ASC
        ");
        // Crear búfer en memoria temporal
        $fileOutput = fopen('php://temp', 'r+');
        fprintf($fileOutput, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

        // 🚀 CABECERAS ACTUALIZADAS: Se inyecta la columna de la descripción
        fputcsv($fileOutput, [
            'Orden', 
            'SKU / Servicio', 
            'Descripción Catálogo', // 👈 Nueva columna en posición correcta
            'Cant. Importada (OT)', 
            'Cant. PagoTécnico (PT)', 
            'Diferencia Cantidad',
            'Costo Importado (OT) (Q)', 
            'Costo PagoTécnico (MAMO) (Q)', 
            'Diferencia Costo (Q)',
            'Subtotal Importado (OT) (Q)', 
            'Subtotal PagoTécnico (PT) (Q)', 
            'Diferencia Subtotal (Q)', 
            'Estado Conciliacion'
        ]);

        foreach ($reporte as $row) {
            // 🚀 FILAS ACTUALIZADAS: Se inyecta la propiedad coincidiendo con la cabecera
            fputcsv($fileOutput, [
                $row->Orden, 
                $row->SKU,
                $row->Descripcion_Mamo, // 👈 Se mapea el campo que unificamos con el LEFT JOIN
                number_format($row->Cantidad_OT, 2, '.', ''),
                number_format($row->Cantidad_PT, 2, '.', ''),
                number_format($row->Diferencia_Cantidad, 2, '.', ''),
                number_format($row->Costo_OT, 2, '.', ''),
                number_format($row->Costo_PT, 2, '.', ''),
                number_format($row->Diferencia_Costo, 2, '.', ''),
                number_format($row->Subtotal_OT, 2, '.', ''),
                number_format($row->Subtotal_PT, 2, '.', ''),
                number_format($row->Diferencia_Subtotal, 2, '.', ''),
                $row->Estado_Conciliacion
            ]);
        }


        rewind($fileOutput);
        $csvContent = stream_get_contents($fileOutput);
        fclose($fileOutput);

        $fileName = 'Extraccion_Conciliacion_Demandada_' . date('Y-m-d_H-i') . '.csv';

        return response($csvContent, 200, [
            "Content-Type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"$fileName\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ]);

    } catch (Exception $e) {
        return redirect()->back()->with('error', 'Error en la extracción masiva: ' . $e->getMessage());
    }
}

/**
 * Método de contingencia para evitar el error de ruta inexistente
 */
public function show($id)
{
    // Redirige automáticamente al index para que el sistema no se rompa
    return redirect()->route('conciliacion.index');
}

    /**
     * Procesa la carga masiva del archivo CSV evitando duplicidades mediante UPSERT
     */

    /**
     * Ejecuta la consulta nativa UPSERT para proteger la integridad de la llave primaria 'id'
     */
    private function ejecutarUpsert(array $datos)
    {
        OrdenTrabajo::upsert(
            $datos, 
            ['id'], // 🚀 Si el ID ya existe en la BD, no se duplica, pasa a actualizar las columnas de abajo
            ['Orden_Trabajo', 'Descripcion', 'Id_Contratista', 'Id_Servicio', 'Cantidad', 'Centro_Mano_Obra', 'COSTO', 'SUBTOTAL', 'TECNO', 'updated_at']
        );
    }
}
