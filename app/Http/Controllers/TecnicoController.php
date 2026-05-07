<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; // Asegúrate de que esta línea esté presente

use Illuminate\Http\Request;
use App\Http\Requests\UpdateTecnicoRequest;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Documento;
use App\Models\MovimientoMaterial;
use App\Models\Expedientetecnico;
use App\Models\Persona;
use App\Models\Pagotecnico;
use App\Models\Tienda;
use App\Models\Expedientefotograficotecnico;
use App\Models\MovimientoMateriales;
use App\Models\Producto;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Spatie\Permission\Models\Role;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use App\Models\Tecnico;
use App\Models\usuariotienda;
use Illuminate\Pagination\Paginator;
use PhpParser\Node\Expr\BinaryOp\Mod;
use Yajra\DataTables\DataTables;


class TecnicoController extends Controller
{
    public function __construct()
    {
        // Aplicar middleware de permisos
        $this->middleware('permission:ver-tecnico', ['only' => ['index']]);
        $this->middleware('permission:crear-tecnico', ['only' => ['create', 'store', 'exist']]);
        $this->middleware('permission:editar-tecnico', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-tecnico', ['only' => ['destroy']]);

    }
    public function boot(): void
{
    Paginator::useBootstrap();
}

    public function index()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

            $sql = "SELECT t.id,
			t.fkTienda,
            td.Nombre as Tienda,
            p.razon_social as tecnico,
            t.especialidad,
            t.codigo FROM
            tecnico as t inner join personas as p
				on p.id=t.fkpersona
            inner join tienda as td
				on td.idTienda=t.fkTienda ";

                if ($Estatus == 'ER') {

                    $sql .= "";

                } else {
                    $sql .= " where t.fkTienda= ".$fkTienda ;

                }
            $parametros=['id'=>''];
            $tecnicos=$this->obtenerdetalles($sql,$parametros);

        return view('tecnico.index', compact('tecnicos'));
    }

    public function show($id)
    {
        // Lógica para mostrar un cliente específico
        $cliente = Cliente::find($id);
        return redirect()->route('clientes.index')->with('success', 'Cliente registrado');
    }
    public function edit($id){
                try{
                                    if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');
        $rol = Role::all();
        $documentos=Documento::all();
        $tecnico=Tecnico::where('id',$id)
        ->first();

$tienda = DB::table('usuario_tienda as us')
    ->select('ti.Nombre', 'ti.idTienda')
    ->join('users as u', 'u.id', '=', 'us.fkUsuario')
    ->join('tecnico as t', 't.fkTienda', '=', 'us.fkTienda')
    ->join('tienda as ti', 'ti.idTienda', '=', 't.fkTienda')
    ->where('t.id', $id)
    ->distinct()
    ->get();

// Verificar si hay resultados
if ($tienda->isEmpty()) {
    // Manejar el caso cuando no hay tiendas
    $tienda = collect(); // Crear colección vacía
}


    $users=DB::table('users as u')
    ->join('usuario_tienda as ut', 'ut.fkUsuario','=','u.id','left')
    ->where('fkTienda',$fkTienda)
    ->get();


        return view('tecnico.edit', compact('tecnico','rol','documentos','users','tienda','id'));
                }catch(Exception $e){
                return response()->json(['error' => $e->getMessage()], 400);

        }
    }
    public function create()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');
        $rol = Role::all();
        $documentos=Documento::all();


    $users=DB::table('users as u')
    ->join('usuario_tienda as ut', 'ut.fkUsuario','=','u.id','left')
    ->where('fkTienda',$fkTienda)
    ->get();


        if ($Estatus == 'ER') {
                    $tecnico = Tienda::all();
                } else {
                    $tecnico = Tienda::where('idTienda',$fkTienda)->get();
                };



