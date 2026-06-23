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

    $fkTienda = session('user_fkTienda');
    $request->validate([
        'archivo' => 'required|file|mimes:csv,txt',
    ]);

    // 1. Leer el archivo binario completo de forma segura
    $realPath = $request->file('archivo')->getRealPath();
    $fileData = file_get_contents($realPath);

    // 2. DETECCIÓN Y CONVERSIÓN DE CODIFICACIÓN GLOBAL (Evita rotura de caracteres y llaves)
    $encoding = mb_detect_encoding($fileData, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    if ($encoding !== 'UTF-8') {
        $fileData = mb_convert_encoding($fileData, 'UTF-8', $encoding);
    }

    // 3. Crear un stream de memoria temporal con los datos ya normalizados en UTF-8
    $file = fopen('php://temp', 'r+');
    fwrite($file, $fileData);
    rewind($file);

    // 4. Leer el encabezado limpio (Ya en UTF-8)
    $encabezado = fgetcsv($file); 
    
    // Quitar espacios residuales o caracteres extraños ocultos en las llaves del encabezado
    $encabezado = array_map(function($val) {
        return trim(preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $val));
    }, $encabezado);

    DB::beginTransaction();

    try {
        while (($linea = fgetcsv($file)) !== false) {
            // Unir columnas con llaves de encabezado
            $data = array_combine($encabezado, $linea);

            // Validar campos mínimos obligatorios
            if (!isset($data['Descripcion']) || !isset($data['SKU']) || !isset($data['unidadmedida']) || 
                !isset($data['CATEGORIA']) || !isset($data['COSTOPAGO']) || !isset($data['CATEGORIACOBRO'])) {
                continue; 
            }

            // Insertar o actualizar de forma atómica en tu tabla MySQL
            Materialmanoobra::updateOrCreate(
                [
                    'SKU' => trim($data['SKU']),
                    'centrocostoespecifico' => !empty($data['centrocostoespecifico']) ? trim($data['centrocostoespecifico']) : null,
                    'fkTienda' => $fkTienda ?? 0,
                    'TIPO'           => trim($data['TIPO'] ?? '')
                ],
                [
                    'SKU'            => trim($data['SKU']),
                    'Descripcion'    => trim($data['Descripcion']),
                    'unidadmedida'   => trim($data['unidadmedida'] ?? ''),
                    'CATEGORIA'      => trim($data['CATEGORIA'] ?? ''),
                    'COSTOPAGO'      => (float) ($data['COSTOPAGO'] ?? 0),
                    'CATEGORIACOBRO' => (float) ($data['CATEGORIACOBRO'] ?? 0)
                ]
            );
        }

        DB::commit();
        fclose($file);
        return back()->with('success', 'Mano de Obra o Materiales importados correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        fclose($file);
        return back()->with('error', 'Error al importar registros: ' . $e->getMessage());
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

            return redirect()->route('materialmanoobra.index')->with('success', 'Eliminado Exitosamente');
        } catch (Exception $e) {
            Log::error('Error al cambiar estado del MAMO - ID: ' . $id . ' - Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar el estado del cliente.');
        }
    }
}
