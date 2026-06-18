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
use App\Models\MovimientoMaterial;
use App\Models\MovimientoMateriales;
use App\Models\Persona;
use App\Models\Producto;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Arbmanoobra;
use App\Models\Treematerialescategoria;
use App\Models\Material_relaciones;

class movimientomaterialesController extends Controller
{
    public function __construct()
    {
        // Aplicar middleware de permisos
        $this->middleware('permission:ver-movimientomateriales', ['only' => ['index']]);
        $this->middleware('permission:crear-movimientomateriales', ['only' => ['create', 'store', 'exist']]);
        $this->middleware('permission:editar-movimientomateriales', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-movimientomateriales', ['only' => ['destroy']]);

    }

    public function index()
    {
  DB::connection()->disableQueryLog();

                    if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

                if ($Estatus == 'ER') {

                    $materialmanoobra = MovimientoMaterial::all();

                } else {
                    $materialmanoobra = MovimientoMaterial::where('fkTienda',$fkTienda)->get();
                }



        return view('materialmovorganizaciones.index', compact('materialmanoobra'));
    }
public function importarmamo(Request $request)
{
    DB::connection()->disableQueryLog();
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    // Elevar límites del servidor para procesos masivos y evitar caídas web
    ini_set('max_execution_time', 600); // 10 minutos
    ini_set('memory_limit', '512M');

    $fkTienda = session('user_fkTienda') ?? session('user_fktienda');
    $nombreUsuario = session('nombreUsuario') ?? 'Sistema SAP';
    $request->validate(['archivo' => 'required|file|mimes:csv,txt']);
    
    $file = fopen($request->file('archivo')->getRealPath(), 'r');
    $encabezadoOriginal = fgetcsv($file); 
    
    $encabezado = array_map(function($item) {
        return trim(strtoupper($item));
    }, $encabezadoOriginal);

    $filasRechazadas = [];
    $fila = 1;
    $insertadosContador = 0;
    $instaladosContador = 0;
    $ahora = now();
    $hoy = $ahora->format('Y-m-d');

    // Escudo para atrapar duplicados explícitos dentro de este mismo archivo
    $registrosEnEsteArchivo = [];

    // Bolsa para acumular operaciones por bloques (Chunking de 250 filas)
    $bloqueOperaciones = [];
    $tamañoBloque = 250; 

    try {
        while (($linea = fgetcsv($file)) !== false) {
            $fila++;
            $data = array_combine($encabezado, $linea);
            
            $sku = trim($data['SKU'] ?? '');
            $cantidad = floatval($data['CANTIDAD'] ?? $data['cantidad'] ?? 0);
            
            if (empty($sku) || empty($cantidad)) {
                continue;
            }

            $serie = trim($data['SERIE'] ?? '');
            $centroOrigen = trim($data['CENTRO_ORIGEN'] ?? '');
            $centroDestino = trim($data['CENTRO_DESTINO'] ?? '');

            // VALIDACIÓN A: EXISTENCIA Y JERARQUÍA DE CENTROS
            if (!empty($centroOrigen)) {
                $existeCentroOrigen = DB::table('centros_organizacion as co')
                    ->join('centro as c', 'c.id', '=', 'co.fkCentro')
                    ->where('c.codigo', $centroOrigen)
                    ->where(function($query) use ($fkTienda) {
                        $query->where('co.fkTiendaPrincipal', $fkTienda)
                              ->orWhere('co.fkTiendaDependiente', $fkTienda);
                    })
                    ->exists();

                if (!$existeCentroOrigen) {
                    $filasRechazadas[] = [
                        'fila' => $fila, 'sku' => $sku, 'serie' => $serie ?: 'N/A', 'cantidad' => $cantidad,
                        'motivo' => "El CENTRO_ORIGEN '" . $centroOrigen . "' no pertenece a la organización de esta tienda."
                    ];
                    continue;
                }
            }

            $existeCentroDestino = DB::table('centros_organizacion as co')
                ->join('centro as c', 'c.id', '=', 'co.fkCentro')
                ->where('c.codigo', $centroDestino)
                ->where(function($query) use ($fkTienda) {
                    $query->where('co.fkTiendaPrincipal', $fkTienda)
                          ->orWhere('co.fkTiendaDependiente', $fkTienda);
                })
                ->exists();

            if (!$existeCentroDestino) {
                $filasRechazadas[] = [
                    'fila' => $fila, 'sku' => $sku, 'serie' => $serie ?: 'N/A', 'cantidad' => $cantidad,
                    'motivo' => "El CENTRO_DESTINO '" . $centroDestino . "' no pertenece a la organización de esta tienda."
                ];
                continue;
            }

            // VALIDACIÓN B: ESCUDO ANTI-DUPLICADOS EN EL MISMO ARCHIVO
            $huellaMovimiento = "{$sku}-{$centroOrigen}-{$centroDestino}-{$cantidad}-{$serie}";
            
            if (in_array($huellaMovimiento, $registrosEnEsteArchivo)) {
                $filasRechazadas[] = [
                    'fila' => $fila, 'sku' => $sku, 'serie' => $serie ?: 'N/A', 'cantidad' => $cantidad,
                    'motivo' => "Registro duplicado explícito dentro del mismo archivo."
                ];
                continue;
            }

            // VALIDACIÓN C: EQUIPOS EN ESTADO "INSTALADO"
            if (!empty($serie)) {
                $registroInstalado = DB::table('movimientomateriales')
                    ->where('serie', $serie)
                    ->where('SKU', $sku)
                    ->where('fkTienda', $fkTienda)
                    ->where('ESTATUS', 'INSTALADO')
                    ->exists();

                if ($registroInstalado) {
                    $instaladosContador++;
                    continue; 
                }
            }

            // VALIDACIÓN D: CUADRE CONTABLE - UBICACIÓN DE SERIE Y STOCK DISPONIBLE
            if (!empty($centroOrigen)) {
                $inventarioOrigen = DB::table('movimientomateriales')
                    ->where('SKU', $sku)
                    ->where('CENTRO', $centroOrigen)
                    ->where('fkTienda', $fkTienda)
                    ->where('Status', 'I') 
                    ->when(!empty($serie), function($q) use ($serie) {
                        return $q->where('serie', $serie);
                    })
                    ->first();

                if (!empty($serie)) {
                    $ubicacionRealSerie = DB::table('movimientomateriales')
                        ->where('SKU', $sku)
                        ->where('serie', $serie)
                        ->where('fkTienda', $fkTienda)
                        ->where('Status', 'I')
                        ->value('CENTRO');

                    if ($ubicacionRealSerie && $ubicacionRealSerie !== $centroOrigen) {
                        $filasRechazadas[] = [
                            'fila' => $fila, 'sku' => $sku, 'serie' => $serie, 'cantidad' => $cantidad,
                            'motivo' => "Descuadre: La serie se encuentra activa en el centro '$ubicacionRealSerie'. No puede salir de '$centroOrigen'."
                        ];
                        continue;
                    }

                    if (!$inventarioOrigen) {
                        $filasRechazadas[] = [
                            'fila' => $fila, 'sku' => $sku, 'serie' => $serie, 'cantidad' => $cantidad,
                            'motivo' => "La serie no cuenta con inventario disponible en el centro '$centroOrigen'."
                        ];
                        continue;
                    }
                }

                $stockDisponible = $inventarioOrigen ? $inventarioOrigen->cantidad : 0;

                if ($stockDisponible < $cantidad) {
                    $filasRechazadas[] = [
                        'fila' => $fila, 'sku' => $sku, 'serie' => $serie ?: 'N/A', 'cantidad' => $cantidad,
                        'motivo' => "Stock insuficiente en origen '$centroOrigen' (Disponible: " . number_format($stockDisponible, 2) . ")"
                    ];
                    continue; 
                }
            }

            $registrosEnEsteArchivo[] = $huellaMovimiento;

            // ACUMULAR EN EL BLOQUE ACTUAL PARA PROCESADO EN MASA
            $bloqueOperaciones[] = [
                'sku' => $sku,
                'cantidad' => $cantidad,
                'serie' => $serie,
                'centroOrigen' => $centroOrigen,
                'centroDestino' => $centroDestino,
                'data' => $data
            ];

            // Si el bloque alcanza el tamaño límite, se procesa de golpe
            if (count($bloqueOperaciones) >= $tamañoBloque) {
                $insertadosContador += $this->procesarBloqueTransaccional($bloqueOperaciones, $fkTienda, $nombreUsuario, $ahora);
                $bloqueOperaciones = [];
            }
        } // Fin del bucle while

        // Procesar los registros que quedaron huérfanos en el último bloque
        if (count($bloqueOperaciones) > 0) {
            $insertadosContador += $this->procesarBloqueTransaccional($bloqueOperaciones, $fkTienda, $nombreUsuario, $ahora);
        }

        fclose($file);
        // STREAMING DE DESCARGA DIRECTA PARA EL ARCHIVO DE ERRORES DETECTADOS
        if (count($filasRechazadas) > 0) {
            $fileName = 'Errores_Importacion_ETA_' . date('Y-m-d_H-i') . '.csv';
            $headers = [
                "Content-type" => "text/csv; charset=UTF-8",
                "Content-Disposition" => "attachment; filename=$fileName",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ];
            $callbackErrores = function() use ($filasRechazadas) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); 
                fputcsv($out, ['Fila CSV', 'SKU', 'Número de Serie', 'Cantidad Evaluada', 'Motivo del Rechazo / Alerta']);
                foreach ($filasRechazadas as $err) {
                    fputcsv($out, [$err['fila'], $err['sku'], $err['serie'], $err['cantidad'], $err['motivo']]);
                }
                fclose($out);
            };
            return response()->stream($callbackErrores, 200, $headers);
        }

        $msg = "Proceso concluido con éxito. Se cargaron " . $insertadosContador . " movimientos de material.";
        if ($instaladosContador > 0) {
            $msg .= " Se omitieron " . $instaladosContador . " equipos por estar en estado INSTALADO.";
        }
        return back()->with('success', $msg);

    } catch (\Illuminate\Database\QueryException $e) {
        if (is_resource($file)) { fclose($file); }
        return redirect()->to('movimientomateriales/lista')->withInput()->withErrors(['archivo' => 'Error de BD: ' . $e->getMessage()]);
    } catch (\Exception $e) {
        if (is_resource($file)) { fclose($file); }
        return redirect()->to('movimientomateriales/lista')->withInput()->withErrors(['archivo' => 'Error crítico: ' . $e->getMessage()]);
    }
}
/**
 * Función auxiliar encargada de procesar el bloque bajo una única transacción de Base de Datos.
 */
