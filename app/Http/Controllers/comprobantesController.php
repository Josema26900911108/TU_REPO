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
$request->validate([
    'tipo_comprobante' => [
        'required',
        Rule::unique('comprobantes', 'tipo_comprobante')
            ->where(fn($query) =>
                $query->where('fkTienda', session('user_fkTienda'))
            )
    ],
    'formula' => 'required',
    'clavevista' => 'required'
]);


        try {
            $fkTienda = session('user_fkTienda');

            DB::beginTransaction();
            //Crear rol
            Comprobante::create(['tipo_comprobante' => $request->tipo_comprobante,
            'formula'=> $request->formula,
            'estado'=> 1,
            'ClaveVista'=>$request->clavevista,
            'fkPlantillaHtml' => $request->disdoc,
            'fkTienda' => $fkTienda]);


            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
        }


        return redirect()->route('comprobante.index')->with('success', 'Rol registrado');
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
    public function edit(Comprobante $comprobante)
    {
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


        $request->validate([
            'tipo_comprobante' => [
                'required',
                'max:50',
                Rule::unique('comprobantes')->where(function ($query) {
                    return $query->where('fkTienda', session('user_fkTienda'));
                })->ignore($comprobante->id), // Ignorar el comprobante actual en caso de que sea una actualización
            ],
            'formula' => [
                'required',
                'max:250'
            ],
        ]);


        try {
            DB::beginTransaction();
            $comprobante->fill([
                'tipo_comprobante' => $request->tipo_comprobante,
                'formula' => $request->formula,
                'ClaveVista' => $request->clavevista,
                'fkPlantillaHtml' => $request->disdoc,
            ]);

            $comprobante->save();
            DB::commit();
        } catch (Exception $e) {
            dd($e);
            DB::rollBack();
        }

        return redirect()->route('comprobante.index')->with('success', 'Comprobante editado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        DetalleComprobante::where('fkComprobante', $id)->delete();
        Comprobante::where('id', $id)->delete();



        return redirect()->route('comprobante.index')->with('success', 'rol eliminado');
    }
    public function storeDetalleComprobante(Request $request)
{
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
