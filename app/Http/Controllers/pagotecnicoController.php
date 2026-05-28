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

    // 4. Aplicar Filtro por Rango de Fechas (usando created_at o la columna de tu preferencia)
    if ($request->filled('fecha_inicio')) {
        $query->whereDate('created_at', '>=', $request->input('fecha_inicio'));
    }
    if ($request->filled('fecha_fin')) {
        $query->whereDate('created_at', '<=', $request->input('fecha_fin'));
    }

    // 5. Obtener los registros filtrados para la tabla
    $pagostecnico = $query->latest()->get();

    // 6. CALCULAR EL BALANCE BALANCEADO (Suma algebraicamente según la Naturaleza)
    // Suponemos que Naturaleza 'D' es un Cargo/Salida (-) y 'H' o cualquier otra es un Abono/Crédito (+)
    $totalBalance = 0;
    foreach ($pagostecnico as $pago) {
        if ($pago->Naturaleza === 'D') {
            $totalBalance -= floatval($pago->COSTOPAGO); // Restar si es salida/cargo
        } else {
            $totalBalance += floatval($pago->COSTOPAGO); // Sumar si es entrada/crédito
        }
    }

    // Opcional: Obtener lista de técnicos para el select del filtro
    $tecnicos = DB::table('tecnico')->select('id', 'nombre')->get();

    return view('pagotecnicos.index', compact('pagostecnico', 'totalBalance', 'tecnicos'));
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

    // Columnas exactas que requiere tu tabla pagostecnico
    $columnas = [
        'Orden',
        'SKU',
        'Descripcion',
        'OBS',
        'Cantidad',
        'COSTOPAGO',
        'Status',
        'Naturaleza',
        'fkTecnico',
        'fkTienda'
    ];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        
        // Añadir el BOM UTF-8 para que Excel reconozca los acentos correctamente al abrir el CSV
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($file, $columnas); // Encabezado de la tabla

        // Línea de ejemplo con datos ficticios pero coherentes con tu negocio:
        // Orden, SKU, Descripcion, Cantidad, COSTOPAGO, Status, Naturaleza, fkTecnico
        fputcsv($file, [
            'ORD-100254',
            'SKU-MO-001',
            'INSTALACION TOMA DE LINEA RJ11',
            'Instalación Caja Adicional Servicio Técnico',            
            1,
            250.00,
            'C', // Status (ej: S para aprobado/pendiente)
            'H', // Naturaleza (H para Suma, D para Resta)
            12   // ID del Técnico asignado
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
            'I'                            // Status (char 2)
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
        fputcsv($file, ['ID Pago', 'Orden / Expediente', 'SKU', 'Descripcion', 'Cantidad', 'Costo Pago ($)', 'Naturaleza', 'Estatus', 'ID Técnico', 'Fecha Registro']);

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

DB::connection()->disableQueryLog(); 

    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $fkTienda = session('user_fkTienda') ?? 0;
    
    // Configuraciones de alto rendimiento para archivos masivos
    set_time_limit(0); 
    ini_set('memory_limit', '512M');
    
    // Desactivar logs de consultas (Crucial para no colapsar la memoria RAM con miles de inserts)
    DB::connection()->disableQueryLog();

    $request->validate([
        'archivo' => 'required|file|mimes:csv,txt',
    ]);

    $file = fopen($request->file('archivo')->getRealPath(), 'r');
    
    // Leer el encabezado del CSV y limpiar el posible carácter invisible BOM UTF-8
    $encabezadoRaw = fgetcsv($file);
    if ($encabezadoRaw && str_contains($encabezadoRaw[0], chr(0xEF).chr(0xBB).chr(0xBF))) {
        $encabezadoRaw[0] = str_replace(chr(0xEF).chr(0xBB).chr(0xBF), '', $encabezadoRaw[0]);
    }
    $encabezado = $encabezadoRaw;

    $insertados = 0;
    $omitidos = 0;
    
    $batchSize = 500; 
    $batchData = [];
    $now = now();

    try {
        while (($linea = fgetcsv($file)) !== false) {
            // Validar que la línea tenga el mismo número de campos que las columnas del encabezado
            if (count($encabezado) !== count($linea)) {
                $omitidos++;
                continue;
            }

            $data = array_combine($encabezado, $linea);

            // Validaciones de campos mandatorios para la tabla pagotecnico
            if (empty($data['Orden']) || empty($data['SKU']) || !isset($data['COSTOPAGO'])) {
                $omitidos++;
                continue;
            }

            // Normalización y limpieza profunda de Naturaleza (D / H) y Status
            $naturaleza = strtoupper(trim($data['Naturaleza'] ?? 'D'));
            if (!in_array($naturaleza, ['D', 'H'])) {
                $naturaleza = 'D'; // Forzar valor contable base si el usuario escribe otra letra
            }

            $obtenervalor= Materialmanoobra::where('SKU', $data['SKU'])->first();
            $valorcosto=floatval($data['COSTOPAGO'] ?? 0.00);

            if($valorcosto==0){
                
            if($obtenervalor->CATEGORIA === 'MANO DE OBRA'){
                $data['COSTOPAGO'] = $obtenervalor ? floatval($obtenervalor->COSTOPAGO) : 0.00;    
                } elseif($obtenervalor->CATEGORIA === 'MATERIAL')  {
                $data['COSTOPAGO'] = $obtenervalor ? floatval($obtenervalor->CATEGORIACOBRO) : 0.00;
                } else {
                    $data['COSTOPAGO'] = 0.00; // Valor por defecto si no se encuentra la categoría
                }

            } else{

                $data['COSTOPAGO'] = isset($data['COSTOPAGO']) ? floatval($data['COSTOPAGO']) : 0;

            }

            $status = substr(trim($data['Status'] ?? 'I'), 0, 2); // Garantizar formato varchar(2) máximo

            // Mapear el lote alineado a las columnas exactas de tu tabla 'pagotecnico'
            $batchData[] = [
                'Orden'       => $data['Orden'],
                'SKU'         => $data['SKU'],
                'Descripcion' => mb_convert_encoding($data['Descripcion'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'OBS'         => mb_convert_encoding($data['OBS'] ?? 'Importación masiva', 'UTF-8', 'ISO-8859-1'),
                'Cantidad'    => floatval($data['Cantidad'] ?? 1.00),
                'COSTOPAGO'   => floatval($data['COSTOPAGO'] ?? 0.00),
                'fkTienda'    => $fkTienda,
                'fkTecnico'   => intval($data['fkTecnico'] ?? 0),
                'Naturaleza'  => $naturaleza,
                'Status'      => $status,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];

            $insertados++;

            // Inserción masiva en transacciones atómicas parciales al alcanzar el tamaño del lote
if (count($batchData) >= $batchSize) {
    DB::transaction(function () use ($batchData) {
        DB::table('pagotecnico')->upsert(
            $batchData, 
            ['Orden', 'SKU'], // 1. Columnas que definen si el registro ya existe (Deben tener índice UNIQUE en la BD)
            ['Descripcion', 'OBS', 'Cantidad', 'COSTOPAGO', 'Naturaleza', 'Status', 'updated_at'] // 2. Columnas a actualizar si se halla duplicado
        );
    });
    $batchData = [];
    gc_collect_cycles();
}

        }

        // Procesar remanentes que no alcanzaron a completar el último múltiplo de 500
        if (!empty($batchData)) {
            DB::transaction(function () use ($batchData) {
                DB::table('pagotecnico')->insert($batchData);
            });
        }

        fclose($file);

        return back()->with('success', "Importación de pagos exitosa: {$insertados} filas procesadas con éxito, {$omitidos} omitidas por errores.");

    } catch (\Exception $e) {
        if (is_resource($file)) {
            fclose($file);
        }
        return back()->with('error', 'Error crítico en procesamiento Cloud de Pagos: ' . $e->getMessage());
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