private function procesarBloqueTransaccional($bloque, $fkTienda, $nombreUsuario, $ahora)
{
    $contadorLocal = 0;

    DB::transaction(function() use ($bloque, $fkTienda, $nombreUsuario, $ahora, &$contadorLocal) {
        foreach ($bloque as $operacion) {
            $sku = $operacion['sku'];
            $cantidad = $operacion['cantidad'];
            $serie = $operacion['serie'];
            $centroOrigen = $operacion['centroOrigen'];
            $centroDestino = $operacion['centroDestino'];
            $data = $operacion['data'];

            $idTecnicoOrigen = !empty($centroOrigen) ? DB::table('tecnico')->where('codigo', $centroOrigen)->value('id') : null;
            $idTecnicoDestino = !empty($centroDestino) ? DB::table('tecnico')->where('codigo', $centroDestino)->value('id') : null;
            $docRef = 'ETA-' . ($idTecnicoOrigen ?? '0') . ";" . ($idTecnicoDestino ?? '0') . ";" . $ahora->format('dmY:H:i:s') . ';' . $serie;
            
            $nombreProducto = null;
            $productoExistente = \App\Models\Producto::where('codigo', $sku)->where('fkTienda', $fkTienda)->select('nombre')->first();

            if ($productoExistente) {
                $nombreProducto = $productoExistente->nombre;
            } else {
                $materialExiste = \App\Models\Materialmanoobra::where('SKU', $sku)->where('fkTienda', $fkTienda)->select('Descripcion')->first();
                if ($materialExiste) {
                    $nombreProducto = $materialExiste->text ?? $materialExiste->Descripcion;
                } else {
                    $arbMaterialExiste = \App\Models\Arbmanoobra::where('SKU', $sku)->where('fkTienda', $fkTienda)->select('nombre')->first();
                    if ($arbMaterialExiste) {
                        $nombreProducto = $arbMaterialExiste->nombre;
                    } else {
                        $treeMateriales = \App\Models\Treematerialescategoria::where('SKU', $sku)->where('fkTienda', $fkTienda)->select('nombre')->first();
                        if ($treeMateriales) {
                            $nombreProducto = $treeMateriales->nombre;
                        }
                    }
                }
            }

            $producto = \App\Models\Producto::firstOrCreate(
                ['codigo' => $sku, 'fkTienda' => $fkTienda],
                [
                    'nombre' => mb_convert_encoding($nombreProducto ?? "Producto $sku", 'UTF-8', 'ISO-8859-1'),
                    'estado' => 1, 'marca_id' => 1, 'presentacione_id' => 1,
                    'stock' => 0, 'precio_base' => 0, 'stock_minimo' => 1, 'perecedero' => 0
                ]
            );

            $costoUnidad = \App\Models\Materialmanoobra::where('SKU', $sku)
                ->where('fkTienda', $fkTienda)
                ->select('CATEGORIACOBRO', 'COSTOPAGO', 'Descripcion', 'TIPO', 'unidadmedida')
                ->latest()
                ->first();

            $costoFinal = $costoUnidad ? doubleval($costoUnidad->COSTOPAGO) : doubleval($data['COSTO'] ?? 0);

            // EJECUCIÓN DEL TRASLADO (HISTORIAL DE SALIDA Y REDUCCIÓN EN ORIGEN)
            if (!empty($centroOrigen) && $centroOrigen != $centroDestino) {
                \App\Models\MovimientoMateriales::create([
                    'fkTienda' => $fkTienda, 'fkMateriales' => $producto->id, 'contrata' => $idTecnicoOrigen,
                    'clase_movimiento' => '311', 'cantidad' => $cantidad * -1,
                    'referencia' => "SALIDA TRASLADO SERIE: " . $serie . " | AL DESTINO " . $centroDestino,
                    'tipo_movimiento' => 'TRASPASO_SALIDA', 'documento_material' => $docRef,
                    'posicion_documento' => '0001', 'fecha_contabilizacion' => $ahora->format('Y-m-d'),
                    'almacen' => $data['ALMACEN'] ?? 'ALMA', 'centro' => $centroOrigen,
                    'unidad_medida_base' => $data['UNIDADMEDIDA'] ?? 'PZ'
                ]);

                if (!empty($serie)) {
                    DB::table('movimientomateriales')
                        ->where('SKU', $sku)->where('serie', $serie)->where('CENTRO', $centroOrigen)
                        ->where('fkTienda', $fkTienda)->where('Status', 'I')
                        ->update(['ESTATUS' => 'TRASLADADO', 'Status' => 'T', 'COSTO' => $costoFinal, 'updated_at' => $ahora]);
                } else {
                    DB::table('movimientomateriales')
                        ->where('SKU', $sku)->where('CENTRO', $centroOrigen)->where('fkTienda', $fkTienda)->where('Status', 'I')
                        ->update(['cantidad' => DB::raw("cantidad - $cantidad"), 'COSTO' => $costoFinal, 'updated_at' => $ahora]);
                }
            }

            // REGISTRO DEL MOVIMIENTO (HISTORIAL DE ENTRADA EN DESTINO)
            \App\Models\MovimientoMateriales::create([
                'fkTienda' => $fkTienda, 'fkMateriales' => $producto->id, 'contrata' => $idTecnicoDestino,
                'clase_movimiento' => !empty($centroOrigen) ? '252' : '101', 'cantidad' => $cantidad,
                'referencia' => !empty($centroOrigen) ? "ENTRADA TRASLADO SERIE: " . $serie . " | ORIGEN: " . $centroOrigen : "INSERCION INICIAL DE STOCK SERIE: " . $serie,
                'tipo_movimiento' => !empty($centroOrigen) ? 'TRASPASO_ENTRADA' : 'INSERCION_STOCK', 
                'documento_material' => $docRef, 'posicion_documento' => '0001', 'fecha_contabilizacion' => $ahora->format('Y-m-d'),
                'centro' => $centroDestino, 'almacen' => $data['ALMACEN'] ?? 'ALMA', 'unidad_medida_base' => $data['UNIDADMEDIDA'] ?? 'PZ'
            ]);

            // INCREMENTAR O REGISTRAR INVENTARIO ACTIVO EN EL DESTINO
            $stockDestinoExistente = DB::table('movimientomateriales')
                ->where('SKU', $sku)->where('CENTRO', $centroDestino)
                ->when(!empty($serie), function($q) use ($serie) { return $q->where('serie', $serie); })
                ->where('fkTienda', $fkTienda)->where('Status', 'I') 
                ->first();

            if ($stockDestinoExistente && empty($serie)) {
                DB::table('movimientomateriales')
                    ->where('id', $stockDestinoExistente->id)
                    ->update([
                        'cantidad' => $stockDestinoExistente->cantidad + $cantidad, 'COSTO' => $costoFinal,
                        'Modificado_el' => $ahora->format('Y-m-d'), 'Modificado_por' => $nombreUsuario, 'updated_at' => $ahora
                    ]);
            } else {
                DB::table('movimientomateriales')->insert([
                    'serie' => $serie, 'SKU' => $sku, 'fkTienda' => $fkTienda, 'fkTecnico' => $idTecnicoDestino, 
                    'almacen' => $data['ALMACEN'] ?? 'ALMA', 'Lote' => $data['LOTE'] ?? 'A000',
                    'MAC1' => $data['MAC1'] ?? '', 'MAC2' => $data['MAC2'] ?? '', 'MAC3' => $data['MAC3'] ?? '',
                    'COSTO' => $costoFinal, 'TIPO' => $costoUnidad->TIPO ?? $data['TIPO'] ?? 'DA',
                    'ESTATUS' => 'DISPONIBLE', 'Status' => 'I', 'Naturaleza' => 'E', 'CENTRO' => $centroDestino,
                    'cantidad' => $cantidad, 'unidadmedida' => $costoUnidad->unidadmedida ?? $data['UNIDADMEDIDA'] ?? 'PZ',
                    'TIPOMOVIMIENTO' => !empty($centroOrigen) ? 'ENTRADA' : 'INSERCION',
                    'Modificado_el' => $ahora->format('Y-m-d'), 'Modificado_por' => $nombreUsuario,
                    'Creado_el' => $ahora->format('Y-m-d'), 'Creado_por' => $nombreUsuario,
                    'created_at' => $ahora, 'updated_at' => $ahora
                ]);
            }
            $contadorLocal++;
        }
    });

    return $contadorLocal;
}



