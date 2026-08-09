<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tienda;
use App\Models\plantillahtml;
use App\Models\Centro;
use App\Http\Requests\StoreTiendaRequest;
use App\Models\DocumentDesings;
use App\Models\plantillahtmlgeneral;
use GuzzleHttp\Promise\Create;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\DatosestaticosSeeder;
use Dom\Document;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use function Laravel\Prompts\select;

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
    if(!Auth::check()){
        return redirect()->route('login');
    }

    $idusuario = auth()->user()->id;

    // 1. Buscamos los IDs de las tiendas a las que el usuario tiene acceso directo
    $tiendasDirectasIds = DB::table('usuario_tienda')
        ->where('fkUsuario', $idusuario)
        ->pluck('fkTienda');

    // 2. Ejecutar el UNION intermedio
    $rawIds = DB::table('centros_organizacion')
        ->whereIn('fkTiendaPrincipal', $tiendasDirectasIds)
        ->select('fkTiendaDependiente as tienda_id')
        ->union(
            DB::table('centros_organizacion')
                ->whereIn('fkTiendaDependiente', $tiendasDirectasIds)
                ->select('fkTiendaPrincipal as tienda_id')
        )
        ->union(
            DB::table('usuario_tienda')
                ->where('fkUsuario', $idusuario)
                ->select('fkTienda as tienda_id')
        )
        ->get()
        ->pluck('tienda_id')
        ->unique()
        ->toArray();

    // 🔥 LA SOLUCIÓN DEFINITIVA: Forzar la conversión estricta a números enteros puros (int)
    $tiendasRelacionadasIds = array_map('intval', $rawIds);

    // 3. Consulta final blindada con leftJoin y protección contra nulos
    $tiendas = Tienda::leftJoin('centro', 'tienda.fkCentro', '=', 'centro.id')
        ->select(
            'tienda.*',
            'centro.codigo as centro_codigo',
            'centro.nombre as centro_nombre',
            'tienda.idTienda as idTienda' // Forzar persistencia del ID base
        )
        ->whereIn('tienda.idTienda', $tiendasRelacionadasIds)
        ->distinct()
        ->get();

    return view('tienda.index', compact('tiendas'));
}

public function guardarFirmaTienda(Request $request)
{
    try {
        // 1. Validar que se reciba el ID correcto de la tienda y la firma
        $request->validate([
            'idTienda' => 'required|exists:tienda,idTienda',
            'firma_base64' => 'required'
        ]);

        // 2. Buscar el registro de la tienda por su clave primaria exacta
        $tienda = \Illuminate\Support\Facades\DB::table('tienda')
            ->where('idTienda', $request->input('idTienda'))
            ->first();

        if (!$tienda) {
            return response()->json(['status' => 'error', 'message' => 'La tienda especificada no existe.'], 404);
        }

        if ($request->has('firma_base64') && !empty($request->input('firma_base64'))) {
            $image_data = $request->input('firma_base64');
            
            // 3. Limpiar y sanitizar la cadena Base64 que viene del frontend
            $image_split = explode(',', $image_data);
            $firmaLimpia = isset($image_split[1]) ? trim($image_split[1]) : trim($image_data);

            // 4. Ejecutar la actualización en la tabla local mediante Query Builder
            \Illuminate\Support\Facades\DB::table('tienda')
                ->where('idTienda', $request->input('idTienda'))
                ->update([
                    'firma_representante' => $firmaLimpia,
                    'updated_at' => \Carbon\Carbon::now()
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Firma del representante de la tienda actualizada con éxito.'
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'No se recibieron datos de la firma.'], 400);

    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $fkTienda=session('user_fkTienda');
        $centros=Centro::join('tienda', 'centro.id', '=', 'tienda.fkCentro')
            ->where('tienda.idTienda', $fkTienda)
            ->select('centro.*')
            ->get();

            $Estatus = session('user_estatus');

if (blank($centros)) {
    if($Estatus=='ER'){
        $centros = Centro::all();
    }else{
        $centros = Centro::where('fkTienda', $fkTienda)->get();
    }
}


        return view('tienda.create',compact('centros'));
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(StoreTiendaRequest $request)
{
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    try {
        DB::beginTransaction();

        $rutaLogo = null; // Inicializamos vacío

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            
            // 1. Inicializa el manager con el driver de GD para optimizar
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());

            // 2. Lee, redimensiona y convierte el logotipo a WebP ligero
            $processedImage = $manager->read($file->getPathname())
                ->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })
                ->toWebp(60); // Compresión eficiente al 60%

            // 3. Definimos el nombre único y la ruta virtual del bucket
            $nombreArchivo = 'logo_' . time() . '.webp';
            $rutaLogo = 'documentos/' . $nombreArchivo;

            // 4. Subimos el archivo optimizado directamente a Google Cloud Storage usando streams
            Storage::disk('gcs_images')->put($rutaLogo, (string) $processedImage);
        }

        // 5. Registramos la Tienda guardando la RUTA de Google Cloud en el campo 'logo'
        Tienda::create(array_merge(
            $request->validated(),
            [
                'EstatusContable' => 'A',
                'logo' => $rutaLogo, // 👈 CORREGIDO: Guardamos 'documentos/logo_174000.webp' en vez de Base64
                'departamento' => $request->departamento,
                'Telefono' => $request->telefono,
                'Direccion' => $request->Direccion,
                'municipio' => $request->municipio,
                'descripcion' => $request->descripcion,
                'representante' => $request->representante,
                'fkCentro' => $request->centro,
                'nit' => $request->nit,
            ]
        ));

        DB::commit();
        return redirect()->route('tienda.index')->with('success', 'Tienda registrada correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        // Cambiado para que en desarrollo te informe el error real si algo falla
        return redirect()->route('tienda.create')->with('error', 'Hubo un error al registrar la tienda: ' . $e->getMessage());
    }
}


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
$fkTienda = $tienda->idTienda;

