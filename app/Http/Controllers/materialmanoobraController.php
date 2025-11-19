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
        // Lógica para mostrar un cliente específico
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

    $columnas = ['SKU','Descripcion','TIPO','unidadmedida','CATEGORIA','COSTOPAGO','CATEGORIACOBRO','fkTienda'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        // Línea de ejemplo opcional:
        fputcsv($file, ['663483', 'Ejemplo mano de obra material', 'TE04', 'PZA','MATERIAL',1525.89306122449,1525.89306122449,3]);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

public function importarMAMO(Request $request)
{
    $request->validate([
        'archivo' => 'required|file|mimes:csv,txt',
    ]);

    $file = fopen($request->file('archivo')->getRealPath(), 'r');
    $encabezado = fgetcsv($file); // leer encabezados

    DB::beginTransaction();

    try {
        while (($linea = fgetcsv($file)) !== false) {
            $data = array_combine($encabezado, $linea);

            // Validar campos mínimos
            if (!isset($data['Descripcion']) || !isset($data['SKU']) || !isset($data['unidadmedida']) || !isset($data['CATEGORIA']) || !isset($data['COSTOPAGO']) || !isset($data['CATEGORIACOBRO']) || !isset($data['fkTienda'])) continue;

            $descripcion = mb_convert_encoding($data['Descripcion'] ?? '', 'UTF-8', 'ISO-8859-1');
            // Insertar o actualizar
            Materialmanoobra::updateOrCreate(
                ['SKU' => $data['SKU']],
                [
                    'SKU'    => $data['SKU'],
                    'Descripcion'    => $descripcion ?? '',
                    'TIPO'  => $data['TIPO'] ?? '',
                    'unidadmedida'  => $data['unidadmedida'] ?? '',
                    'CATEGORIA'  => $data['CATEGORIA'] ?? '',
                    'COSTOPAGO'  => $data['COSTOPAGO'] ?? 0,
                    'CATEGORIACOBRO'  => $data['CATEGORIACOBRO'] ?? 0,
                    'fkTienda'  => $data['fkTienda'] ?? 0
                ]
            );
        }

        DB::commit();
        return back()->with('success', 'Mano de Obra o Materiales importados correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error al importar: ' . $e->getMessage());
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

            Materialmanoobra::destroy('id',$id);

            return redirect()->route('materialmanoobra.index')->with('success', 'Eliminado Exitosamente');
        } catch (Exception $e) {
            Log::error('Error al cambiar estado del MAMO - ID: ' . $id . ' - Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar el estado del cliente.');
        }
    }
}
