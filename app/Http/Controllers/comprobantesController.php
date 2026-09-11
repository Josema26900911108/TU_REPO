<?php

namespace App\Http\Controllers;
use Exception;
use App\Models\Comprobante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\DetalleComprobante;
use App\Models\DocumentDesings;
use App\Models\plantillahtml;
use Database\Seeders\DatosestaticosSeeder;
use Illuminate\Support\Facades\Auth;


class comprobantesController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-comprobante|crear-comprobante|mostrar-comprobante|eliminar-comprobante', ['only' => ['index']]);
        $this->middleware('permission:crear-comprobante', ['only' => ['create', 'store']]);
        $this->middleware('permission:mostrar-comprobante', ['only' => ['show']]);
        $this->middleware('permission:eliminar-comprobante', ['only' => ['destroy']]);
    }


public function index()
{
                    if(!Auth::check()){
            return redirect()->route('login');
        }

    $fkTienda = session('user_fkTienda');
    $Estatus = session('user_estatus');

    // -------------------------
    // COMPROBANTES
    // -------------------------
    $comprobanteQuery = DB::table('comprobantes')
        ->join('tienda', 'comprobantes.fkTienda', '=', 'tienda.idTienda')
        ->select('comprobantes.*', 'tienda.nombre as tienda_nombre')
        ->where('comprobantes.estado', 1);

    if ($Estatus != 'ER') {
        $comprobanteQuery->where('comprobantes.fkTienda', $fkTienda);
    }

    $comprobante = $comprobanteQuery
        ->orderBy('comprobantes.created_at', 'desc')
        ->paginate(10);


    // -------------------------
    // DETALLES DE COMPROBANTES
    // -------------------------
    $detallecomprobanteQuery = DB::table('detalle_comprobantes AS dc')
        ->select(
            'dc.*',
            'c.tipo_comprobante AS comprobante_nombre',
            'cc.nombre AS cuenta_contable_nombre',
            'cc.formula AS cuenta_contable_numero',
            'c.defauldoc AS comprobante_defauldoc',
            DB::raw("(select sum(valorminimo) from detalle_comprobantes as ddc where ddc.fkComprobante=dc.fkComprobante and ddc.Naturaleza='D') AS Debe"),
            DB::raw("(select sum(valorminimo) from detalle_comprobantes as ddc where ddc.fkComprobante=dc.fkComprobante  and ddc.Naturaleza='H') AS Haber")
        )
        ->join('comprobantes AS c', 'dc.fkComprobante', '=', 'c.id')
        ->join('cuentas_contables AS cc', 'dc.fkCuentaContable', '=', 'cc.id')
        ->where('c.estado', 1);

    if ($Estatus != 'ER') {
        $detallecomprobanteQuery->where('c.fkTienda', $fkTienda);
    }

    $detallecomprobante = $detallecomprobanteQuery
        ->orderBy('dc.created_at', 'DESC')
        ->get();

    return view('comprobante.index', compact('comprobante', 'detallecomprobante'));
}



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        // Inicializar la consulta de comprobantes
        $comprobanteQuery = Comprobante::with('tienda')->where('estado', 1);

        if ($Estatus != 'ER') {
            // Filtrar comprobantes solo por la tienda del usuario
            $comprobanteQuery->where('fkTienda', $fkTienda);
        }

        // Obtener los comprobantes más recientes
        $comprobante = $comprobanteQuery->latest()->get();

        $clavevista = DatosestaticosSeeder::getVistas();

        $designs = plantillahtml::where('fkTienda',$fkTienda)->get();

        // Pasar los datos a la vista
        return view('comprobante.create', compact('comprobante','clavevista','designs'));
    }


    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    if(!Auth::check()){
        return redirect()->route('login');
    }

    $request->validate([
        'tipo_comprobante' => [
            'required',
            Rule::unique('comprobantes', 'tipo_comprobante')
                ->where(fn($query) =>
                    $query->where('fkTienda', session('user_fkTienda'))
                )
        ],
        'formula' => 'required',
        'clavevista' => 'required',
        'compdefault' => 'nullable|boolean' // Se agrega validación para el check
    ]);

    try {
        $fkTienda = session('user_fkTienda');
        // Aseguramos un valor booleano (1 o 0) para la base de datos
        $isDefault = $request->has('compdefault') ? 1 : 0; 

        DB::beginTransaction();

        // Si el nuevo comprobante será el por defecto, desactivamos los anteriores de la misma vista y tienda
        if ($isDefault == 1) {
            Comprobante::where('ClaveVista', $request->clavevista)
                ->where('fkTienda', $fkTienda)
                ->update(['defauldoc' => 0]);
        }

        // Crear el nuevo comprobante
        Comprobante::create([
            'tipo_comprobante' => $request->tipo_comprobante,
            'formula'=> $request->formula,
            'estado'=> 1,
            'ClaveVista'=>$request->clavevista,
            'defauldoc'=>$isDefault,
            'fkPlantillaHtml' => $request->disdoc,
            'fkTienda' => $fkTienda
        ]);

        DB::commit();
    } catch (Exception $e) {
        DB::rollBack();
        // Es recomendable retornar un mensaje de error si la transacción falla
        return redirect()->back()->with('error', 'Error al registrar el comprobante: ' . $e->getMessage());
    }

    return redirect()->route('comprobante.index')->with('success', 'Comprobante registrado con éxito');
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
function edit(Comprobante $comprobante)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');
        $id = $comprobante->id;

        if ($Estatus == 'ER') {
            // Encontrar el comprobante con el estado 1 y cargar la tienda
            $comprobante = Comprobante::with('tienda')
                ->where('estado', 1)
                ->where('id',$id)
                ->latest()
                ->first(); // Usamos first() para obtener solo un comprobante
        } else {
            // Filtrar los comprobantes solo por la tienda del usuario
            $comprobante = Comprobante::with('tienda')
                ->where('fkTienda', $fkTienda)
                ->where('estado', 1)
                ->where('id',$id)
                ->latest()
                ->first(); // De nuevo, obtener un solo comprobante
        }
        $clavevista = DatosestaticosSeeder::getVistas();
            $designs = plantillahtml::where('fkTienda',$fkTienda)->get();
        // Retornamos la vista con el comprobante encontrado
        return view('comprobante.edit', compact('designs','comprobante','clavevista'));
    }


    /**
     * Update the specified resource in storage.
     */