private function procesarLoteConDevolucion($batchData, $nombreUsuario)
{
    $erroresBatch = [];
    $ahora = now();

    foreach ($batchData as $item) {
        // Clonamos el item para asegurar una iteración limpia sin contaminar el lote
        $registro = $item;
        
        $numFila = $registro['num_fila'] ?? 0;
        $centroOrigen = $registro['CENTRO_ORIGEN'] ?? $registro['centro_origen'] ?? null;
        $centroDestino = $registro['CENTRO_DESTINO'] ?? $registro['centro_destino'] ?? null;
        $esSeriado = !empty($registro['serie']);
        $fechaCreadoEl = $registro['Creado_el'] ?? $ahora->format('Y-m-d');

        // Limpieza obligatoria de campos virtuales del CSV para evitar errores de columnas en MySQL
        unset($registro['num_fila']);
        unset($registro['CENTRO_ORIGEN']);
        unset($registro['CENTRO_DESTINO']);
        unset($registro['almacen_destino']);

        if (empty($centroDestino)) {
            $erroresBatch[] = [
                'fila' => $numFila, 'sku' => $registro['SKU'], 'serie' => $registro['serie'] ?? 'N/A',
                'cantidad' => $registro['cantidad'], 'motivo' => 'Rechazado: El CENTRO_DESTINO está vacío en el CSV'
            ];
            continue;
        }

        // =========================================================================
        // REQUERIMIENTO 1: RESOLVER ID DE TÉCNICO SI EL CENTRO PERTENECE A UNO
        // =========================================================================
        $idTecnicoOrigen =  !empty(DB::table('tecnico')->where('codigo', $centroOrigen)->value('id'))  ? $centroOrigen : null;
        $idTecnicoDestino = !empty(DB::table('tecnico')->where('codigo', $centroDestino)->value('id')) ? $centroDestino : null;

        // ==========================================
        // ESCENARIO A: EQUIPOS SERIADOS
        // ==========================================
        if ($esSeriado) {
            // CONTROL ANTI-DUPLICADOS ESTRICTO PARA SERIES (Busca si ya existe activo en el destino hoy)
            $duplicadoSerie = DB::table('movimientomateriales')
                ->where('SKU', $registro['SKU'])
                ->where('serie', $registro['serie'])
                ->where('CENTRO', $centroDestino)
                ->where('Status', 'A')
                ->where('Creado_el', $fechaCreadoEl)
                ->exists();

            if ($duplicadoSerie) {
                $erroresBatch[] = [
                    'fila' => $numFila, 'sku' => $registro['SKU'], 'serie' => $registro['serie'],
                    'cantidad' => $registro['cantidad'], 'motivo' => 'Omitido: El equipo seriado ya se encuentra registrado en el destino para esta fecha'
                ];
                continue;
            }

            // Buscamos el estado activo previo en la tabla técnica para proceder con el traslado
            $ultimoRegistroSeriado = DB::table('movimientomateriales')
                ->where('SKU', $registro['SKU'])
                ->where('serie', $registro['serie'])
                ->where('fkTienda', $registro['fkTienda'])
                ->where('Status', 'A')
                ->orderBy('id', 'desc')
                ->first();

            if ($ultimoRegistroSeriado && is_null($ultimoRegistroSeriado->fkTecnico)) {
                $erroresBatch[] = [
                    'fila' => $numFila, 'sku' => $registro['SKU'], 'serie' => $registro['serie'],
                    'cantidad' => $registro['cantidad'], 'motivo' => 'Rechazado: El equipo seriado ya se encuentra resguardado en Bodega'
                ];
                continue; 
            }

            if ($ultimoRegistroSeriado) {
                DB::table('movimientomateriales')
                    ->where('id', $ultimoRegistroSeriado->id)
                    ->update(['ESTATUS' => 'DEVUELTO', 'Status' => 'I', 'updated_at' => $ahora]);
            }

            // Inserción en tabla técnica
            $registroTecnico = $registro;
            $registroTecnico['CENTRO'] = $centroDestino;
            $registroTecnico['fkTecnico'] = $idTecnicoDestino;
            $registroTecnico['Status'] = 'A';
            DB::table('movimientomateriales')->insert($registroTecnico);

            // Inserción en tabla global
            $registroGlobal = $registro;
            $registroGlobal['CENTRO'] = $centroDestino;
            $registroGlobal['fkTecnico'] = $idTecnicoDestino;
            $registroGlobal['TIPOMOVIMIENTO'] = 'TRASLADO_SERIADO';
            $registroGlobal['Naturaleza'] = 'E';
            $registroGlobal['Status'] = 'A';
            DB::table('movimiento_materiales')->insert($registroGlobal);
        } 
        // ==========================================
        // ESCENARIO B: MATERIALES MISCELÁNEOS
        // ==========================================
        else {
            // Asignamos los tipos de movimiento exactos que usará el sistema
            $movimientoEntradaTipo = 'INGRESO_TRASLADO';
            $movimientoSalidaTipo = 'SALIDA_TRASLADO';

            // Si el CSV no provee Centro de Origen, se asume movimiento directo (usando el TIPOMOVIMIENTO del CSV)
            if (empty($centroOrigen)) {
                $movimientoEntradaTipo = $registro['TIPOMOVIMIENTO'] ?? 'ENTRADA';
            }

            // 1. VALIDACIÓN ANTI-DUPLICADOS CRÍTICA PARA MISCELÁNEOS (Evita dobles inserts)
            // Revisa si ya existe un registro idéntico de entrada en el destino el día de hoy
            $yaExisteIngreso = DB::table('movimiento_materiales')
                ->where('SKU', $registro['SKU'])
                ->where('CENTRO', $centroDestino)
                ->where('cantidad', (double)$registro['cantidad'])
                ->where('TIPOMOVIMIENTO', $movimientoEntradaTipo)
                ->where('Creado_el', $fechaCreadoEl)
                ->exists();

            if ($yaExisteIngreso) {
                $erroresBatch[] = [
                    'fila' => $numFila, 'sku' => $registro['SKU'], 'serie' => 'N/A',
                    'cantidad' => $registro['cantidad'], 'motivo' => 'Omitido: Este movimiento de material ya existe en la base de datos'
                ];
                continue; // Detiene la ejecución de esta fila y pasa a la siguiente
            }

            // --- A. EJECUTAR MOVIMIENTO DE SALIDA (Origen: G817) ---
            if (!empty($centroOrigen)) {
                $movimientoSalida = $registro;
                $movimientoSalida['CENTRO'] = $centroOrigen;
                $movimientoSalida['fkTecnico'] = $idTecnicoOrigen;
                $movimientoSalida['cantidad'] = (double)$registro['cantidad']; 
                $movimientoSalida['TIPOMOVIMIENTO'] = $movimientoSalidaTipo;
                $movimientoSalida['Naturaleza'] = 'S'; 
                $movimientoSalida['Status'] = 'A';
                
                DB::table('movimientomateriales')->insert($movimientoSalida);
                DB::table('movimiento_materiales')->insert($movimientoSalida);
            }

            // --- B. EJECUTAR MOVIMIENTO DE ENTRADA (Destino: 9901) ---
            $movimientoEntrada = $registro;
            $movimientoEntrada['CENTRO'] = $centroDestino;
            $movimientoEntrada['fkTecnico'] = $idTecnicoDestino;
            $movimientoEntrada['cantidad'] = (double)$registro['cantidad'];
            $movimientoEntrada['TIPOMOVIMIENTO'] = $movimientoEntradaTipo;
            $movimientoEntrada['Naturaleza'] = 'E'; 
            $movimientoEntrada['Status'] = 'A';
            
            DB::table('movimientomateriales')->insert($movimientoEntrada);
            DB::table('movimiento_materiales')->insert($movimientoEntrada);
        }
    }

    return $erroresBatch;
}


