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
    // Obtener el ID de la tienda desde la sesión con un valor por defecto seguro (0)
    $tiendaId = session('user_fkTienda', 0);

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
                WHERE fkTienda = {$tiendaId}

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
                    /* 🚀 FILTRO ACTUALIZADO: Costos filtrados por la tienda actual */
                    SELECT SKU, MAX(CATEGORIACOBRO) AS CATEGORIACOBRO 
                    FROM MaterialManoObra 
                    WHERE fkTienda = {$tiendaId}
                    GROUP BY SKU
                ) mamo ON TRIM(pt.SKU) = TRIM(mamo.SKU)
                WHERE pt.Status IN ('S', 'C') 
                  AND pt.fkTienda = {$tiendaId}
            ) universo_plano
            GROUP BY Orden_Llave, SKU_Llave
        ) base_cruce
        LEFT JOIN (
            /* 🚀 FILTRO ACTUALIZADO: Descripciones filtradas por la tienda actual */
            SELECT SKU, MAX(Descripcion) AS Descripcion
            FROM MaterialManoObra
            WHERE fkTienda = {$tiendaId}
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
public function exportarConcili0acion(Request $request)
{
    set_time_limit(0);
    ini_set('memory_limit', '512M');

    try {
        // 🚀 RECUPERAR DE SESIÓN: Obtenemos la tienda activa y el filtro de órdenes
        $tiendaId = session('user_fkTienda', 0);
        $ordenesFiltro = session('ultimas_ordenes_importadas', []);

        if (empty($ordenesFiltro)) {
            return redirect()->back()->with('error', 'No hay registros recientes en la sesión para exportar. Por favor, sube un archivo CSV primero.');
        }

        // 🔍 DETECTOR DINÁMICO DE DELIMITADOR DE SALIDA (Para evitar celdas amontonadas en Windows)
        // Si el navegador del usuario está en español, Excel en Windows requiere ";" por defecto
        $lang = $request->server('HTTP_ACCEPT_LANGUAGE');
        $delimitadorSalida = (strpos($lang, 'es') !== false) ? ";" : ",";

        // Convertimos el array de órdenes a un formato seguro para la consulta SQL
        $listaOrdenesSql = implode(',', array_map(function($item) {
            return "'" . addslashes($item) . "'";
        }, $ordenesFiltro));

        // Ejecutamos la consulta inyectando el filtro de tienda en el cruce de raíz
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
                    WHERE fkTienda = {$tiendaId}

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
                        WHERE fkTienda = {$tiendaId}
                        GROUP BY SKU
                    ) mamo ON TRIM(pt.SKU) = TRIM(mamo.SKU)
                    WHERE pt.Status IN ('S', 'C')
                      AND pt.fkTienda = {$tiendaId}
                ) universo_plano
                GROUP BY Orden_Llave, SKU_Llave
            ) base_cruce
            LEFT JOIN (
                SELECT SKU, MAX(Descripcion) AS Descripcion
                FROM MaterialManoObra
                WHERE fkTienda = {$tiendaId}
                GROUP BY SKU
            ) cat ON TRIM(base_cruce.SKU) = TRIM(cat.SKU)
            WHERE base_cruce.Orden IN ($listaOrdenesSql)
            ORDER BY Estado_Conciliacion DESC, base_cruce.Orden ASC
        ");

        // Inicializar el puntero temporal de archivo en memoria RAM
        $file = fopen('php://temp', 'r+');

        // Inyectar el BOM UTF-8 para mantener acentos y emojis legibles en Windows
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // 1. Cabeceras utilizando el delimitador dinámico detectado
        fputcsv($file, [
            'Orden (8 Dígitos)', 'SKU / Servicio', 'Descripción Catálogo',
            'Cant. Importada (OT)', 'Cant. PagoTécnico (PT)', 'Diferencia Cantidad',
            'Costo Importado (OT) (Q)', 'Costo PagoTécnico (MAMO) (Q)', 'Diferencia Costo (Q)',
            'Subtotal Importado (OT) (Q)', 'Subtotal PagoTécnico (PT) (Q)', 'Diferencia Subtotal (Q)', 
            'Estado Conciliación'
        ], $delimitadorSalida);

        // 2. Inyección de las filas en el ciclo foreach aplicando el delimitador dinámico
        foreach ($reporte as $row) {
            fputcsv($file, [
                $row->Orden,
                $row->SKU,
                $row->Descripcion_Mamo,
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
            ], $delimitadorSalida);
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
        // 🚀 RECUPERAR DE SESIÓN: Obtenemos el ID de la tienda del usuario actual
        $tiendaId = session('user_fkTienda', 0);

        $path = $request->file('csv_ordenes')->getRealPath();
        $fileInput = fopen($path, 'r');

        // 🔍 DETECTOR DINÁMICO DE DELIMITADOR DE ENTRADA (PC del usuario)
        $primeraLinea = fgets($fileInput);
        $delimitadorEntrada = ","; // Por defecto
        
        if ($primeraLinea !== false) {
            $conteoComas = substr_count($primeraLinea, ',');
            $conteoPuntosComas = substr_count($primeraLinea, ';');
            $conteoTabuladores = substr_count($primeraLinea, "\t");

            if ($conteoPuntosComas > $conteoComas) {
                $delimitadorEntrada = ";";
            } elseif ($conteoTabuladores > $conteoComas && $conteoTabuladores > $conteoPuntosComas) {
                $delimitadorEntrada = "\t";
            }
        }
        
        // Reseteamos el puntero para leer el archivo desde el principio incluyendo la primera línea si no tiene título
        rewind($fileInput);
        
        $ordenesFiltro = [];
        
        // Recorremos el archivo del usuario con el delimitador correcto detectado
        while (($fila = fgetcsv($fileInput, 0, $delimitadorEntrada)) !== FALSE) {
            if (isset($fila[0])) { 
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

        // 🔍 DETECTOR DINÁMICO DE DELIMITADOR DE SALIDA (Para evitar celdas amontonadas al descargar)
        $lang = $request->server('HTTP_ACCEPT_LANGUAGE');
        $delimitadorSalida = (strpos($lang, 'es') !== false) ? ";" : ",";

        // Ejecutar la consulta relacional inyectando el filtro estricto de fkTienda
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
                    /* 🎯 Filtro por tienda en archivo externo */
                    WHERE fkTienda = {$tiendaId}

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
                        WHERE fkTienda = {$tiendaId}
                        GROUP BY SKU
                    ) mamo ON TRIM(pt.SKU) = TRIM(mamo.SKU)
                    /* 🎯 Filtro por estatus y tienda en sistema interno */
                    WHERE pt.Status IN ('S', 'C')
                      AND pt.fkTienda = {$tiendaId}
                ) universo_plano
                GROUP BY Orden_Llave, SKU_Llave
            ) base_cruce
            /* 🚀 LEFT JOIN AGREGADO: Acoplamiento seguro de la descripción por tienda */
            LEFT JOIN (
                SELECT SKU, MAX(Descripcion) AS Descripcion
                FROM MaterialManoObra
                WHERE fkTienda = {$tiendaId}
                GROUP BY SKU
            ) cat ON TRIM(base_cruce.SKU) = TRIM(cat.SKU)
            WHERE base_cruce.Orden IN ($listaOrdenesSql)
            ORDER BY Estado_Conciliacion DESC, base_cruce.Orden ASC
        ");

        // Crear búfer en memoria temporal
        $fileOutput = fopen('php://temp', 'r+');
        fprintf($fileOutput, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

        // Cabeceras actualizadas utilizando el delimitador de salida inteligente
        fputcsv($fileOutput, [
            'Orden', 
            'SKU / Servicio', 
            'Descripción Catálogo', 
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
        ], $delimitadorSalida);

        foreach ($reporte as $row) {
            fputcsv($fileOutput, [
                $row->Orden, 
                $row->SKU,
                $row->Descripcion_Mamo, 
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
            ], $delimitadorSalida);
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
/**
 * 🚀 FUNCIÓN ACTUALIZADA: Inserta o actualiza los registros amarrados a fkTienda
 */
private function ejecutarUpsert(array $loteDatos)
{
    if (empty($loteDatos)) return;

    // Ejecutamos el Upsert masivo de Eloquent / Query Builder
    DB::table('ordenes_trabajo')->upsert(
        $loteDatos, 
        // 🎯 COLUMNAS DE IDENTIFICACIÓN: 
        // Si el ID ya existe, la base de datos sabrá que debe actualizar en lugar de duplicar
        ['id'], 
        
        // 🎯 COLUMNAS A ACTUALIZAR:
        // Si hay coincidencia de ID, estas son las columnas que refrescarán sus valores en la BD
        [
            'fkTienda',          // 🚀 Guardamos/Aseguramos la tienda correcta en la actualización
            'Orden_Trabajo', 
            'Descripcion', 
            'Id_Contratista', 
            'Id_Servicio', 
            'Cantidad', 
            'Centro_Mano_Obra', 
            'COSTO', 
            'SUBTOTAL', 
            'TECNO', 
            'updated_at'         // Solo actualizamos la fecha de modificación
        ]
    );
}

}