public function update(Request $request, Comprobante $comprobante)
{
    if(!Auth::check()){
        return redirect()->route('login');
    }

    $request->validate([
        'tipo_comprobante' => [
            'required',
            'max:50',
            Rule::unique('comprobantes')->where(function ($query) {
                return $query->where('fkTienda', session('user_fkTienda'));
            })->ignore($comprobante->id), 
        ],
        'formula' => [
            'required',
            'max:250'
        ],
        'clavevista' => 'required',
        'compdefault' => 'nullable|boolean' 
    ]);

    try {
        $fkTienda = session('user_fkTienda');
        $isDefault = $request->has('compdefault') ? 1 : 0; 

        DB::beginTransaction();

        if ($isDefault == 1) {
            Comprobante::where('ClaveVista', $request->clavevista)
                ->where('fkTienda', $fkTienda)
                ->where('id', '!=', $comprobante->id) 
                ->update(['defauldoc' => 0]);
        }

        // SOLUCIÓN: Asignación directa de propiedades para ignorar restricciones de $fillable
        $comprobante->tipo_comprobante = $request->tipo_comprobante;
        $comprobante->formula          = $request->formula;
        $comprobante->ClaveVista       = $request->clavevista;
        $comprobante->defauldoc        = $isDefault;
        $comprobante->fkPlantillaHtml  = $request->disdoc; // Forzamos el nuevo diseño aquí

        $comprobante->save();
        DB::commit();
    } catch (Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Error al actualizar el comprobante: ' . $e->getMessage());
    }

    return redirect()->route('comprobante.index')->with('success', 'Comprobante editado con éxito');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        DetalleComprobante::where('fkComprobante', $id)->delete();
        Comprobante::where('id', $id)->delete();



        return redirect()->route('comprobante.index')->with('success', 'rol eliminado');
    }
    public function storeDetalleComprobante(Request $request)
{
                    if(!Auth::check()){
            return redirect()->route('login');
        }
        
    // Validación de los datos que vienen del formulario
    $request->validate([
        'nombre' => 'required|string|max:255',
        'formula' => 'required|string|max:255',
        'valorminimo' => 'required|numeric|min:0',
        'fkComprobante' => 'required|exists:comprobantes,id',
        'fkCuentaContable' => 'nullable|exists:cuentas_contables,id',
    ]);

    // Inserción de los datos en la tabla detalle_comprobantes
    DB::table('detalle_comprobantes')->insert([
        'nombre' => $request->input('nombre'),
        'formula' => $request->input('formula'),
        'valorminimo' => $request->input('valorminimo'),
        'fkComprobante' => $request->input('fkComprobante'),
        'fkCuentaContable' => $request->input('fkCuentaContable'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Retorna una respuesta
    return response()->json(['success' => 'Detalle de comprobante agregado exitosamente']);
}

}