// 1. Definimos la subconsulta para obtener los IDs de los centros relacionados
$subqueryIds = DB::table('centros_organizacion')
    ->select('fkCentro')
    ->where('fkTiendaDependiente', $fkTienda)
    ->union(
        DB::table('centros_organizacion')
            ->select('fkCentro')
            ->where('fkTiendaPrincipal', $fkTienda)
    )
    ->union(
        DB::table('centro')
            ->select('id')
            ->where('fkTienda', $fkTienda)
    );

// 2. Consultamos el modelo Centro usando esos IDs
$centros = Centro::whereIn('id', $subqueryIds)
    ->with('tienda') // Carga la relación si la tienes definida
    ->get();


        return view('tienda.edit', compact('tienda', 'centros'));
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

                        if(!Auth::check()){
            return redirect()->route('login');
        }


            $plantillas=plantillahtmlgeneral::all();
            return response()->json($plantillas, 200);

        }catch(Exception $e){
                return response()->json(['error' => $e->getMessage()], 400);

        }
    }

    public function obtenerplantillaselect(Request $request)
{
    try {

                    if(!Auth::check()){
            return redirect()->route('login');
        }
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

                    if(!Auth::check()){
            return redirect()->route('login');
        }
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
            'plantillahtml'=> $plantilla->plantillahtml,
            'fkDesignDocument'=> $plantilla->fkDesignDocument,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
}

public function editfacturaplantilla(Request $request)
{
                    if(!Auth::check()){
            return redirect()->route('login');
        }

    $tienda = $request->only(['Titulo', 'cabecera', 'detalle', 'pie','consulta','idTienda','fkDocumentDesing']);
    $tienda['fkTienda'] = $tienda['idTienda'];
    $tienda['plantillahtml'] = $request->input('detallehijo') ?? '<!-- aca ingresar html -->';
    $tienda['descripcion'] = $request->input('detallenieto') ?? '<!-- aca ingresar html -->';
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
        'plantillahtml'      => $tienda['plantillahtml'],
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
        $limitess=count(DatosestaticosSeeder::getVistas());
        for($i=0; $i<$plantillaeliminar->count(); $i++){

            if($i>$limitess-1){
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
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $request->validate([
        'Nombre' => 'max:150',
        'firma_base64' => 'nullable'
    ], [
        'Nombre.max' => 'El nombre de la tienda es muy largo.'
    ]);

    try {
        DB::beginTransaction();

        // 1. Preparamos los datos básicos
        $data = [
            'Nombre'      => $request->Nombre,
            'Direccion'   => $request->Direccion,
            'descripcion' => $request->descripcion,
            'Telefono'    => $request->Telefono,
            'fkCentro'    => $request->centro,
        ];

        // Inicializamos el gestor de Intervention Image V3
        $manager = new ImageManager(new Driver());

        // 2. ⚡ REDUCIR Y COMPRIMIR EL LOGO (IMAGEN)
        if ($request->hasFile('image')) {
            $imageFile = $request->file('image');
            
            // Leemos la imagen subida, la redimensionamos manteniendo el aspecto
            $logoProcesado = $manager->read($imageFile->path())
                                     ->resize(800, 800, function ($constraint) {
                                         $constraint->aspectRatio();
                                         $constraint->upsize();
                                     });

            // La codificamos en formato WebP con calidad optimizada (70)
            $encodedLogo = $logoProcesado->encode(new WebpEncoder(quality: 70));

            // Guardamos el Base64 ultraligero resultante
            $data['logo'] = base64_encode($encodedLogo->toString());
        }

        // 3. ⚡ REDUCIR Y COMPRIMIR LA FIRMA DIGITAL
        if ($request->has('firma_base64') && !empty($request->input('firma_base64'))) {
            $firmaRaw = $request->input('firma_base64');
            
            // Limpiamos la cabecera del Canvas para obtener el binario puro
            if (str_contains($firmaRaw, ',')) {
                $parts = explode(',', $firmaRaw);
                $firmaBinaria = base64_decode($parts[1]); 
            } else {
                $firmaBinaria = base64_decode($firmaRaw);
            }

            // Leemos los datos binarios de la firma en memoria
            $firmaProcesada = $manager->read($firmaBinaria)
                                       ->resize(600, 600, function ($constraint) {
                                           $constraint->aspectRatio();
                                           $constraint->upsize();
                                       });

            // Codificamos la firma en WebP (ideal para trazos monocromáticos, pesa poquísimo)
            $encodedFirma = $firmaProcesada->encode(new WebpEncoder(quality: 60));

            // Guardamos el Base64 de la firma optimizada
            $data['firma_representante'] = base64_encode($encodedFirma->toString());
        }

        // 4. Ejecutamos la actualización con los Base64 optimizados
        $tienda->update($data);

        DB::commit();
        return redirect()->route('tienda.index')->with('success', 'Tienda editada correctamente');

    } catch (Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'Error al actualizar: ' . $e->getMessage()]);
    }
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

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
