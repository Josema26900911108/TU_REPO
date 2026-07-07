<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialExistenteSap;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;

class MaterialSapController extends Controller
{

public function index(Request $request)
{
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $fkTienda = session('user_fkTienda');
    $Estatus = session('user_estatus');
    $page = $request->input('page', 1) ?? 1;

    // CORREGIDO: Se cambia 'MaterialExistenteSap' por 'movimientomaterial'
    $query = MaterialExistenteSap::select('MaterialExistenteSap.*', 'tienda.Nombre as nombre_tienda')
        ->leftJoin('tienda', 'MaterialExistenteSap.fkTienda', '=', 'tienda.idTienda'); 

    if ($Estatus != 'ER') {
        $query->where('MaterialExistenteSap.fkTienda', $fkTienda);
    }

    // Paginamos 15 registros por página desde el servidor
    $materialmanoobra = $query->paginate(15, ['*'], 'page', $page);

    return view('stocksap.index', compact('materialmanoobra'));
}

    /**
     * Procesa e importa el archivo CSV de materiales a la tabla movimientomaterial
     */
    public function importaExistenciaSaprMAMO(Request $request)
    {
        DB::connection()->disableQueryLog();

        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $nombreUsuario = session('nombreUsuario') ?? Auth::user()->name ?? 'Sistema';

        // Validar que el archivo sea CSV o TXT
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt|max:10240', // Máximo 10MB
        ]);

        $file = fopen($request->file('archivo')->getRealPath(), 'r');
        $encabezado = fgetcsv($file); 
        
        $fkTienda = session('user_fkTienda');

        DB::beginTransaction();
        $fila = 1;
        
        MaterialExistenteSap::where('fkTienda', $fkTienda)->delete(); // Elimina todos los registros existentes para la tienda actual antes de importar
        
        try {
            while (($linea = fgetcsv($file)) !== false) {
                $fila++;
                
                // Combinar la fila de encabezados con los valores de la línea actual
                $data = array_combine($encabezado, $linea);

                // 1. VALIDACIÓN DE CAMPOS CRÍTICOS
                if (empty($data['SKU'])) {
                    continue; // Saltar filas sin código SKU
                }

                $sku = trim($data['SKU']);
                $serie = !empty($data['serie']) ? trim($data['serie']) : null;
                $ahora = now();
                $fechaHoy = $ahora->toDateString();

                // 2. BUSCAR DUPLICADOS EXISTENTES (Por SKU + Serie, o SKU + MAC1)
                $materialExistente = MaterialExistenteSap::where('SKU', $sku)
                    ->when($serie, function($query) use ($serie) {
                        return $query->where('serie', $serie);
                    })
                    ->when(empty($serie) && !empty($data['MAC1']), function($query) use ($data) {
                        return $query->where('MAC1', trim($data['MAC1']));
                    })
                    ->when(empty($serie) && !empty($data['almacen']), function($query) use ($data) {
                        return $query->where('almacen', trim($data['almacen']));
                    })
                    ->when(empty($serie) && !empty($data['Lote']), function($query) use ($data) {
                        return $query->where('Lote', trim($data['Lote']));
                    })
                    ->when(empty($serie) && !empty($data['cantidad']), function($query) use ($data) {
                        return $query->where('cantidad', floatval($data['cantidad']));
                    })
                    ->when(empty($serie) && !empty($data['COSTO']), function($query) use ($data) {
                        return $query->where('COSTO', floatval($data['COSTO']));
                    })
                    ->where('fkTienda', $fkTienda)
                    ->first();

                if ($materialExistente) {
                    // Actualizar registro existente
                    $materialExistente->update([
                        'almacen'         => $data['almacen'] ?? $materialExistente->almacen,
                        'Lote'            => $data['Lote'] ?? $materialExistente->Lote,
                        'ESTATUS'         => $data['ESTATUS'] ?? $materialExistente->ESTATUS,
                        'COSTO'           => isset($data['COSTO']) ? floatval($data['COSTO']) : $materialExistente->COSTO,
                        'fkTienda'        => $fkTienda,
                        'CENTRO'          => $data['CENTRO'] ?? $materialExistente->CENTRO,
                        'Modificado_el'   => $fechaHoy,
                        'Modificado_por'  => $nombreUsuario,
                        'TIPO'            => $data['TIPO'] ?? $materialExistente->TIPO,
                        'unidadmedida'    => $data['unidadmedida'] ?? $materialExistente->unidadmedida,
                        'TIPOMOVIMIENTO'  => $data['TIPOMOVIMIENTO'] ?? 'ACTUALIZACION_MAMO',
                        'cantidad'        => isset($data['cantidad']) ? floatval($data['cantidad']) : $materialExistente->cantidad,
                    ]);
                } else {
                    // Insertar nuevo registro utilizando el Modelo
                    MaterialExistenteSap::create([
                        'serie'          => $serie,
                        'SKU'            => $sku,
                        'fkTienda'        => $fkTienda,
                        'almacen'        => $data['almacen'] ?? null,
                        'Lote'           => $data['Lote'] ?? null,
                        'MAC1'           => !empty($data['MAC1']) ? trim($data['MAC1']) : null,
                        'MAC2'           => !empty($data['MAC2']) ? trim($data['MAC2']) : null,
                        'MAC3'           => !empty($data['MAC3']) ? trim($data['MAC3']) : null,
                        'ESTATUS'        => $data['ESTATUS'] ?? 'A',
                        'COSTO'          => isset($data['COSTO']) ? floatval($data['COSTO']) : 0.00,
                        'CENTRO'         => $data['CENTRO'] ?? null,
                        'Modificado_el'  => $fechaHoy,
                        'Modificado_por' => $nombreUsuario,
                        'Creado_el'      => $fechaHoy,
                        'Creado_por'     => $nombreUsuario,
                        'TIPO'           => $data['TIPO'] ?? null,
                        'unidadmedida'   => $data['unidadmedida'] ?? null,
                        'TIPOMOVIMIENTO' => $data['TIPOMOVIMIENTO'] ?? 'IMPORTACION_MAMO',
                        'cantidad'       => isset($data['cantidad']) ? floatval($data['cantidad']) : 0.00,
                    ]);
                }
            }

            fclose($file);
            DB::commit();
            return back()->with('success', 'Materiales SAP (MAMO) importados y procesados correctamente.');

        } catch (Exception $e) {
            DB::rollBack();
            if (isset($file)) {
                fclose($file);
            }
            Log::error('Error al importar Materiales SAP: ' . $e->getMessage());
            return back()->with('error', 'Error en la fila del archivo ' . $fila . ': ' . $e->getMessage());
        }
    }

    /**
     * Genera y descarga de forma inmediata la plantilla vacía en formato CSV
     */
    public function descargarFormatoExistenciaSap()
    {
        $headers = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=plantilla_importacion_existencia_sap.csv',
            'Expires' => '0',
            'Pragma' => 'public'
        ];

        // Columnas exactas que espera recibir el array_combine en el método importarMAMO
        $columns = ['serie', 'SKU', 'almacen', 'Lote', 'MAC1', 'MAC2', 'MAC3', 'ESTATUS', 'COSTO', 'cantidad', 'CENTRO', 'TIPO', 'unidadmedida', 'TIPOMOVIMIENTO'];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns); 
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
