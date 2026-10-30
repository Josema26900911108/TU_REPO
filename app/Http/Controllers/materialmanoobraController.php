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


class materialmanoobraController extends Controller
{
    public function __construct()
    {
        // Aplicar middleware de permisos
        $this->middleware('permission:ver-materialmanoobra', ['only' => ['index']]);
        $this->middleware('permission:crear-materialmanoobra', ['only' => ['create', 'store', 'exist']]);
        $this->middleware('permission:editar-materialmanoobra', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-materialmanoobra', ['only' => ['destroy']]);

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

                    $materialmanoobra = Materialmanoobra::all();

                } else {
                    $materialmanoobra = Materialmanoobra::where('fkTienda',$fkTienda)->get();
                }



        return view('materialmanoobra.index', compact('materialmanoobra'));
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
        $materialmanoobra = Materialmanoobra::all();
        return view('materialmanoobra.create', compact('materialmanoobra'));
    }

    public function descargarFormato()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=formato_productos.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $idTienda= session('user_fkTienda');

    $columnas = ['SKU','Descripcion','TIPO','unidadmedida','CATEGORIA','COSTOPAGO','CATEGORIACOBRO','centrocostoespecifico'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        // Línea de ejemplo opcional:
        fputcsv($file, ['663483', 'Ejemplo mano de obra material', 'TE04', 'PZA','MATERIAL',1525.89306122449,1525.89306122449,'D087018 O EN BLANCO SI ES GENERAL']);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}
public function importarMAMO(Request $request)
{
    DB::connection()->disableQueryLog();

    if (!Auth::check()) {
        return redirect()->route('login');
    }

    // Validación robusta: por extensión, no por MIME (el MIME varía según SO/navegador)
    $request->validate([
        'archivo' => 'required|file|max:20480', // 20 MB
    ]);

    $archivo = $request->file('archivo');
    $ext = strtolower($archivo->getClientOriginalExtension());
    if (!in_array($ext, ['csv', 'txt'])) {
        return back()->with('error', 'El archivo debe ser .csv o .txt (recibido: .' . $ext . ')');
    }

    $fkTienda = session('user_fkTienda');

    // 1. Leer contenido completo
    $realPath = $archivo->getRealPath();
    $fileData = file_get_contents($realPath);
    if ($fileData === false || $fileData === '') {
        return back()->with('error', 'El archivo está vacío o no se pudo leer.');
    }

    // 2. Quitar BOM UTF-8 si existe
    $fileData = preg_replace('/^\xEF\xBB\xBF/', '', $fileData);

    // 3. Detectar y convertir codificación a UTF-8
    $encoding = mb_detect_encoding($fileData, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    if ($encoding && $encoding !== 'UTF-8') {
        $fileData = mb_convert_encoding($fileData, 'UTF-8', $encoding);
    }

    // 4. Detectar delimitador automáticamente (coma, punto y coma o tab)
    $primeraLinea = strtok($fileData, "\r\n");
    $delim = ',';
    $candidatos = [',' => 0, ';' => 0, "\t" => 0];
    foreach ($candidatos as $d => $_) {
        $candidatos[$d] = substr_count($primeraLinea, $d);
    }
    arsort($candidatos);
    $delim = array_key_first($candidatos);
    if ($candidatos[$delim] === 0) {
        $delim = ','; // fallback
    }

    // 5. Stream en memoria con datos normalizados
    $file = fopen('php://temp', 'r+');
    fwrite($file, $fileData);
    rewind($file);

    // 6. Leer encabezado y normalizarlo
    $encabezadoRaw = fgetcsv($file, 0, $delim);
    if (!$encabezadoRaw || count($encabezadoRaw) < 2) {
        fclose($file);
        return back()->with('error', 'No se pudo leer el encabezado del CSV (delimitador detectado: "' . $delim . '"). Verifica el archivo.');
    }

    // Normalizar encabezados: trim, quitar caracteres de control, quitar BOM residual, mapear a canónico
    $mapaCanonico = [
        'descripcion'          => 'Descripcion',
        'sku'                  => 'SKU',
        'unidadmedida'         => 'unidadmedida',
        'unidad de medida'     => 'unidadmedida',
        'categoria'            => 'CATEGORIA',
        'costopago'            => 'COSTOPAGO',
        'costo pago'           => 'COSTOPAGO',
        'categoriacobro'       => 'CATEGORIACOBRO',
        'categoria cobro'      => 'CATEGORIACOBRO',
        'tipo'                 => 'TIPO',
        'centrocostoespecifico'=> 'centrocostoespecifico',
        'centro costo especifico' => 'centrocostoespecifico',
    ];

    $encabezado = [];
    foreach ($encabezadoRaw as $col) {
        $col = preg_replace('/^\xEF\xBB\xBF/', '', $col);          // BOM residual
        $col = trim(preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $col)); // control chars
        $clave = mb_strtolower($col, 'UTF-8');
        $clave = preg_replace('/\s+/', ' ', $clave);               // espacios múltiples
        $encabezado[] = $mapaCanonico[$clave] ?? $col;
    }

    // Validar que estén las columnas obligatorias
    $requeridas = ['Descripcion', 'SKU', 'unidadmedida', 'CATEGORIA', 'COSTOPAGO', 'CATEGORIACOBRO'];
    $faltantes = array_diff($requeridas, $encabezado);
    if (!empty($faltantes)) {
        fclose($file);
        return back()->with('error', 'Faltan columnas en el CSV: ' . implode(', ', $faltantes)
            . '. Columnas encontradas: ' . implode(', ', $encabezado));
    }

    DB::beginTransaction();
    $insertados = 0;
    $saltados   = 0;

    try {
        while (($linea = fgetcsv($file, 0, $delim)) !== false) {
            // Saltar líneas vacías
            if ($linea === [null] || (count($linea) === 1 && trim((string)$linea[0]) === '')) {
                continue;
            }

            // Tolerar filas con menos/más columnas que el encabezado
            $n = count($encabezado);
            if (count($linea) < $n) {
                $linea = array_pad($linea, $n, '');
            } elseif (count($linea) > $n) {
                $linea = array_slice($linea, 0, $n);
            }

            $data = array_combine($encabezado, $linea);
            if ($data === false) {
                $saltados++;
                continue;
            }

            // Obtener valores con fallback tolerante
            $get = function($key) use ($data) {
                return isset($data[$key]) ? trim((string)$data[$key]) : '';
            };

            $sku = $get('SKU');
            $descripcion = $get('Descripcion');
            if ($sku === '' || $descripcion === '') {
                $saltados++;
                continue;
            }

            $centro = $get('centrocostoespecifico');

            Materialmanoobra::updateOrCreate(
                [
                    'SKU'                    => $sku,
                    'centrocostoespecifico'  => $centro !== '' ? $centro : null,
                    'fkTienda'               => $fkTienda ?? 0,
                    'TIPO'                   => $get('TIPO'),
                ],
                [
                    'SKU'            => $sku,
                    'Descripcion'    => $descripcion,
                    'unidadmedida'   => $get('unidadmedida'),
                    'CATEGORIA'      => $get('CATEGORIA'),
                    'COSTOPAGO'      => $this->parseDecimal($get('COSTOPAGO')),
                    'CATEGORIACOBRO' => $this->parseDecimal($get('CATEGORIACOBRO')),
                ]
            );
            $insertados++;
        }

        DB::commit();
        fclose($file);

        $msg = "Importación completada: {$insertados} registros procesados";
        if ($saltados > 0) {
            $msg .= ", {$saltados} filas omitidas (sin SKU o Descripción)";
        }
        return back()->with('success', $msg . '.');

    } catch (\Throwable $e) {
        DB::rollBack();
        if (is_resource($file)) fclose($file);
        \Log::error('Error importarMAMO: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return back()->with('error', 'Error al importar registros: ' . $e->getMessage());
    }
}

/**
 * Convierte un valor decimal tolerando coma o punto decimal
 * y separadores de miles. Ej: "1.234,56" -> 1234.56
 */
private function parseDecimal($valor)
{
    $valor = trim((string)$valor);
    if ($valor === '') return 0.0;

    // Quitar símbolos de moneda y espacios
    $valor = preg_replace('/[^\d,.\-]/', '', $valor);

    $tieneComa = strpos($valor, ',') !== false;
    $tienePunto = strpos($valor, '.') !== false;

    if ($tieneComa && $tienePunto) {
        // Formato europeo: 1.234,56 -> quitar puntos, coma a punto
        if (strrpos($valor, ',') > strrpos($valor, '.')) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } else {
            // Formato US: 1,234.56 -> quitar comas
            $valor = str_replace(',', '', $valor);
        }
    } elseif ($tieneComa) {
        // Solo coma: puede ser decimal (1,56) o miles (1,234)
        // Si hay exactamente 2 dígitos tras la última coma -> decimal
        if (preg_match('/,\d{2}$/', $valor)) {
            $valor = str_replace(',', '.', $valor);
        } else {
            $valor = str_replace(',', '', $valor);
        }
    }

    return (float) $valor;
}

public function descargarFormatoEditar()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=formato_actualizacion_mamo.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    // NUEVO: Añadimos 'id' al inicio del arreglo de columnas
    $columnas = ['id', 'SKU', 'Descripcion', 'TIPO', 'unidadmedida', 'CATEGORIA', 'COSTOPAGO', 'CATEGORIACOBRO', 'centrocostoespecifico'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // Encabezado con ID

        // Línea de ejemplo indicando un ID existente
        fputcsv($file, ['15', '663483', 'Ejemplo mano de obra material modificado', 'TE04', 'PZA', 'MATERIAL', 1525.89, 1525.89, 'D087018']);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}
public function importarMAMOEditar(Request $request)
{
    DB::connection()->disableQueryLog();
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $fkTienda = session('user_fkTienda');
    $request->validate([
        'archivoedit' => 'required|file|mimes:csv,txt',
    ]);

    $realPath = $request->file('archivoedit')->getRealPath();
    $fileData = file_get_contents($realPath);

    // 1. ELIMINAR EL BOM UTF-8 SI EXISTE (Crucial para que Excel no rompa el ID)
    if (strpos($fileData, pack('H*', 'EFBBBF')) === 0) {
        $fileData = substr($fileData, 3);
    }

    // Detección y conversión de codificación
    $encoding = mb_detect_encoding($fileData, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    if ($encoding !== 'UTF-8') {
        $fileData = mb_convert_encoding($fileData, 'UTF-8', $encoding);
    }

    $file = fopen('php://temp', 'r+');
    fwrite($file, $fileData);
    rewind($file);

    $encabezado = fgetcsv($file); 
    
    // Limpieza estricta de cabeceras (Elimina espacios ocultos en los nombres de columnas)
    $encabezado = array_map(function($val) {
        return trim(preg_replace('/[\x00-\x1F\x7F-\x9F\xA0]/u', '', $val));
    }, $encabezado);

    // Verificar si la columna 'id' realmente existe en el encabezado procesado
    if (!in_array('id', $encabezado)) {
        fclose($file);
        return back()->with('error', 'El archivo CSV no contiene una columna llamada "id" en su cabecera.');
    }

    DB::beginTransaction();

    try {
        $contadorActualizados = 0;

        while (($linea = fgetcsv($file)) !== false) {
            // Prevenir errores si la línea está vacía o faltan columnas
            if (count($encabezado) !== count($linea)) {
                continue;
            }

            // Unir columnas con llaves de encabezado
            $data = array_combine($encabezado, $linea);

            // Limpieza agresiva del valor del ID de la fila
            $idLimpio = isset($data['id']) ? trim(preg_replace('/[\x00-\x1F\x7F-\x9F\xA0]/u', '', $data['id'])) : '';

            if (empty($idLimpio)) {
                continue; 
            }

            // Ejecutar la actualización masiva por ID
            $afectado = Materialmanoobra::where('id', $idLimpio)
                ->update([
                    'SKU'                   => trim($data['SKU'] ?? ''),
                    'Descripcion'           => trim($data['Descripcion'] ?? ''),
                    'TIPO'                  => trim($data['TIPO'] ?? ''),
                    'unidadmedida'          => trim($data['unidadmedida'] ?? ''),
                    'CATEGORIA'             => trim($data['CATEGORIA'] ?? ''),
                    'COSTOPAGO'             => (float) ($data['COSTOPAGO'] ?? 0),
                    'CATEGORIACOBRO'        => (float) ($data['CATEGORIACOBRO'] ?? 0),
                    'centrocostoespecifico' => !empty($data['centrocostoespecifico']) ? trim($data['centrocostoespecifico']) : null,
                    'fkTienda'              => $fkTienda ?? 0 
                ]);

            if ($afectado > 0) {
                $contadorActualizados++;
            }
        }

        DB::commit();
        fclose($file);

        if ($contadorActualizados === 0) {
            return back()->with('error', 'El proceso terminó pero se actualizaron 0 registros. Verifica que los IDs del CSV coincidan exactamente con la base de datos.');
        }

        return back()->with('success', "Se actualizaron con éxito {$contadorActualizados} registros.");

    } catch (\Exception $e) {
        DB::rollBack();
        fclose($file);
        return back()->with('error', 'Error al procesar la actualización por ID: ' . $e->getMessage());
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
                            if(!Auth::check()){
            return redirect()->route('login');
        }
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
                            if(!Auth::check()){
            return redirect()->route('login');
        }
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
                        if(!Auth::check()){
            return redirect()->route('login');
        }
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
                if(!Auth::check()){
            return redirect()->route('login');
        }
            Materialmanoobra::destroy('id',$id);

            return redirect()->route('manoobramaterial.index')->with('success', 'Eliminado Exitosamente');
        } catch (Exception $e) {
            Log::error('Error al cambiar estado del MAMO - ID: ' . $id . ' - Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar el estado del cliente.');
        }
    }
}
