<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tienda;
use App\Models\plantillahtml;
use App\Http\Requests\StoreTiendaRequest;
use App\Models\DocumentDesings;
use App\Models\plantillahtmlgeneral;
use GuzzleHttp\Promise\Create;
use Barryvdh\DomPDF\Facade\Pdf;
use Dom\Document;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
class tiendaController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-tienda|crear-tienda|editar-tienda|eliminar-tienda', ['only' => ['index']]);
        $this->middleware('permission:crear-tienda', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-tienda', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-tienda', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tiendas = Tienda::all();
        return view('tienda.index', compact('tiendas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        return view('tienda.create');
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(StoreTiendaRequest $request)
{
    try {
        DB::beginTransaction();

        $imageBase64 = null; // Inicializamos vacío

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageBase64 = base64_encode(file_get_contents($image->path()));
        }

Tienda::create(array_merge(
    $request->validated(),
    [
        'EstatusContable' => 'A',
        'logo' => $imageBase64 ?? null,
        'departamento' => $request->departamento,
        'municipio' => $request->municipio,
        'representante' => $request->representante,
        'nit' => $request->nit,
    ]
));


        DB::commit();
        return redirect()->route('tienda.index')->with('success', 'Tienda registrado correctamente.');
    } catch (Exception $e) {
        DB::rollBack();
        return redirect()->route('tienda.create')->with('error', 'Hubo un error al registrar el tienda.');
    }
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
    public function edit(Tienda $tienda)
    {
        $tiendas = Tienda::all();
        return view('tienda.edit', compact('tienda', 'tienda'));
    }

        public function editfactura($idTienda)
    {
        $tienda = Tienda::findOrFail($idTienda); // Devuelve solo un registro o lanza 404
        $tienda = Tienda::all()->where('idTienda','=',$tienda->idTienda);

        $plantilla = plantillahtml::where('fkTienda', $idTienda)
                ->orderByDesc('id')
                ->first();
        $plantillas = plantillahtml::where('fkTienda', $idTienda)
                ->orderByDesc('id')
                ->get();

        $desings=DocumentDesings::get();

        $fkTienda=$idTienda;

        return view('tienda.editfactura', compact('plantillas','desings','tienda','plantilla','fkTienda'));
    }

    public function obtenerplantillas(){
        try{

            $plantillas=plantillahtmlgeneral::all();
            return response()->json($plantillas, 200);

        }catch(Exception $e){
                return response()->json(['error' => $e->getMessage()], 400);

        }
    }

    public function obtenerplantillaselect(Request $request)
{
    try {
        $plantilla = plantillahtmlgeneral::find($request->idplantilla);


        if (!$plantilla) {
            return response()->json(['error' => 'Plantilla no encontrada'], 404);
        }

        return response()->json([
            'cabecera' => $plantilla->cabecera,
            'detalle' => $plantilla->detalle,
            'pie' => $plantilla->pie,
            'consulta' => $plantilla->consulta,
            'Titulo' => $plantilla->Titulo,
            'descripcion'=> $plantilla->descripcion,
            'disdoc'=> $plantilla->fkDesignDocument,

        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
}
public function obtenerplantillaselectTienda(Request $request)
{
    try {
            $plantilla = plantillahtml::find($request->idplantilla);


        if (!$plantilla) {
            return response()->json(['error' => 'Plantilla no encontrada'], 404);
        }

        return response()->json([
            'cabecera' => $plantilla->cabecera,
            'detalle' => $plantilla->detalle,
            'pie' => $plantilla->pie,
            'consulta' => $plantilla->consulta,
            'Titulo' => $plantilla->Titulo,
            'descripcion'=> $plantilla->descripcion,
            'fkDesignDocument'=> $plantilla->fkDesignDocument,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
}

public function editfacturaplantilla(Request $request)
{
    $tienda = $request->only(['Titulo', 'cabecera', 'detalle', 'pie','descripcion','consulta','idTienda','fkDocumentDesing']);
    $tienda['fkTienda'] = $tienda['idTienda'];
    $tienda['plantillahtml'] = $tienda['cabecera'].$tienda['detalle'].$tienda['pie'];
    $tienda['fkDesignDocument'] = $request->input('fkDesignDocument');
    $tienda['chkeditar'] = $request->input('chkeditar');
    $tienda['disdoc'] = $request->input('disdoc');

    $fkTienda=$tienda['fkTienda'];
    if($request->chkcompartir==true){
        $tienda['fkDesignDocument'] = $request->input('fkDesignDocument');
      plantillahtmlgeneral::create($tienda);
    }

    if($request->chkeditar==true){
$plantilla = plantillahtml::where('id', $tienda['disdoc'])
    ->update([
        'Titulo'        => $tienda['Titulo'],
        'cabecera'      => $tienda['cabecera'],
        'detalle'       => $tienda['detalle'],
        'pie'           => $tienda['pie'],
        'descripcion'   => $tienda['descripcion'],
        'consulta'      => $tienda['consulta'],
        'fkDesignDocument' => $tienda['fkDesignDocument'],
    ]);

    $id=$tienda['fkTienda'];
    }else{
        $plantilla= plantillahtml::create($tienda);
        $id=$plantilla->fkTienda;

        $plantillaeliminar=plantillahtml::where('fkTienda',$id)->orderByDesc('id')->get();

        for($i=0; $i<$plantillaeliminar->count(); $i++){
            if($i>2){
                $plantillaeliminar[$i]->delete();
            }
        }

  }

    $tienda = Tienda::where('idTienda', $tienda['fkTienda'])->first();
    $desings=DocumentDesings::get();

   return redirect()->route('tienda.editfactura', ['tienda' => $tienda->idTienda])
    ->with('success', 'Se guarda plantilla existosamente');


    //return view('tienda.editfactura', compact('desings','tienda','plantilla','fkTienda'))->with('success', 'Se guarda plantilla existosamente.');
}

    public function update(Request $request, Tienda $tienda)
    {
        $request->validate([
            'Nombre' => 'max:150'
        ], [
            'Nombre.required' => 'El nombre del tienda es obligatorio.'
        ]);

        try {
            DB::beginTransaction();

            // Opción 1: Convertir imagen a Base64 desde el archivo subido
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageBase64 = base64_encode(file_get_contents($image->path()));
            }


            //Actualizar rol
            tienda::where('idTienda', $tienda->idTienda)
                ->update([
                    'Nombre' => $request->Nombre,
                    'Direccion' => $request->Direccion,
                    'descripcion' => $request->descripcion,
                    'Telefono' => $request->Telefono,
                    'logo' => $imageBase64 ?? null,
                ]);


            DB::commit();
        } catch (Exception $e) {
            dd($e);
            DB::rollBack();
        }

        return redirect()->route('tienda.index')->with('success', 'tienda editado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Tienda::where('idTienda', $id)->delete();

        return redirect()->route('tienda.index')->with('success', 'tienda eliminado');
    }

public function ejecutarConsultaConMetadata(Request $request)
{
    $sql = $request->input('plantilla');

    // Validar que solo se permitan SELECT por seguridad
    if (!preg_match('/^\s*select/i', $sql)) {
        return response()->json(['error' => 'Solo se permiten consultas SELECT'], 400);
    }

    try {
        $pdo = DB::getPdo();
        $stmt = $pdo->query($sql);

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $columnCount = $stmt->columnCount();
        $columns = [];

        for ($i = 0; $i < $columnCount; $i++) {
            $meta = $stmt->getColumnMeta($i);
            $columns[] = [
                'name' => $meta['name'] ?? null,
                'native_type' => $meta['native_type'] ?? null,
                'pdo_type' => $meta['pdo_type'] ?? null,
            ];
        }

        return response()->json([
            'columnas' => $columns,
            'filas' => $rows,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'Error al ejecutar la consulta',
            'detalle' => $e->getMessage(),
            'sql' => $sql
        ], 500);
    }
}

public function PDF(Request $request)
{
        $html = $request->input('html');


    try {
  // $html = $request->input('html');

    $pdf = PDF::loadHTML($html);
    $filename = 'pdf_' . time() . '.pdf';
    $path = storage_path("app/public/pdf/{$filename}");
    $pdf->save($path);

    return response()->json([
        'url' => asset("storage/pdf/{$filename}")
    ]);

    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'Error al ejecutar la consulta',
            'detalle' => $e->getMessage()
        ], 500);
    }
}

}