private function descargarReporteErrores($errores, $correctos)
{
    $fileName = 'Errores_Importacion_SAP_' . date('Y-m-d_H-i') . '.csv';
    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $callback = function() use ($errores) {
        $file = fopen('php://output', 'w');
        
        // BOM UTF-8 para las tildes en Excel
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Columnas explicativas del error
        fputcsv($file, ['Fila CSV Original', 'SKU', 'Serie / Tipo', 'Cantidad Enviada', 'Motivo del Rechazo (SAP Logic)']);

        foreach ($errores as $linea) {
            fputcsv($file, [
                $linea['fila'],
                $linea['sku'],
                $linea['serie'],
                $linea['cantidad'],
                $linea['motivo']
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}


public function descargarFormeta()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=Formato_Movimiento_Materiales.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

   $columnas = [
        'SERIE', 'SKU', 'almacen', 'Lote', 'MAC1', 'MAC2', 'MAC3', 
        'ESTATUS', 'COSTO', 'CENTRO_ORIGEN', 'CENTRO_DESTINO', 
        'TIPO', 'unidadmedida', 'TIPOMOVIMIENTO', 'cantidad'
    ];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        
        fputcsv($file, $columnas);

        fputcsv($file, [
            '142878214761', '1005749', 'Almacen Principal', 'LOTE-A1', 
            '00:1A:2B:3C:4D:5E', '', '', 'DF', '150.50', 
            'CENTRO-NORTE', 'CENTRO-SUR', 'DA', 'PZ', 'SALIDA', '1.00'
        ]);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}


    public function reporteTransito()
{
    $fkTienda = session('user_fkTienda');

    // Buscamos movimientos 641 que no han sido "cerrados" por un 101
    $transito = DB::table('movimiento_materiales as m641')
        ->leftJoin('movimiento_materiales as m101', function($join) {
            $join->on('m101.referencia', '=', 'm641.documento_material')
                 ->where('m101.clase_movimiento', '=', '101');
        })
        ->select(
            'm641.documento_material as guia',
            'm641.fkMateriales',
            'm641.cantidad as cantidad_enviada',
            DB::raw('COALESCE(SUM(m101.cantidad), 0) as cantidad_recibida'),
            DB::raw('(m641.cantidad - COALESCE(SUM(m101.cantidad), 0)) as pendiente')
        )
        ->where('m641.clase_movimiento', '641')
        ->where('m641.fkTienda', $fkTienda)
        ->groupBy('m641.id', 'm641.documento_material', 'm641.fkMateriales', 'm641.cantidad')
        ->having('pendiente', '>', 0) // Solo lo que sigue "volando"
        ->get();

    return view('reportes.transito', compact('transito'));
}

function traslados(){
    MovimientoMateriales::create([
    'fkTienda' => $fkTienda,
    'fkMateriales' => $productoId,
    'fkLotes' => $loteId,
    'clase_movimiento' => '311',
    'almacen' => 'Bodega Central',
    'almacen_destino' => 'Tienda Norte', // Obligatorio por el Observer
    'cantidad' => 10,
    'documento_material' => 'TRA-' . time(),
    'fecha_contabilizacion' => now(),
]);

}

    public function show($id)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        // Lógica para mostrar un cliente específico
        $cliente = Cliente::find($id);
        return redirect()->route('clientes.index')->with('success', 'Cliente registrado');
    }
    public function create()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $materialmanoobra = Materialmanoobra::all();
        return view('materialmanoobra.create', compact('materialmanoobra'));
    }

    public function storeTraslado(Request $request)
{
    // fkTiendaActual es la que envía, fkTiendaDestino es la que recibe
    $request->validate([
        'producto_id' => 'required',
        'cantidad' => 'required|numeric|min:1',
        'fkTiendaDestino' => 'required'
    ]);

    try {
        DB::beginTransaction();

        $tiendaOrigenId = Auth::user()->fkTienda; // Tienda del usuario logueado
        $productoOrigen = Producto::where('id', $request->producto_id)
                                  ->where('fkTienda', $tiendaOrigenId)
                                  ->firstOrFail();

        if ($productoOrigen->stock < $request->cantidad) {
            return back()->withErrors(['error' => 'Stock insuficiente en origen.']);
        }

        // 1. Descontar Stock Origen
        $productoOrigen->decrement('stock', $request->cantidad);

        // 2. Aumentar o Crear Stock en Destino
        // Buscamos si el producto ya existe en la tienda destino por código
        $productoDestino = Producto::where('codigo', $productoOrigen->codigo)
                                   ->where('fkTienda', $request->fkTiendaDestino)
                                   ->first();

        if ($productoDestino) {
            $productoDestino->increment('stock', $request->cantidad);
        } else {
            // Si no existe, lo clonamos a la nueva tienda
            $productoDestino = $productoOrigen->replicate();
            $productoDestino->fkTienda = $request->fkTiendaDestino;
            $productoDestino->stock = $request->cantidad;
            $productoDestino->save();
        }

        // 3. Registrar en Movimiento_Materiales
        DB::table('movimiento_materiales')->insert([
            'fkTienda' => $tiendaOrigenId,
            'fkMateriales' => $productoOrigen->id,
            'clase_movimiento' => '311', // Código estándar para traslados
            'tipo_movimiento' => 'TRASLADO',
            'origen_uso' => 'traslado_entre_bodegas',
            'cantidad' => $request->cantidad,
            'fecha_contabilizacion' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            // Agrega aquí los campos de 'centro' o 'almacen' según tus modelos de Centros
        ]);

        DB::commit();
        return redirect()->back()->with('success', 'Movimiento realizado con éxito.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
    }
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
        try {
                            if(!Auth::check()){
            return redirect()->route('login');
        }

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

    
public function fetchrelacionmovimientosmat(Request $request)
{
 try {
      DB::connection()->disableQueryLog();

        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $fechain = $request->input('fechain', now()->subDays(3)->format('Y-m-d'));
        $fechafin = $request->input('fechafin', now()->addDay()->format('Y-m-d'));
        
        // Capturar el parámetro de búsqueda general
        $search = $request->input('search') ? $request->input('search') : '';

        // Consulta base usando Query Builder (o tu Modelo si existe, ej: MovimientoMaterial::query())
        $query = DB::table('movimientomateriales')
            ->where('fkTienda', $fkTienda)
            // Filtro por rango de fechas usando Creado_el
            ->whereBetween('Creado_el', [$fechain, $fechafin]);

        // Filtrado específico por Técnico si se envía el ID
        if ($request->has('id') && !empty($request->input('id'))) {
            $query->where('fkTecnico', $request->input('id'));
        }

        // Si el usuario escribió en el buscador, aplicamos filtros tipo OR LIKE
        if (!empty($search) || $search == '') {
            $query->where(function($q) use ($search) {
                $q->where('serie', 'LIKE', "%{$search}%")
                  ->orWhere('SKU', 'LIKE', "%{$search}%")
                  ->orWhere('almacen', 'LIKE', "%{$search}%")
                  ->orWhere('Lote', 'LIKE', "%{$search}%")
                  ->orWhere('MAC1', 'LIKE', "%{$search}%")
                  ->orWhere('MAC2', 'LIKE', "%{$search}%")    
                  ->orWhere('MAC3', 'LIKE', "%{$search}%")    
                  ->orWhere('ESTATUS', 'LIKE', "%{$search}%")
                  ->orWhere('CENTRO', 'LIKE', "%{$search}%")
                  ->orWhere('TIPO', 'LIKE', "%{$search}%")
                  ->orWhere('TIPOMOVIMIENTO', 'LIKE', "%{$search}%")
                  ->orWhere('Creado_por', 'LIKE', "%{$search}%");
            });
        }

        // Ordenamos por ID de forma descendente para ver lo más reciente primero
        $query->orderBy('id', 'DESC');

        // Paginamos los resultados ya filtrados de forma limpia
        $movimientos = $query->paginate(15);

        // Si la petición es por AJAX, retornamos solo el fragmento de la tabla renderizado
        if ($request->ajax()) {
            return view('materialmovorganizaciones.tabla.movimientostable', compact('movimientos'))->render();
        }

        // Carga inicial completa de la página index
        return view('movimientos', compact('movimientos'));

    } catch (\Exception $e) {
        return response()->json(['error' => 'Error al filtrar movimientos: ' . $e->getMessage()], 500);
    }

}

public function exportarExcelMovimientos(Request $request)
{
    try {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda') ?? session('user_fktienda');
        
        // CORRECCIÓN: Aseguramos el rango de fechas correcto sin desfasar días de forma innecesaria
        $fechain = $request->input('fechain', now()->subDays(3)->format('Y-m-d'));
        $fechafin = $request->input('fechafin', now()->format('Y-m-d'));
        $search = trim($request->input('search', ''));

        // Construcción de la consulta base
        $query = DB::table('movimientomateriales')
            ->where('fkTienda', $fkTienda)
            ->whereBetween('Creado_el', [$fechain, $fechafin]);

        // Buscador general por texto
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('serie', 'LIKE', "%{$search}%")
                  ->orWhere('SKU', 'LIKE', "%{$search}%")
                  ->orWhere('almacen', 'LIKE', "%{$search}%")
                  ->orWhere('Lote', 'LIKE', "%{$search}%")
                  ->orWhere('ESTATUS', 'LIKE', "%{$search}%")
                  ->orWhere('CENTRO', 'LIKE', "%{$search}%")
                  ->orWhere('TIPO', 'LIKE', "%{$search}%")
                  ->orWhere('TIPOMOVIMIENTO', 'LIKE', "%{$search}%")
                  ->orWhere('Creado_por', 'LIKE', "%{$search}%");
            });
        }

        // Configuración de cabeceras HTTP para la descarga limpia del CSV
        $fileName = 'Reporte_Movimientos_Materiales_' . date('Y-m-d_H-i') . '.csv';
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // Generar el archivo mediante streaming usando un cursor perezoso
        $callback = function() use ($query) {
            $file = fopen('php://output', 'w');
            
            // Forzar BOM UTF-8 para compatibilidad total de acentos en Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Cabeceras del CSV
            fputcsv($file, [
                'ID', 'Serie', 'SKU', 'Almacén', 'Lote', 'Cantidad', 'Unidad Medida', 
                'MAC1', 'MAC2', 'MAC3', 'Estatus', 'Costo', 'Centro', 'Tipo', 
                'Tipo Movimiento', 'Naturaleza', 'Status Registro', 'Creado Por', 'Creado El', 'Técnico ID', 'Expediente ID'
            ]);

            // SOLUCIÓN: Usamos ->cursor() en lugar de ->chunk(). Procesa millones de filas usando 0% de memoria RAM.
            foreach ($query->cursor() as $row) {
                fputcsv($file, [
                    $row->id,
                    $row->serie,
                    $row->SKU,
                    $row->almacen,
                    $row->Lote,
                    $row->cantidad,
                    $row->unidadmedida,
                    $row->MAC1,
                    $row->MAC2,
                    $row->MAC3,
                    $row->ESTATUS,
                    $row->COSTO,
                    $row->CENTRO,
                    $row->TIPO,
                    $row->TIPOMOVIMIENTO,
                    $row->Naturaleza,
                    $row->Status,
                    $row->Creado_por,
                    $row->Creado_el ? date('d-m-Y', strtotime($row->Creado_el)) : 'N/A',
                    $row->fkTecnico,
                    $row->fkExpediente
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);

    } catch (\Exception $e) {
        return redirect()->back()->withErrors(['error' => 'Error al exportar: ' . $e->getMessage()]);
    }
}


    public function AutomataValidarMamo(Request $request)
{
      DB::connection()->disableQueryLog();
      
    if(!Auth::check()) return redirect()->route('login');
    $procesados = []; $rastro = [];
    $limite = $request->input('Orden', 10);
    
    $mamoorden = Eta::whereBetween('created_at', [
            Carbon::parse($request->fechaincio)->startOfDay(),
            Carbon::parse($request->fechafin)->endOfDay()
        ])
        ->where('fkTienda', session('user_fkTienda'))->select('Orden')->groupBy('Orden')->limit($limite)->get();

    foreach($mamoorden as $ordenitem) {
        $items = DB::table('ETA')->select('TIPO_DE_SERVICIO', 'CENTRO', 'SKU', DB::raw('SUM(cantidad) as Cantidad'))
                 ->where('fkTienda', session('user_fkTienda'))
                 ->where('Orden', $ordenitem->Orden)->groupBy('SKU', 'CENTRO')->get();

        foreach ($items as $item) {
            $this->ejecutarLogicaInterna($ordenitem->Orden, $item, $procesados, $rastro);
        }
    }

    return $this->descargarCSV($this->quitarDuplicadosPorOrdenYSKU($procesados), 'validaciones_lote.csv');
}

private function ejecutarLogicaInterna($orden, $item, &$procesados, &$rastro)
{
  DB::connection()->disableQueryLog();
$centrosEspeciales = ["'MGG845", "'MGG830", "'G888", "'MGG840","'MJG845", "'MJG830", "'MJG840","'M7G845", "'M7G830", "'M7G840"];
$patronG8 = "G888";

$esEspecial = false;
// Verificamos si es especial (por lista o por patrón 'G8)
if (str_contains($item->CENTRO, $patronG8)) {
    $esEspecial = true;
}

// 1. Aseguramos que el centro se limpie o valide bien
$centroLimpio = "'".$item->TIPO_DE_SERVICIO . substr($item->CENTRO, 1, 4);
$centrosEspeciales = ["'MGG845", "'MGG830", "'MGG840","'MJG845", "'MJG830", "'MJG840","'M7G845", "'M7G830", "'M7G840"];
$patronG8 = "G8";



// Forzamos la detección de especial
$esEspecial = false;
if (str_contains($centroLimpio, $patronG8)) {
    $esEspecial = true;
}

if ($esEspecial) {
    // CONSULTA ESPECÍFICA (Prioridad 1)
    // Usamos selectRaw para añadir una columna 'prioridad'
// 1. Construye la cadena completa incluyendo la comilla que viene en el objeto
$skuConComilla = $item->TIPO_DE_SERVICIO . substr($item->CENTRO, 1, 100) . trim($item->SKU);

if($skuConComilla=="MJG8304018238"){
    $sk="'".$skuConComilla;
}

// 1. CONSULTA ESPECÍFICA (Prioridad 1)
$especifica = Material_relaciones::selectRaw("*, 1 as prioridad")
    ->where('skufinal', 'like', '%' . $skuConComilla . '%')
    ->where('fkTienda', session('user_fkTienda'))
    ->where('minimo', '>=', 1);

    $SKUPAT=trim($item->SKU);
// 2. CONSULTA GENERAL (Prioridad 2)
$general = Material_relaciones::selectRaw("*, 2 as prioridad")
    ->where('depende_SKU', $item->SKU)
    ->where('minimo', '>=', 1)
    ->where('fkTienda', session('user_fkTienda'))
    ->where(function($q) use ($centrosEspeciales, $SKUPAT   ) {
        $q->where(function($subQuery) use ($centrosEspeciales) {
            foreach ($centrosEspeciales as $index => $ce) {
                if ($index === 0) {
                    $subQuery->where('skufinal', 'like', '%' . $ce . '%');
                } else {
                    $subQuery->orWhere('skufinal', 'like', '%' . $ce . '%');
                }
            }
        });
    });


    $ignorarpatron = Material_relaciones::selectRaw("*, 3 as prioridad")
    ->where('skufinal', 'like', '%G888' . trim($item->SKU) . '%')
    ->where('fkTienda', session('user_fkTienda'))
    ->where('minimo', '>=', 1);


// 3. CONSULTA GENERAL TOTAL (Prioridad 2)
$generalTOTAL = Material_relaciones::selectRaw("*, 2 as prioridad")
    ->where('depende_SKU', $item->SKU)
    ->where('fkTienda', session('user_fkTienda'))
    ->where('minimo', '>=', 1)
    ->where(function($q) use ($patronG8, $item) {
        $q->where('skufinal', 'not like', $patronG8 . '%')
          ->where('skufinal', 'not like', $item->SKU . '%')
          ->where('skufinal', '<>', "'G888" . $item->SKU);

    });

// 4. UNIÓN Y ELIMINACIÓN DE DUPLICADOS CON PHP
$relaciones = $especifica->union($general)->union($generalTOTAL)
    ->union($ignorarpatron)
    ->orderBy('prioridad', 'ASC')
    ->orderBy('id', 'ASC')
    ->get()             // Obtenemos todos los registros de la BD ordenados
    ->unique('id');     // Elimina los duplicados conservando siempre la prioridad más alta (1 antes que 2)

// EVALUACIÓN: Si hay prioridad 1, removemos la prioridad 3 de la colección
$resultadoFinal = $relaciones->when($relaciones->contains('prioridad', 1), function ($coleccion) {
    return $coleccion->reject(function ($registro) {
        return $registro->prioridad == 3;
    });
});

} else {
    // Centros normales
    $resultadoFinal = Material_relaciones::where('depende_SKU', $item->SKU)
        ->where('minimo', '>=', 1)
        ->where('fkTienda', session('user_fkTienda'))
        ->where(function($q) use ($centrosEspeciales, $patronG8) {
            foreach ($centrosEspeciales as $ce) {
                $q->where('skufinal', 'not like', '%' . $ce . '%');
            }
            $q->where('skufinal', 'not like', '%' . $patronG8 . '%');
        })
        ->orderBy('id', 'ASC')
        ->get();
}


if ($resultadoFinal->isEmpty()) {
    return;
}


    foreach ($resultadoFinal as $relacion) {
        // 3. Conteo de precisión: Solo cuenta lo que existe en la Orden actual
        $conteo = Material_relaciones::where('depende_SKU', $relacion->SKU)
            ->whereExists(function ($q) use ($orden) {
                $q->select(DB::raw(1))
                  ->from('ETA')
                  ->whereColumn('ETA.SKU', 'material_relaciones.SKU')
                  ->where('ETA.Orden', $orden)
                  ->where('ETA.fkTienda', session('user_fkTienda'));
            })->count();

        // 4. Caso especial: Acumulado de categoría (Máximo 10000)
        if ($relacion->maximo == 10000) {
            $monto = DB::selectOne("
SELECT SUM(e.Cantidad) AS total 
FROM ETA e 
WHERE e.Orden = ? 
  AND e.fkTienda = ?
  -- Filtramos para que el SKU de ETA pertenezca al árbol de categorías correcto
  AND e.SKU IN (
      SELECT DISTINCT tm.SKU 
      FROM treematerialescategoria tm 
      INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id 
      WHERE tmc.SKU = (
          SELECT tmc_sub.SKU 
          FROM treematerialescategoria tm_sub 
          INNER JOIN treematerialescategoria tmc_sub ON tm_sub.padre_id = tmc_sub.id 
          WHERE tm_sub.SKU = ? AND tm_sub.fkTienda = ?
          LIMIT 1
      )
  );
 ", [$orden, session('user_fkTienda'), $item->SKU, session('user_fkTienda')]);
            
            $item->Cantidad = $monto->total ?? 0;
        }

        // 5. Obtener información jerárquica del padre (Treematerialescategoria)
        $padre = DB::table('treematerialescategoria as tm')
            ->join('treematerialescategoria as tmc', 'tm.padre_id', '=', 'tmc.id')
            ->where('tm.SKU', $relacion->SKU)
            ->where('tmc.fkTienda', session('user_fkTienda'))
            ->select('tmc.SKU', 'tmc.nombre', 'tmc.minimo', 'tmc.limite', 'tmc.tipo', 'tmc.valor')
            ->first();

        // 6. Iniciar Autómata Recursivo si encontramos la información del padre
        if ($padre) {
            $this->AutomataRecursivo(
                $relacion->SKU,      // skuActual
                $orden,               // orden
                $conteo,              // recuento
                $item->Cantidad,      // valor
                $relacion->maximo ?? 0,
                $relacion->minimo ?? 0,
                $relacion->formula ?? '',
                $procesados,
                $relacion->tipo_relacion,
                0,                    // val (inicial)
                $rastro,
                $item->SKU,           // skuOrigen
                $relacion->SKU,       // skuOrigenraiz
                0,                    // nivel
                $relacion->skufinal,
                $padre,
                $item->CENTRO,
                $relacion->nombre
            );
        }
    }
}

private function descargarCSV($validaciones, $nombreArchivo)
{
    if (ob_get_level()) ob_end_clean();
    return response()->streamDownload(function () use ($validaciones) {
        $handle = fopen('php://output', 'w');
        fputcsv($handle, ['Orden','SKU_Origen','SKU_Destino','msj','ValorEntrada','Resultado','TipoRelacion','formula','Nivel','skuOrigenraiz','CENTRO','NOMBRE_Destino']);
        foreach ($validaciones as $f) {
            fputcsv($handle, [
                $f->Orden, $f->SKU_Origen, $f->SKU_Destino, $f->msj, 
                $f->ValorEntrada, $f->Resultado, $f->TipoRelacion, $f->formula, 
                $f->Nivel, $f->skuOrigenraiz, $f->CENTRO, $f->NOMBRE_Destino
            ]);
        }
        fclose($handle);
    }, $nombreArchivo);
}

private function quitarDuplicadosPorOrdenYSKU(array $items): array
{
    $unicos = [];

    foreach ($items as $item) {
        // Creamos la clave combinando el Nombre y la Orden como solicitaste
        $clave = $item->NOMBRE_Destino . '_' . $item->Orden. '_' . $item->msj;

        // Si la clave no existe, guardamos el item actual
        if (!isset($unicos[$clave])) {
            $unicos[$clave] = $item;
        } else {
            // LÓGICA DE PRIORIDAD:
            // Si el que ya tenemos guardado NO cumple (Resultado <= 0)
            // pero el nuevo SI cumple (Resultado > 0), lo reemplazamos
            $actualCumple = $unicos[$clave]->Resultado > 0;
            $nuevoCumple = $item->Resultado > 0;

            if (!$actualCumple && $nuevoCumple) {
                $unicos[$clave] = $item;
            }
        }
    }

    return array_values($unicos);
}


public function AutomataRecursivo(
    string $skuActual,
    int $orden,
    int $recuento,
    float $valor,
    float $maximo,
    float $minimo,
    string $formula,
    array &$procesados,
    string $tipoRelacion,
    float $val,
    array &$rastro,
    string $skuOrigen,
    string $skuOrigenraiz,
    int $nivel = 0,
    string $skufinal,
    ?object &$padre,
    string $Centro,
    string $mensaj=''
) {
    try {

      DB::connection()->disableQueryLog();

    // 🛑 CASO BASE 1
    if ($valor <= 0 && $nivel==0) {
        return;
    }
    $cantidad = substr_count($skuOrigen, ".");

  $clave = $orden . '_' . $skuActual. '_' .$skuOrigen . '_' . $skufinal.'_'.$tipoRelacion;
$CANT = substr_count($clave, "01.011007881TRAMO");
      if($CANT>0){
        $a="25188580_34028679_1021133_102113334028679";
    }

if (isset($procesados[$clave])) {
    return; // 🚫 Ya fue procesado este Orden + SKU
}

if (in_array($clave, $rastro)) {
    return; // 🔁 ciclo detectado
}

$rastro[] = $clave;

    if($nivel>($recuento+1) && $tipoRelacion=='calculo' ){
        return;
    }

        $cantidad=0;
    $cantidad = substr_count($skuOrigen, ".");

     if($cantidad>=1){


    $total = DB::selectOne("
SELECT SUM(total_cantidad) AS total
FROM (
    SELECT DISTINCT e.id, e.Cantidad AS total_cantidad
    FROM ETA e
    INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU
    INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
    INNER JOIN (
        SELECT DISTINCT tmc.SKU
        FROM treematerialescategoria tm
        INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
        WHERE tmc.SKU = ?
    ) AS tmcp ON tmc.SKU = tmcp.SKU
    WHERE e.Orden = ? and tmc.fkTienda = ?
) AS subconsulta
", [$skuActual,$orden, session('user_fkTienda')]);
} else{
    $total = DB::selectOne("
SELECT SUM(total_cantidad) AS total
FROM (
    SELECT DISTINCT e.id, e.Cantidad AS total_cantidad
    FROM ETA e
    INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU
    WHERE e.Orden = ? 
      AND tm.SKU = ?
      AND tm.fkTienda = ?
) AS subconsulta
", [$orden, $skuOrigen, session('user_fkTienda')]);
}

    $valor = $total->total ?? 0;

    $cantidad=0;
    $cantidad = substr_count($skuActual, ".");


    if($cantidad>=1){


    $total = DB::selectOne("
SELECT SUM(total_cantidad) AS total
FROM (
    SELECT DISTINCT e.id, e.Cantidad AS total_cantidad
    FROM ETA e
    INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU
    INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
    INNER JOIN (
        SELECT DISTINCT tmc.SKU
        FROM treematerialescategoria tm
        INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
        WHERE tmc.SKU = ?
    ) AS tmcp ON tmc.SKU = tmcp.SKU
    WHERE e.Orden = ? and tmc.fkTienda = ?
) AS subconsulta
", [$skuActual,$orden, session('user_fkTienda')]);
} else{
    $total = DB::selectOne("
SELECT SUM(total_cantidad) AS total
FROM (
    SELECT DISTINCT e.id, e.Cantidad AS total_cantidad
    FROM ETA e
    INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU
    WHERE e.Orden = ? 
      AND tm.SKU = ?
      AND tm.fkTienda = ?
) AS subconsulta
", [$orden, $skuActual, session('user_fkTienda')]);
}



    $usado = $total->total ?? 0;

$variables = [
    'minimo' => $minimo ?? 0,
    'maximo' => $maximo ?? 0,
    'valor'  => $valor ?? 0,
    'usado'  => $usado ?? 0,
    'total'  => $nivel ?? 0,
    'valant' => $val ?? 0,
];

if($clave=="25542286_02.15_34033641_02.1534033641UNIDAD_requiere"){
    $a="25188580_34028673_34028677_102113334028679";
}

$resultado=0;
    $resultado = $this->evaluarFormulaexp($formula, $variables);

$resultadoMostrar = $resultado;
$resultado= $resultado == 20000 ? 0 : $resultado;

     if($resultado > 0|| $resultado < 0) {
        if ($tipoRelacion == 'requiere'  || $tipoRelacion == 'incompatible' ) {

                $padrerel = DB::selectOne("
            SELECT distinct e.SKU FROM ETA e
            inner join material_relaciones mr on e.SKU=mr.SKU where e.Orden=? and e.SKU=? and mr.fkTienda=? ;
    ", [$orden, $skuActual, session('user_fkTienda')]);

        if($resultado <> 0){
            $nodo = (object)[
                'Orden'         => $orden,
                'SKU_Origen'    => $skuActual,
                'SKU_Destino'   => $skuOrigen,
                'msj'           => $mensaj,
                'ValorEntrada'  => $valor,
                'Resultado'     => $resultado == 10000 ? $val : $resultado,
                'TipoRelacion'  => $resultado < 0 ? $tipoRelacion . " - Exceso" : $tipoRelacion,
                'formula'       => $formula,
                'Nivel'         => $nivel,
                'children'      => [],
                'skuOrigenraiz' => $skufinal,
                'CENTRO'        => $Centro,
                'claveunica'     => $clave,
                'NOMBRE_Destino' => $padre->nombre ?? "SKU materia analizado ".$tipoRelacion." ".$skuActual,
            ];

            $procesados[$clave] = $nodo;
        }
        elseif($padrerel){
            $nodo = (object)[
                'Orden'         => $orden,
                'SKU_Origen'    => $skuActual,
                'SKU_Destino'   => $skuOrigen,
                'msj'           => $mensaj,
                'ValorEntrada'  => $valor,
                'Resultado'     => $resultado == 10000 ? $val : $resultado,
                'TipoRelacion'  => $resultado < 0 ? $tipoRelacion . " - Exceso" : $tipoRelacion,
                'formula'       => $formula,
                'Nivel'         => $nivel,
                'children'      => [],
                'skuOrigenraiz' => $skufinal,
                'CENTRO'        => $Centro,
                'claveunica'     => $clave,
                'NOMBRE_Destino' => $padre->nombre ?? "SKU materia analizado ".$tipoRelacion." ".$skuActual,
            ];

            $procesados[$clave] = $nodo;
        }


        }


    }

        $relaciones = Material_relaciones::where('skufinal', $skufinal)
    ->orderBy('id', 'ASC')
    ->get();

    $conteo = Material_relaciones::where('skufinal', $skufinal)
    ->count();


    foreach ($relaciones as $relacion) {
$a=$orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion;
            if($orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion == "25658138_34028704_34028710_40144661001856_calculo"){
        $a=$orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion;
    }

    if (in_array($orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion, $rastro)) {
    continue; // 🔁 ciclo detectado
}



    $aSKUFILTRO=$clave;
        $tipoRelacion=$relacion->tipo_relacion;

      if($aSKUFILTRO==$orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion){
        $a="25188580_34028673_34028677_102113334028679";
  $clave=$orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal;
  $rastro[] = $clave;
    continue; // 🚫 Evitar procesar el mismo nodo en esta ram
    }
$SKUSSS=$relacion->SKU;
    if($tipoRelacion=="calculo" ){
          $padre = DB::selectOne("
            SELECT distinct e.SKU FROM ETA e
            inner join material_relaciones mr on e.SKU=mr.depende_SKU where e.Orden=? and mr.depende_SKU=? and mr.fkTienda=?;
    ", [$orden, $relacion->SKU, session('user_fkTienda')]);

        if (!$padre) {
             $cantidad = substr_count($relacion->depende_SKU, ".");
        if($cantidad==0){
            $cantidad = substr_count($relacion->SKU, ".");
        if($cantidad==0){
             continue;
        }
        }
    }
    }



    // 1️⃣ Obtener SKU padre
    $padre = DB::selectOne("
        SELECT
            tmc.SKU,
            tmc.nombre,
            tmc.minimo,
            tmc.limite,
            tmc.tipo,
            tmc.valor
        FROM treematerialescategoria tm
        INNER JOIN treematerialescategoria tmc
            ON tm.padre_id = tmc.id
        WHERE tm.SKU = ? AND tmc.fkTienda = ?
        LIMIT 1
    ", [$relacion->SKU, session('user_fkTienda')]);

    // 🛑 CASO BASE 2
    if (!$padre) {
        continue;
    }

    if($aSKUFILTRO=="25298449_34025712_4018238_401823834028673_calculo"){
        $a="25188580_34028673_34028677_102113334028679";
    }

        // 🔁 llamada recursiva
        $this->AutomataRecursivo(
            $relacion->SKU,
            $orden,
            $conteo,
            $resultadoMostrar == 20000 ? $val : $resultadoMostrar,
            $relacion->maximo   ?? 0,
            $relacion->minimo   ?? 0,
            $relacion->formula,
            $procesados,
            $relacion->tipo_relacion,
            $resultadoMostrar == 20000 ? $val : $resultadoMostrar,
    $rastro,
    $relacion->depende_SKU,
    $skuOrigenraiz,
    $nivel + 1,
    $relacion->skufinal,
    $padre,
                     $Centro,
                     $relacion->nombre
        );
    }  } catch (Exception $e) {

            Log::error('Error al registrar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el cliente.');
        }

}




private function evaluarFormula
(string $formula, array $variables)
{
    try {

        $exp = new \Symfony\Component\ExpressionLanguage\ExpressionLanguage();

        // round
        $exp->register(
            'round',
            fn ($value, $precision = 0) => sprintf('round(%s, %s)', $value, $precision),
            fn ($variables, $value, $precision = 0) => round($value, $precision)
        );

        // floor (ENTERO en Excel)
        $exp->register(
            'floor',
            fn ($value) => sprintf('floor(%s)', $value),
            fn ($variables, $value) => floor($value)
        );

        // ceil
        $exp->register(
            'ceil',
            fn ($value) => sprintf('ceil(%s)', $value),
            fn ($variables, $value) => ceil($value)
        );

        // max
        $exp->register(
            'max',
            fn (...$args) => sprintf('max(%s)', implode(',', $args)),
            fn ($variables, ...$args) => max(...$args)
        );

        // min
        $exp->register(
            'min',
            fn (...$args) => sprintf('min(%s)', implode(',', $args)),
            fn ($variables, ...$args) => min(...$args)
        );

        return $exp->evaluate($formula, $variables);

    } catch (\Throwable $e) {
        Log::error('Error al evaluar fórmula: ' . $e->getMessage());
        return 0;
    }
}


}