        return view('tecnico.create', compact('tecnico','rol','documentos','users'));
    }

    public function prepararimagen($request){
            $request->validate([
        'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);
                $file = $request->file('image');
        $manager = new ImageManager(new Driver()); // Fixed driver initialization

        $image = $manager->read($file->getPathname())
                         ->resize(800, 800, function ($constraint) {
                             $constraint->aspectRatio();
                             $constraint->upsize();
                         });

        // Convert to WebP
        $filename = 'tecnico_' . time() . '.webp';
        $path = 'tecnicos/' . $filename;
        $webpEncoder = new WebpEncoder(quality: 80);

        // Store image
        Storage::disk('public')->put($path, (string) $image->encode($webpEncoder));
    }

    public function store(Request $request)
    {


        try {

            DB::beginTransaction();
         // Procesar imagen y convertir a BLOB
        $file = $request->file('image');
        $manager = new ImageManager(new Driver());

        $image = $manager->read($file->getPathname())
                         ->resize(800, 800, function ($constraint) {
                             $constraint->aspectRatio();
                             $constraint->upsize();
                         });

        // Convertir a WebP y obtener como cadena binaria
        $webpEncoder = new WebpEncoder(quality: 80);
        $imageBlob = $image->encode($webpEncoder);

        // Crear persona
        $persona = Persona::create([
            'razon_social' => $request->razon_social,
            'direccion' => $request->direccion,
            'tipo_persona' => $request->tipo_persona,
            'estado' => 1,
            'documento_id' => $request->documento_id,
            'numero_documento' => $request->numero_documento,
            'created_at' => now()
        ]);


    //creacion de tecnico
            $persona->tecnico()->create([
                'fkpersona' => $persona->id,
                'nombre' => $persona->razon_social,
                'fkTienda' => $request->tienda,
                'codigo' => $request->numero_eta,
                'especialidad' => $request->especialidad,
                'logo' => $imageBlob
            ]);

            //Encriptar contraseña
            $fieldHash = Hash::make($request->password);
            //Modificar el valor de password en nuestro request
            $request->merge(['password' => $fieldHash]);

            //Crear usuario
            $user = User::create(array_merge([
                'fkTienda' => $request->tienda,
                'logo'=>$imageBase64??null,
                'name' => $request->razon_social,
                'email' => $request->email,
                'password'=> $request->password,
                'created_at'=>now()
                ]));

            //Asignar su rol
            $user->assignRole($request->role);

            usuariotienda::create(array_merge([
                'fkUsuario'=>$user->id,
                'fkTienda'=>$request->tienda,
                'Estatus'=>$request->Estatus,
                'FechaIngreso'=>now(),
                'created_at'=>now()
            ]));

            DB::commit();
            
            return redirect()->route('tecnico.lista')->with('success', 'Tecnico registrado');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el cliente.');
        }

        

    }

    public function exist(Request $request)
{
    if (!Auth::check()) {
        return response()->json(['error' => 'Sesión expirada.'], 401);
    }

    try {
        DB::beginTransaction();

        // 1. Procesar imagen (solo si existe)
    $file = $request->file('image');
    $manager = new ImageManager(new Driver());


// Luego manipulas la imagen
    $image = $manager->read($file->getPathname())
        ->resize(300, 300, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })
        ->toWebp(50); // calidad 50%

        $imageBase64 = (string) $image;

        $idpersona=Tecnico::where('id',$request->idtecnico)->value('fkpersona');
        // 2. Buscar la Persona (esta sí debe existir obligatoriamente)
        $persona = Persona::findOrFail($idpersona);

        $request->validate([
                'email.unique' => 'El correo electrónico ya existe en el sistema, por favor elige uno nuevo.'
            ]);

            $fieldHash = Hash::make($request->password);

        // 3. BUSCAR O CREAR el técnico vinculado a esa persona
        // Usamos updateOrCreate para que si no existe, lo inserte
        $tecnico = Tecnico::updateOrCreate(
            ['fkpersona' => $persona->id], // Condición para buscar
            [
                'nombre'       => $persona->razon_social, // Datos para actualizar/crear
                'fkTienda'     => $request->tienda,
                'codigo'       => $request->numero_eta,
                'especialidad' => $request->especialidad,
                'fkuser'=>$request->user,
                'updated_at'   => now()
            ]
        );

        // 4. Actualizar logo si se subió uno
        if ($imageBase64) {
            $tecnico->update(['logo' => $imageBase64]);
        }

        DB::commit();

        return redirect()->route('tecnico.lista')->with('success', 'Tecnico registrado');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error en Exist: ' . $e->getMessage());
        return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
    }
}



    public function obtenerdetalless(Request $request){

        try {
                            if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $param = $request->input('parametros');


            $materiales = MovimientoMaterial::join('treematerialescategoria as tmc', 'tmc.sku', '=', 'movimientomateriales.SKU')
            ->join('expedientetecnico as et', 'et.id', '=', 'movimientomateriales.fkExpediente')
            ->where('et.id', $param)
            ->select(
                'movimientomateriales.id',
                'movimientomateriales.serie',
                'tmc.nombre as Descripcion',
                'tmc.sku as sku',
                DB::raw('IFNULL(movimientomateriales.cantidad, 1) as cantidad')
            )
    ->get();
    return response()->json($materiales);
            } catch (Exception $e) {
            dd($e);
            DB::rollBack();
        }
    }

    public function validarMaterialesTecnicos(Request $request) {
    $materialesInput = $request->input('materiales', []);
    $procesados = [];
    $rastro = [];

    foreach ($materialesInput as $item) {
        // Convertimos a objeto para que sea compatible con tu lógica de ejecutarLogicaInterna
        $objItem = (object)[
            'SKU' => $item['sku'],
            'Cantidad' => $item['cantidad'],
            'CENTRO' => 'TEMP' // Opcional si no filtras por centro aquí
        ];
        
        $this->ejecutarLogicaInterna(0, $objItem, $procesados, $rastro);
    }

    return response()->json(['validaciones' => array_values($procesados)]);
}


    public function inventariotecnicoorden($tecbucket)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $orden = Expedientetecnico::where('id', $tecbucket)
            ->where(function($query) {
                $query->where('Estatus', 'I')
                    ->orWhere('Estatus', 'S');
            })
            ->first();


        $tecnico = Tecnico::where('id',$orden->fkTecnico)->first();


        return view('buckettecnico.edit', compact('tecbucket', 'orden','tecnico'));
    }

            public function fillEstructura()
    {
try {
        $fkTienda = session('user_fkTienda');
        $pdo = DB::getPdo();
        $sqlll='
        SELECT DISTINCT am.nombre, am.id, am.SKU FROM arbolmaterial as amo
        inner join (select ams.id, ams.SKU, ams.nombre from arbolmaterial as ams where isnull(ams.padre_id)) AS am on am.id=amo.padre_id
        where fkTienda=:id
        ';
        $stmt = $pdo->prepare($sqlll);

        $stmt->execute(['id' => $fkTienda]);


        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);


    return response()->json($detallecomprobante);

            } catch (Exception $e) {
            dd($e);
            DB::rollBack();
        }

    }
    public function fetch2(Request $request)
    {
        $id = $request->input('id');
        // Inicializamos el ID de la categoría padre como NULL para empezar desde el nodo raíz.
        $data = $this->get_node_data($id);

        // Codificamos los datos en formato JSON para enviarlos al frontend.
        echo json_encode(array_values($data));
    }

    function get_node_data($parent_category_id)
    {
        // Obtenemos las arbolmateriales contables que tienen como padre el ID dado
        $result = DB::table('arbolmaterial')
            ->where('padre_id', $parent_category_id) // Buscamos por padre_id
            ->where('fkTienda',session('user_fkTienda'))
            ->get();

        $output = []; // Inicializamos el arreglo de salida

        // Iteramos sobre los resultados y construimos el árbol de nodos
        foreach ($result as $row) {
            $sub_array = [];
            $sub_array['nodeId'] = $row->id; // Usamos nodeId para cada nodo
            $sub_array['Cid'] = $row->id; // Usamos nodeId para cada nodo
            $sub_array['padre_id'] = $row->padre_id; // Usamos nodeId para cada nodo
            $sub_array['cuenta_id'] = $row->SKU; // Usamos nodeId para cada nodo
            $sub_array['text'] = $row->SKU."-".$row->nombre; // Mostrar el nombre de la cuenta
            $sub_array['nombre'] = $row->nombre; // Mostrar el nombre de la cuenta
            $sub_array['aplicafotografia'] = $row->aplicafotografia; // Mostrar el nombre de la cuenta
            $sub_array['Tipo_servicio'] = $row->Tipo_servicio; // Mostrar el nombre de la cuenta
            $sub_array['nodes'] = $this->get_node_data($row->id); // Recursión para obtener los hijos
            $sub_array['idpivote'] = $row->idpivote; // Recursión para obtener los hijos
            $output[] = $sub_array; // Agregar al array de salida
        }

        return $output;
    }

    public function fillEstructuraMO($id)
    {
    try {
        $fkTienda = session('user_fkTienda');
        $pdo = DB::getPdo();
        $sqlll='
        SELECT DISTINCT am.nombre, am.id, am.SKU FROM arbolmaterial as am where am.padre_id=:id
        ';
        $stmt = $pdo->prepare($sqlll);

        $stmt->execute(['id' => $id]);


        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);


    return response()->json($detallecomprobante);

            } catch (Exception $e) {
            dd($e);
            DB::rollBack();
        }

    }

  public function InventarioLista(request $request)
{
    try {
        $fkTienda = session('user_fkTienda');
        $pdo = DB::getPdo();
        
        $sqlll = "
        WITH RECURSIVE nodo_padre AS (
            SELECT id, padre_id, nombre, sku, aplicafotografia as apf, Tipo_servicio as TP
            FROM arbolmanoobra
            WHERE id = ? and fkTienda= ?
            UNION ALL
            SELECT a.id, a.padre_id, a.nombre, a.sku, aplicafotografia as apf, Tipo_servicio as TP
            FROM arbolmanoobra a
            INNER JOIN nodo_padre np ON a.padre_id = np.id
            WHERE a.fkTienda= ?
        ),
        cte_raiz AS ( SELECT * FROM nodo_padre )
        SELECT DISTINCT
            ams.nombre, ams.sku, ams.limite, ams.minimo, ams.fkTienda, ams.padre_id, 
            r.apf, r.TP, am.nombre as categoria_nombre
        FROM cte_raiz as r
        JOIN treematerialescategoria AS am ON am.nombre COLLATE utf8mb4_unicode_ci = r.nombre
        JOIN treematerialescategoria AS ams ON ams.padre_id = am.id";

        $stmt = $pdo->prepare($sqlll);
        $stmt->execute([$request->id1, $fkTienda, $fkTienda]);
        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Detectar si hay algún registro de tipo MO
        $contieneMO = collect($detallecomprobante)->contains('TP', 'MO');

        if ($contieneMO) {
            // Caso MO: Construimos el array manualmente
            $final = [];
            foreach ($detallecomprobante as $value) {
                $final[] = [
                    'id'               => 0,
                    'serie'            => '',
                    'categoria_nombre' => $value['nombre'], // 'nombre' de ams
                    'sku'              => $value['sku'],
                    'cantidad'         => $value['limite']
                ];
            }
        } else {
            // Caso Materiales: Buscamos en MovimientoMaterial
            $skus = collect($detallecomprobante)->pluck('sku')->toArray();
            
            $final = MovimientoMaterial::join('treematerialescategoria as tmc', 'tmc.sku', '=', 'movimientomateriales.SKU')
                ->where('movimientomateriales.fkTienda', $fkTienda)
                ->where('fkTecnico', $request->id2)
                ->whereIn('movimientomateriales.SKU', $skus)
                ->where('movimientomateriales.STATUS', 'A')
                ->select(
                    'movimientomateriales.id',
                    'movimientomateriales.serie',
                    'tmc.nombre as categoria_nombre',
                    'tmc.sku as sku',
                    DB::raw('IFNULL(movimientomateriales.cantidad, 1) as cantidad')
                )
                ->get();
        }

        // Si es colección de Laravel usamos toArray(), si es array lo dejamos tal cual
        return response()->json(is_array($final) ? $final : $final->toArray());

    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


    public function update(UpdateTecnicoRequest $request, Tecnico $tecnico)
    {
        try {
                            if(!Auth::check()){
            return redirect()->route('login');
        }

            DB::beginTransaction();
            $tecnico->load('persona');

            $id=$tecnico->fkpersona;
            Persona::where('id', $id)
                ->update([
                   'razon_social'=>$request->name
                ]);

            Tecnico::where('id',$tecnico->id)
            ->update(array_merge($request->validated(),['nombre'=>$request->name]));


            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }

        return redirect()->route('tecnico.lista')->with('success', 'Tecnico editado');
    }

        public function operartrabajo(Request $request, Tecnico $tecnico, Expedientetecnico $expediente)
{
    try {
        if (!Auth::check()) return redirect()->route('login');

        DB::beginTransaction();

        $iditems = $request->input('arrayiditem', []);
        
        foreach ($iditems as $contar => $iditem) {
            
            // 1. Si el item es 0, es Mano de Obra nueva. Si no, es un material de inventario.
            if ($iditem == 0) {
// 1. CRITERIOS DE BÚSQUEDA: Solo lo mínimo para identificar el registro
// Si ya existe un registro con este SKU para este Expediente y Técnico, que lo ACTUALICE.
$nuevoMovimiento = MovimientoMaterial::updateOrCreate(
    // 1. Criterios de búsqueda
    [
        'fkExpediente' => $expediente->id,
        'fkTecnico'    => $expediente->fkTecnico,
        'SKU'          => $request->input('arraysku')[$contar] ?? '-',
        'TIPO'         => 'MO',
    ],
    // 2. Datos a actualizar o insertar
    [
        'fkTienda'       => $expediente->fkTienda,
        'serie'          => ($request->input('arrayserie')[$contar] ?? null) ?: '-',
        'cantidad'       => $request->input('arraycantidad')[$contar] ?? 1,
        'CENTRO'         => 'CF',
        'ESTATUS'        => 'INSTALADO',
        'almacen'        => 'ALMA',
        'TIPOMOVIMIENTO' => 'INSTALADO',
        'Naturaleza'     => 'H',
        'Status'         => 'S',
        'Lote'           => 'A000',
        'MAC1'           => '-',
        'MAC2'           => '-',
        'MAC3'           => '-',
        'COSTO'          => 0,
        'unidadmedida'   => 'UNIDAD',
        'Modificado_el'  => now(),
        'Modificado_por' => Auth::user()->name,
    ]
);

// 3. SOLO SI ES UN REGISTRO NUEVO, asignamos los campos de creación
if ($nuevoMovimiento->wasRecentlyCreated) {
    $nuevoMovimiento->update([
        'Creado_el'  => now(),
        'Creado_por' => Auth::user()->name,
    ]);
}

$iditem = $nuevoMovimiento->id;
            } else {
                // Actualizar estatus del item de inventario existente
                MovimientoMaterial::where('id', $iditem)->update([
                    'Estatus' => 'INSTALADO',
                    'Status' => 'S',
                    'updated_at' => now(),
                ]);
            }

            // 2. Obtener los datos del movimiento (sea el nuevo o el existente)
            $mat = MovimientoMaterial::find($iditem);

            // 3. Lógica de Inventario (Solo para materiales, no para Mano de Obra)
            if ($mat->TIPO !== 'MO') {
                $nuevaCantidad = $mat->cantidad - ($request->input('arraycantidad')[$contar] ?? 0);
                
                if ($mat->serie == '-') {
                    $mat->update(['cantidad' => max(0, $nuevaCantidad), 'Estatus' => 'INSTALADO']);
                } else {
                    $mat->update(['Status' => 'S', 'Estatus' => 'INSTALADO']);
                }
            }

// 1. Usar updateOrCreate (modelo) para obtener un objeto, no un booleano
$nuevoMovimientoPago = Pagotecnico::updateOrCreate([
    'Orden'       => $expediente->Orden,
    'SKU'         => $request->input('arraysku')[$contar],
    'fkTienda'    => $expediente->fkTienda,
    'fkTecnico'   => $expediente->fkTecnico,
], [
    'Descripcion' => $request->input('arraynameProducto')[$contar],
    'OBS'         => 'Pago por servicio tecnico',
    'Cantidad'    => $request->input('arraycantidad')[$contar],
    'COSTOPAGO'   => ($request->input('arraycantidad')[$contar] ?? 1) * ($mat->COSTO ?? 0),
    'Naturaleza'  => 'D',
    'Status'      => 'S',
    // updated_at se gestiona solo si el modelo tiene timestamps
]);

// 2. Ahora sí puedes usar wasRecentlyCreated
if ($nuevoMovimientoPago->wasRecentlyCreated) {
    // Nota: Verifica si es 'created_at' (estándar) o 'create_at' como pusiste
    $nuevoMovimientoPago->update([
        'created_at'  => now() 
    ]);
}

            // 5. Procesamiento de Fotos (Mantenemos tu lógica de base64)
            $itemInput = $request->input('items', [])[$contar] ?? [];
            $photos = $itemInput['photos'] ?? [];
            $names = $itemInput['names'] ?? [];

            foreach ($photos as $i => $photoBase64) {
                // 1. Separar metadata del base64
                @list($type, $fileData) = explode(';', $photoBase64);
                @list(, $fileData) = explode(',', $fileData);

                if ($fileData) {
                    $fileData = base64_decode($fileData);

                    // 2. Obtener extensión
                    $extension = str_contains($type, 'png') ? 'png' : 'jpg';

                    // 3. Definir nombre único (usando el nombre del producto para identificar qué se instaló)
                    $nombreLimpio = preg_replace('/[^A-Za-z0-9_\-]/', '_', $names[$i] ?? 'foto');
                    $productoNombre = preg_replace('/[^A-Za-z0-9_\-]/', '_', $request->input('arraynameProducto')[$contar] ?? 'item');
                    
                    $fileName = "{$nombreLimpio}_{$productoNombre}_" . uniqid() . ".{$extension}";

                    // 4. Definir ruta y guardar en storage
                    // La ruta física: storage/app/public/fotos/ordenes/{orden}
                    $directory = "public/fotos/ordenes/{$expediente->Orden}";
                    $path = "{$directory}/{$fileName}";

                    if (!Storage::exists($directory)) {
                        Storage::makeDirectory($directory);
                    }

                    Storage::put($path, $fileData);

                    // 5. Crear el registro en la base de datos
                    Expedientefotograficotecnico::create([
                        'fkTienda'   => $expediente->fkTienda,
                        'Orden'      => $expediente->Orden,
                        // Guardamos la ruta que será accesible desde el navegador (usando el symlink)
                        'fotografia' => "storage/fotos/ordenes/{$expediente->Orden}/{$fileName}",
                    ]);
                }
            }

        }

        if($expediente->OBS==''){
        $expediente->update([
            'Status' => 'S',
            'FECHAINSTALACION' => now(),
        ]);
        }else{
        // 6. Finalizar Expediente
        $expediente->update([
            'Status' => 'S',
            'FECHAINSTALACION' => now(),
            'OBS' => $expediente->OBS . ' ||OBS TECNICO: ' . $request->input('obs'),
        ]);
        }



        DB::commit();
return redirect()->route('tecnico.buckettecnico')->with('success', 'Orden actualizada');
        

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

        public function bucket($id)
    {
        try {
                            if(!Auth::check()){
            return redirect()->route('login');
        }

            DB::beginTransaction();

            $fkTienda = session('user_fkTienda');
            $tecnicos=Tecnico::where('id',$id)->get();
            $expediente=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$id)->get();

            DB::commit();

            return view('buckettecnico.index', compact('tecnicos','expediente'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }


    }


    public function fetchrelacion(Request $request)
{
    try{
                        if(!Auth::check()){
            return redirect()->route('login');
        }

                    $Estatus = session('user_estatus');
                    $fkTienda = session('user_fkTienda');
                    $idtecnico= $request->input('id');
                    $fechain=$request->input('fechain');
                    $fechafin=$request->input('fechafin');

                    if(isset($fechain) or isset($fechafin)){
                if ($Estatus == 'ER') {

            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$request->input('id'))
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->where('ESTATUS','I')
            ->paginate(10);

                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->where('ESTATUS','I')
            ->paginate(10);
                };
                    }else{
                if ($Estatus == 'ER') {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$request->input('id'))->paginate(10);
                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)->paginate(10);
                };
                    }





    if ($request->ajax()) {
        return view('buckettecnico.table.tabla', compact('relacion'))->render();
    }
    }catch(Exception $e){
    return view('tecnico.index', compact('relacion','Error: '.$e->getMessage()));
    }


}

    public function fetchrelacionTecnico(Request $request)
{
    try{
                        if(!Auth::check()){
            return redirect()->route('login');
        }

                    $Estatus = session('user_estatus');
                    $fkTienda = session('user_fkTienda');
                     $idtecnico = Tecnico::where('fkuser', Auth()->id())->value('id');
                    $fechain=$request->input('fechain');
                    $fechafin=$request->input('fechafin');

                    if(isset($fechain) or isset($fechafin)){
                if ($Estatus == 'ER') {

            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->where('ESTATUS','I')
            ->paginate(10);

                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->where('ESTATUS','I')
            ->paginate(10);
                };
                    }else{
                if ($Estatus == 'ER') {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$request->input('id'))->paginate(10);
                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)->paginate(10);
                };
                    }





    if ($request->ajax()) {
        return view('buckettecnico.table.tabla', compact('relacion'))->render();
    }
    }catch(Exception $e){
    return view('tecnico.index', compact('relacion','Error: '.$e->getMessage()));
    }


}

