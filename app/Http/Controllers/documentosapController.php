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
use App\Models\DocumentoSap;
use App\Models\Persona;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
class DocumentoSapController extends Controller
{
    public function __construct()
    {
        // Aplicar middleware de permisos
        $this->middleware('permission:ver-documentosap', ['only' => ['index']]);
        $this->middleware('permission:crear-documentosap', ['only' => ['create', 'store', 'exist']]);
        $this->middleware('permission:editar-documentosap', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-documentosap', ['only' => ['destroy']]);

    }

public function index(Request $request)
{
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $fkTienda = session('user_fkTienda');
    $Estatus = session('user_estatus');

    // Cambiamos a get() para cargar todos los datos en la memoria de JS de una sola vez
    $query = DocumentoSap::select('DocumentoSAP.*', 'tienda.Nombre as nombre_tienda')
        ->leftJoin('tienda', 'DocumentoSAP.fkTienda', '=', 'tienda.idTienda');

    if ($Estatus != 'ER') {
        $query->where('DocumentoSAP.fkTienda', $fkTienda);
    }

    // OPTIMIZACIÓN: get() elimina las recargas de página lentas
    $documentos = $query->get();

    return view('documento.index', compact('documentos'));
}

    /**
     * Importador masivo nativo mediante lectura de archivos CSV
     */
    public function importar(Request $request)
    {
        DB::connection()->disableQueryLog();
        if (!Auth::check()) return redirect()->route('login');

        $fkTienda = session('user_fkTienda');

        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = fopen($request->file('archivo')->getRealPath(), 'r');
        $encabezado = fgetcsv($file); 

        DB::beginTransaction();
        $fila = 1;

        try {
          
        DocumentoSap::where('fkTienda', $fkTienda)->delete();

        while (($linea = fgetcsv($file)) !== false) {
                $fila++;
                $data = array_combine($encabezado, $linea);

                // Validación de campo crítico
                if (empty($data['numero_documento'])) continue;

                $numDoc = trim($data['numero_documento']);
                
                // Formatear Fecha
                $fechaCont = null;
                if (!empty($data['fecha_contabilizacion_sap'])) {
                    try {
                        $fechaCont = Carbon::parse($data['fecha_contabilizacion_sap'])->toDateString();
                    } catch (\Exception $e) {
                        $fechaCont = now()->toDateString();
                    }
                }

                // Buscar si el documento ya se encuentra registrado para actualizar o crear
                $docExistente = DocumentoSap::where('numero_documento', $numDoc)
                ->when(!empty($data['serie']), function($query) use ($data) {
                    return $query->where('serie', trim($data['serie']));
                })
                ->when(!empty($data['SKU']), function($query) use ($data) {
                    return $query->where('SKU', trim($data['SKU']));
                })
                ->when(!empty($data['cantidad_sap']), function($query) use ($data) {
                    return $query->where('cantidad_sap', trim($data['cantidad_sap']));
                })
                ->first();

                if ($docExistente) {
                    $docExistente->update([
                        'referencia_sap'               => $data['referencia_sap'] ?? $docExistente->referencia_sap,
                        'texto_clase_movimiento_sap'   => $data['texto_clase_movimiento_sap'] ?? $docExistente->texto_clase_movimiento_sap,
                        'unidad_medida_base_sap'       => $data['unidad_medida_base_sap'] ?? $docExistente->unidad_medida_base_sap,
                        'fecha_contabilizacion_sap'    => $fechaCont ?? $docExistente->fecha_contabilizacion_sap,
                        'cantidad_sap'                 => $data['cantidad_sap'] ?? $docExistente->cantidad_sap,
                        'clase_movimiento_sap'         => $data['clase_movimiento_sap'] ?? $docExistente->clase_movimiento_sap,
                        'centro_sap'                   => $data['centro_sap'] ?? $docExistente->centro_sap,
                        'Naturaleza'                   => $data['Naturaleza'] ?? $docExistente->Naturaleza,
                        'Status'                       => $data['Status'] ?? $docExistente->Status,
                        'serie'                        => $data['serie'] ?? $docExistente->serie,
                        'fkTienda'                     => $fkTienda,
                        'SKU'                          => $data['SKU'] ?? $docExistente->SKU,
                    ]);
                } else {
                    DocumentoSap::create([
                        'numero_documento'             => $numDoc,
                        'referencia_sap'               => $data['referencia_sap'] ?? null,
                        'texto_clase_movimiento_sap'   => $data['texto_clase_movimiento_sap'] ?? null,
                        'unidad_medida_base_sap'       => $data['unidad_medida_base_sap'] ?? null,
                        'fecha_contabilizacion_sap'    => $fechaCont,
                        'cantidad_sap'                 => $data['cantidad_sap'] ?? '0',
                        'clase_movimiento_sap'         => $data['clase_movimiento_sap'] ?? null,
                        'centro_sap'                   => $data['centro_sap'] ?? null,
                        'fkTienda'                     => $fkTienda,
                        'Naturaleza'                   => $data['Naturaleza'] ?? 'E',
                        'Status'                       => $data['Status'] ?? 'AC',
                        'serie'                        => $data['serie'] ?? null,
                        'SKU'                          => $data['SKU'] ?? null,
                    ]);
                }
            }

            fclose($file);
            DB::commit();
            return back()->with('success', 'Documentos SAP procesados e importados correctamente.');

        } catch (Exception $e) {
            DB::rollBack();
            if (isset($file)) fclose($file);
            Log::error('Error al importar Documentos SAP: ' . $e->getMessage());
            return back()->with('error', 'Error en la fila ' . $fila . ': ' . $e->getMessage());
        }
    }

    /**
     * Descarga de plantilla CSV estructurada
     */
    public function descargarFormato()
    {
        $headers = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=plantilla_documento_sap.csv',
            'Expires' => '0',
            'Pragma' => 'public'
        ];

        $columns = [
            'numero_documento', 'referencia_sap', 'texto_clase_movimiento_sap', 
            'unidad_medida_base_sap', 'fecha_contabilizacion_sap', 'cantidad_sap', 
            'clase_movimiento_sap', 'centro_sap', 'Naturaleza', 'Status', 'SKU', 'serie'
        ];  

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns); 
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    public function show($id)
    {
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

    public function store(StorePersonaRequest $request)
    {
        try {

                        if(!Auth::check()){
            return redirect()->route('login');
        }

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
        $clientes = Cliente::select('id', 'persona_id')
        ->get();
        return response()->json($clientes);
    }

    public function listaClientes(Request $request)
    {
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
}