public function fetchrelacionS(Request $request)
{
    try{
                        if(!Auth::check()){
            return redirect()->route('login');
        }

                    $Estatus = session('user_estatus');
                    $fkTienda = session('user_fkTienda');
                    $idtecnico= $request->input('id');
                    $fechain=$request->input('fechainS');
                    $fechafin=$request->input('fechafinS');

                    if(isset($fechain) or isset($fechafin)){
                if ($Estatus == 'ER') {

            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)
            ->where('Status','S')
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->paginate(25);

                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)
            ->where('Status','S')
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->paginate(25);
                };
                    }else{
                if ($Estatus == 'ER') {

            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)
            ->where('Status','S')
            ->where('fkTecnico',$idtecnico)->paginate(25);

                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)
            ->where('Status','S')
            ->where('fkTecnico',$idtecnico)->paginate(25);
                };
                    }





    if ($request->ajax()) {
        return view('buckettecnico.table.tablaexpediente', compact('relacion'))->render();
    }
    }catch(Exception $e){
    return view('tecnico.index', compact('relacion','Error: '.$e->getMessage()));
    }


}

public function fetchrelacionP(Request $request)
{
    try {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $Estatus   = session('user_estatus');
        $fkTienda  = session('user_fkTienda');
        $idtecnico = $request->input('id');
        $fechain   = $request->input('fechainP');
        $fechafin  = $request->input('fechafinP');

$relacion = Pagotecnico::with(['arbolmanoobra' => function($query) {
        $query->select('SKU', 'nombre as descripcion');
    }])
    ->where('fkTecnico', $idtecnico)
    ->where('Status', 'C')
    ->whereNotNull('fkTecnico')
    ->whereHas('arbolmanoobra', function($query) {
        $query->where('Tipo_servicio', 'MO');
    });



        if ($Estatus !== 'ER') {
            $relacion->where('fkTienda', $fkTienda);
        }

        if ($fechain && $fechafin) {
            $relacion->whereBetween('created_at', [$fechain, $fechafin]);
        }

        $relacion = $relacion->paginate(10);

        return view('buckettecnico.table.tablapago', compact('relacion'))->render();

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function fetchrelacionC(Request $request)
{
    try {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $Estatus   = session('user_estatus');
        $fkTienda  = session('user_fkTienda');
        $idtecnico = $request->input('id');
        $fechain   = $request->input('fechainP');
        $fechafin  = $request->input('fechafinP');

        $query = pagotecnico::where('fkTecnico', $idtecnico)
            ->where('Status', 'C')
            ->whereNotNull('fkTecnico');

        if ($Estatus !== 'ER') {
            $query->where('fkTienda', $fkTienda);
        }

        if ($fechain && $fechafin) {
            $query->whereBetween('created_at', [$fechain, $fechafin]);
        }

        $relacion = $query->paginate(10);

        return view('buckettecnico.table.tablacobro', compact('relacion'))->render();

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}




    public function fetchrelacioninv(Request $request)
{
    try{
                        if(!Auth::check()){
            return redirect()->route('login');
        }

                    $Estatus = session('user_estatus');
                    $fkTienda = session('user_fkTienda');
                    $idtecnico= $request->input('id');


   $relacion = MovimientoMaterial::with(['treematerialcategoria' => function($query) {
                // Solo traer columnas necesarias
                $query->select('SKU', 'nombre as descripcion');
            }])
            ->where('fkTienda', $fkTienda)
            ->where('ESTATUS', 'DISPONIBLE')
            ->where('fkTecnico', $idtecnico)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('count', 15));


    if ($request->ajax()) {
        return view('buckettecnico.table.tablainv', compact('relacion'))->render();
    }
    }catch(Exception $e){
    return view('tecnico.index', compact('relacion','Error: '.$e->getMessage()));
    }


}

public function exportar(Request $request)
{

                if(!Auth::check()){
            return redirect()->route('login');
        }


        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

                $request->validate([
                    'fechaincio' => 'required|date',
                    'fechafin' => 'required|date|after_or_equal:fechaincio',
                    ]);

                $inicio = Carbon::parse($request->fechaincio)->startOfDay();
                $fin = Carbon::parse($request->fechafin)->endOfDay();

                  if ($Estatus == 'ER') {

                $datos = Expedientetecnico::whereBetween('FECHAINSTALACION', [$inicio, $fin])
                ->get();

                } else {
                $datos = Expedientetecnico::where('fkTienda', $fkTienda)
                ->whereBetween('FECHAINSTALACION', [$inicio, $fin])
                ->get();
                }



    // Encabezado del CSV
    $csv = "fkTienda,Orden,virtual,Status,Tipo_servicio,Tipo_orden,NOMBRECLIENTE,DIRECCION,OBS,SIGLASCENTRAL,AREA,FECHAINSTALACION,created_at,updated_at,fkTecnico,AUTORIZA,ESTATUS,TECNOLOGIA\n";

    // Agregar datos
    foreach ($datos as $item) {

        $csv .= implode(",", [
            $item->fkTienda,
            $item->Orden,
            $item->virtual,
            $item->Status,
            $item->Tipo_servicio,
            $item->Tipo_orden,
            $item->NOMBRECLIENTE,
            $item->DIRECCION,
            $item->OBS,
            $item->SIGLASCENTRAL,
            $item->AREA,
            $item->FECHAINSTALACION,
            $item->created_at,
            $item->updated_at,
            $item->fkTecnico,
            $item->Autoriza,
            $item->ESTATUS,
            $item->TECNOLOGIA
        ]) . "\n";
    }

    // Retornar respuesta para descarga
    $nombreArchivo = 'tecnicosordenes_export_' . now()->format('Ymd_His') . '.csv';

    return Response::make($csv, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$nombreArchivo\"",
    ]);
}

    public function bucketlista()
    {
        try {
            DB::beginTransaction();

                    $Estatus = session('user_estatus');
                    $fkTienda = session('user_fkTienda');

            $idtecnico = Tecnico::where('fkuser', Auth()->id())
            ->value('id');
            $tecnicos = Tecnico::where('fkuser', Auth()->id())
            ->get();


                if ($Estatus == 'ER') {
                    $tecnicos=Tecnico::where('fkTienda',$fkTienda)->get();
                    $expediente=Expedientetecnico::where('fkTienda',$fkTienda)
                    ->where('ESTATUS','A')
                    ->get();
                    $tecnico=null;
                } else {
                    $tecnico=null;
                    $tecnico=Tecnico::where('fkTienda',$fkTienda)
                    ->where('id',$idtecnico)->first();
                    $expediente=Expedientetecnico::where('fkTienda',$fkTienda)
                    ->where('ESTATUS','A')
                    ->where('fkTecnico',$idtecnico)->get();
                };

            DB::commit();

            return view('buckettecnico.index', compact('tecnicos','tecnico','expediente','Estatus'));

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }


    }
public function importarMAMO(Request $request)
{
    if (!Auth::check()) return redirect()->route('login');

    $fkTienda = session('user_fkTienda');
    $idDestino = $request->input('id'); // Técnico que recibe las órdenes
    $nombreUsuario = session('nombreUsuario');

    $request->validate([
        'archivo' => 'required|file|mimes:csv,txt',
    ]);

    $file = fopen($request->file('archivo')->getRealPath(), 'r');
    $encabezado = fgetcsv($file); 

    DB::beginTransaction();
    try {
        $fila = 1;
        while (($linea = fgetcsv($file)) !== false) {
            $fila++;
            $data = array_combine($encabezado, $linea);

            // 1. VALIDACIÓN DE CAMPOS CRÍTICOS
            if (empty($data['Orden']) || empty($data['virtual'])) continue;

            $orden = trim($data['Orden']);
            $virtual = trim($data['virtual']);
            $ahora = now();

            // 2. TRATAMIENTO DE FECHA
            $fechaInst = null;
            if (!empty($data['FECHAINSTALACION'])) {
                try {
                    $fechaInst = Carbon::createFromFormat('d/m/Y', $data['FECHAINSTALACION'])->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $fechaInst = $ahora; 
                }
            }

            // 3. LOGICA DE REASIGNACIÓN (Trazabilidad)
            // Buscamos si la orden ya existe y está activa con otro técnico
            $expedientePrevio = DB::table('expedientetecnico')
                ->where('orden', $orden)
                ->where('virtual', $virtual)
                ->where('fkTienda', $fkTienda)
                ->where('Estatus', '!=', 'RE') // Evitamos los ya procesados
                ->first();

            if ($expedientePrevio) {
                // Si el técnico es el mismo, solo actualizamos datos y saltamos
                if ($expedientePrevio->fkTecnico == $idDestino) {
                    DB::table('expedientetecnico')->where('id', $expedientePrevio->id)->update([
                        'status' => $data['Status'] ?? $expedientePrevio->status,
                        'updated_at' => $ahora
                    ]);
                    continue;
                }

                // Si es un técnico diferente, "cerramos" el expediente anterior
                DB::table('expedientetecnico')
                    ->where('id', $expedientePrevio->id)
                    ->update([
                        'Estatus' => 'RE',
                        'obs' => ($expedientePrevio->OBS . " | Reasignada a técnico ID: $idDestino por $nombreUsuario"),
                        'updated_at' => $ahora
                    ]);
            }

            // 4. INSERTAR LA ORDEN PARA EL NUEVO TÉCNICO
            // Usamos insert para mantener el historial de quién ha tenido la orden
            DB::table('expedientetecnico')->insert([
                'orden'            => $orden,
                'virtual'          => $virtual,
                'fkTienda'         => $fkTienda,
                'fkTecnico'        => $idDestino,
                'status'           => $data['Status'] ?? 'PENDIENTE',
                'tipo_servicio'    => mb_convert_encoding($data['Tipo_servicio'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'tipo_orden'       => mb_convert_encoding($data['Tipo_orden'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'nombrecliente'    => mb_convert_encoding($data['NOMBRECLIENTE'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'direccion'        => mb_convert_encoding($data['DIRECCION'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'obs'              => mb_convert_encoding($data['OBS'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'SIGLASCENTRAL'    => $data['SIGLASCENTRAL'] ?? '',
                'area'             => $data['AREA'] ?? '',
                'FECHAINSTALACION' => $fechaInst,
                'autoriza'         => $data['AUTORIZA'] ?? '',
                'Estatus'          => $data['ESTATUS'] ?? 'AC',
                'TECNOLOGIA'       => $data['TECNOLOGIA'] ?? '',
                'created_at'       => $ahora,
                'updated_at'       => $ahora,
            ]);
        }

        fclose($file);
        DB::commit();
        return back()->with('success', 'Expedientes técnicos procesados y reasignados correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        if (isset($file)) fclose($file);
        Log::error('Error al importar Expediente: ' . $e->getMessage());
        return back()->with('error', 'Error en fila ' . $fila . ': ' . $e->getMessage());
    }
}


public function importarInvTecnico(Request $request)
{
    if (!Auth::check()) return redirect()->route('login');

    $fkTienda = session('user_fkTienda');
    $idDestino = $request->input('id'); 
    $nombreUsuario = session('nombreUsuario');
    $CentroDestino=Tecnico::where('id', $idDestino)->value('codigo') ?? 'N/A';
    
    $request->validate(['archivoinv' => 'required|file|mimes:csv,txt']);
    $file = fopen($request->file('archivoinv')->getRealPath(), 'r');
    $encabezado = fgetcsv($file); 

    DB::beginTransaction();
    try {
        $fila = 1;
        $instaladosContador = 0;

        while (($linea = fgetcsv($file)) !== false) {
            $fila++;
            $data = array_combine($encabezado, $linea);
            if (empty($data['SKU']) || empty($data['cantidad'])) continue;

            $sku = trim($data['SKU']);
            $serie = trim($data['serie'] ?? '');
            $cantidad = floatval($data['cantidad']);
            $docRef = 'IMP-' . now()->format('dmY:H:i:s') . '-' . $serie;
            $ahora = now();

            // 1. OBTENER O CREAR PRODUCTO
            $producto = Producto::firstOrCreate(
                ['codigo' => $sku],
                [
                    'nombre' => mb_convert_encoding($data['descripcion'] ?? "Producto $sku", 'UTF-8', 'ISO-8859-1'),
                    'fkTienda' => $fkTienda, 'estado' => 1, 'marca_id' => 1, 'presentacione_id' => 1,
                    'stock' => 0, 'precio_base' => 0, 'stock_minimo' => 1, 'perecedero' => 0
                ]
            );

            // 2. IMPEDIR TRASPASO SI ESTÁ INSTALADO
            $stockActual = DB::table('movimientomateriales')
                ->where('serie', $serie)
                ->where('SKU', $sku)
                ->where('fkTienda', $fkTienda)
                ->where('Status', 'A')
                ->first();

            if ($stockActual && $stockActual->ESTATUS == 'INSTALADO') {
                $instaladosContador++;
                continue; 
            }

            // 3. BUSCAR ÚLTIMO DUEÑO (Historial)
            $ultimoMov = MovimientoMateriales::where('fkMateriales', $producto->id)
                ->where('referencia', 'LIKE', "%$serie%")
                ->where('fkTienda', $fkTienda)
                ->orderBy('id', 'desc')->first();

            $idOrigen = $ultimoMov ? $ultimoMov->contrata : null;
            $CentroOrigen=Tecnico::where('id', $idOrigen)->value('codigo') ?? 'N/A';
            if ($idOrigen == $idDestino) continue;

            // 4. REGISTRAR SALIDA DEL ANTERIOR
            if ($idOrigen) {
                MovimientoMateriales::create([
                    'fkTienda' => $fkTienda, 'fkMateriales' => $producto->id, 'contrata' => $idOrigen,
                    'clase_movimiento' => '251', 'cantidad' => $cantidad * -1,
                    'referencia' => "SALIDA SERIE: $serie | TRASPASO A $idDestino",
                    'tipo_movimiento' => 'TRASPASO_SALIDA', 'documento_material' => $docRef,
                    'posicion_documento' => '0001', 'fecha_contabilizacion' => $ahora->format('Y-m-d'),
                    'almacen' => $CentroOrigen, 'centro' => $data['CENTRO'] ?? 'G817',
                    'unidad_medida_base' => $data['unidadmedida'] ?? 'PZA'
                ]);

                DB::table('movimientomateriales')
                    ->where('serie', $serie)
                    ->where('SKU', $sku)
                    ->where('fkTecnico', $idOrigen)
                    ->update([
                        'ESTATUS' => 'TRASLADADO',
                        'Status' => 'I',
                        'updated_at' => $ahora
                    ]);
            }

            // 5. REGISTRAR ENTRADA EN HISTORIAL (Destino)
            MovimientoMateriales::create([
                'fkTienda' => $fkTienda, 'fkMateriales' => $producto->id, 'contrata' => $idDestino,
                'clase_movimiento' => $idOrigen ? '252' : '101', 'cantidad' => $cantidad,
                'referencia' => "ENTRADA SERIE: $serie | ORIGEN: " . ($idOrigen ?? 'BODEGA'),
                'tipo_movimiento' => 'TRASPASO_ENTRADA', 'documento_material' => $docRef,
                'posicion_documento' => '0001', 'fecha_contabilizacion' => $ahora->format('Y-m-d'),
                'centro' => $data['CENTRO'] ?? 'G817', 'almacen' => $CentroDestino,
                'unidad_medida_base' => $data['unidadmedida'] ?? 'PZA'
            ]);

            // 6. ASIGNAR STOCK AL NUEVO TÉCNICO (Blindado contra Error 1364)
            DB::table('movimientomateriales')->updateOrInsert(
                [
                    'serie' => $serie,
                    'SKU' => $sku,
                    'fkTecnico' => $idDestino,
                    'fkTienda' => $fkTienda,
                ],
                [
                    'almacen' => $data['almacen'] ?? 'A000',
                    'Lote' => $data['Lote'] ?? 'N/A',
                    'MAC1' => $data['MAC1'] ?? '', // <-- Evita error si el CSV no lo trae
                    'MAC2' => $data['MAC2'] ?? '',
                    'MAC3' => $data['MAC3'] ?? '',
                    'COSTO' => $data['COSTO'] ?? 0,
                    'TIPO' => $data['TIPO'] ?? 'MAT',
                    'ESTATUS' => 'DISPONIBLE',
                    'Status' => 'A',
                    'Naturaleza'=> 'E',
                    'CENTRO' => $data['CENTRO'] ?? 'G817',
                    'cantidad' => $cantidad,
                    'unidadmedida' => $data['unidadmedida'] ?? 'PZA',
                    'TIPOMOVIMIENTO' => 'TRASPASO_ENTRADA',
                    'Modificado_el' => $ahora->format('Y-m-d'),
                    'Modificado_por' => $nombreUsuario,
                    'Creado_el' => $ahora->format('Y-m-d'),
                    'Creado_por' => $nombreUsuario,
                    'updated_at' => $ahora
                ]
            );
        }

        fclose($file);
        DB::commit();
        
        $msg = "Inventario procesado.";
        if($instaladosContador > 0) $msg .= " Se omitieron $instaladosContador series ya instaladas.";
        
        return back()->with('success', $msg);

    } catch (\Exception $e) {
        DB::rollBack();
        if (isset($file)) fclose($file);
        return back()->with('error', 'Error en fila ' . $fila . ': ' . $e->getMessage());
    }
}



        public function descargarFormeta()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=Formato Expediente Ruta.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columnas = ['Orden','virtual','Status','Tipo_servicio','Tipo_orden','NOMBRECLIENTE','DIRECCION','OBS','SIGLASCENTRAL','AREA','FECHAINSTALACION','AUTORIZA','ESTATUS','TECNOLOGIA'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        $fkTienda = session('user_fkTienda') ?? 0;
        // Línea de ejemplo opcional:
        fputcsv($file, [23450285,1005749,'A','DT',"DA",'JUAN PEREZ','Canton camoja, Huehuetanango, Huehuetenango',"ORDEN QUE SOLO SE AGREGAN CAJAS ADICIONALES",'HUE0301','OC3',"15/06/2025",'1T','I','WTTx']);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

        public function descargarinventario()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=Formato Expediente inventario.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columnas = ['serie','SKU','almacen','Lote','MAC1','MAC2','MAC3','ESTATUS','COSTO','CENTRO','TIPO','unidadmedida','TIPOMOVIMIENTO','Naturaleza','Status', 'cantidad'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        $fkTienda = session('user_fkTienda') ?? 0;
        // Línea de ejemplo opcional:
        fputcsv($file, ['fajJSJJDF4013896',4013896,'ALMA','A000',"N/A",'N/A','N/A',"A",350,'G817',"MA/MO",'UNIDAD',231,'D','I',1]);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

      public function pagocobro($id)
    {
            try {
                                if(!Auth::check()){
            return redirect()->route('login');
        }

            DB::beginTransaction();


            $tecnico=Tecnico::where('id',$id)->first();

            DB::commit();
            return redirect()->route('buckettecnico.index')->with('success', 'Puede filtrar por fechas');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }

    }

       public function produccion($id)
    {
        try {

                        if(!Auth::check()){
            return redirect()->route('login');
        }
            DB::beginTransaction();
            $tecnico=Tecnico::where('id',$id)->first();

            DB::commit();
            return redirect()->route('buckettecnico.index')->with('success', 'Puede filtrar por fechas');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }

    }


    public function obtenerClientes()
    {
        $clientes = Cliente::select('id', 'persona_id')
        ->get();
        return response()->json($clientes);
    }
public function ejecutarconsulta(Request $request)
    {
        try{
        $comprobanteId=$request->idcomprobante;

                $pdo = DB::getPdo();
        $stmt = $pdo->prepare("
    SELECT
        cc.id as idcuentacontable,
        dc.formula,
        cc.nombre,
        dc.Naturaleza,
        dc.valorminimo as resultado
    FROM
        dbsistemaventa.detalle_comprobantes as dc
    INNER JOIN
        cuentas_contables as cc
    ON
        cc.id = dc.fkCuentaContable
    WHERE
        dc.fkComprobante = :id
");

$stmt->execute(['id' => $comprobanteId]);


        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);


return response()->json($detallecomprobante);

        // Consultar los detalles del comprobante

}catch(Exception $e){

            return response()->json([
            'error' => 'Error al ejecutar la consulta',
            'detalle' => $e->getMessage()
        ], 500);
}

    }

    public function obtenerdetalles(string $sql, array $parametros)
    {
        try{

                $pdo = DB::getPdo();
        $stmt = $pdo->prepare($sql);
        if($parametros['id']==''){
        $stmt->execute();
        }else{
        $stmt->execute($parametros);
        };

        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);


        return $detallecomprobante;

        // Consultar los detalles del comprobante

}catch(Exception $e){

    $detallecomprobante[0]="Error: ".$e->getMessage();
}

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
    if(!Auth::check()){
        return redirect()->route('login');
    }

    // Buscamos al técnico y su usuario relacionado directamente
    // Nota: $id aquí debe ser el ID del Técnico o de la Persona según tu tabla
    $tecnico = Tecnico::where('id', $id)->first();

    if (!$tecnico || !$tecnico->fkuser) {
        return back()->with('error', 'No se encontró el usuario asociado a este técnico.');
    }

    try {
        DB::beginTransaction();

        // 1. Desactivar técnico
        $tecnico->update(['especialidad' => 'INACTIVO']); 

        // 2. Desactivar usuario
        $user = User::findOrFail($tecnico->fkuser);
        $user->status = 0; 
        $user->save();

        // 3. Quitar roles (Spatie)
        $user->roles()->detach();

        DB::commit();
        return redirect()->route('tecnico.lista')->with('success', 'Técnico y usuario desactivados correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error al procesar la baja: ' . $e->getMessage());
    }
}

}
