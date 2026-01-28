<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; // Asegúrate de que esta línea esté presente

use App\Models\Materialmanoobra;
use Illuminate\Http\Request;
use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Http\Requests\StoreClienteExistenteRequest;
use App\Http\Requests\UpdateTecnicoRequest;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Documento;
use App\Models\MovimientoMaterial;
use App\Models\Expedientetecnico;
use App\Models\Persona;
use App\Models\Pagotecnico;
use App\Models\Tienda;
use App\Models\usuariotienda;
use App\Models\Expedientefotograficotecnico;
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
use Intervention\Image\Drivers\Gd\Driver; // Import GD driver
use Intervention\Image\Encoders\WebpEncoder; // Import WebP encoder
use App\Http\Controllers\movimientomaterialesController;
use App\Models\Tecnico;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\AssignOp\Concat;
use Illuminate\Pagination\Paginator;
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

    $request->validate([
        'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

        try {
            DB::beginTransaction();
//creacion de entidad persona
            $persona = Persona::create(array_merge([
                'razon_social'=>$request->razon_social,
                'direccion'=>$request->direccion,
                'tipo_persona'=>$request->tipo_persona,
                'estado'=>1,
                'documento_id'=>$request->documento_id,
                'numero_documento'=>$request->numero_documento,
                'fkuser'=>$request->user,
                'created_at'=>now()
            ]));

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
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el cliente.');
        }

        return redirect()->route('tecnico.lista')->with('success', 'Tecnico registrado');

    }
    public function exist(Request $request)
    {
        try {
            DB::beginTransaction();

         // Procesar imagen y convertir a BLOB
        $file = $request->file('image');
        if($file!=null){
        $manager = new ImageManager(new Driver());

        $image = $manager->read($file->getPathname())
                         ->resize(800, 800, function ($constraint) {
                             $constraint->aspectRatio();
                             $constraint->upsize();
                         });

        // Convertir a WebP y obtener como cadena binaria
        $webpEncoder = new WebpEncoder(quality: 80);
        $imageBlob = $image->encode($webpEncoder);
        }
$tecnico = Tecnico::findOrFail($request['idtecnico']);


           $tecnico->update(
['fkuser'=>$request->user,
            'fkTienda'=>$request->tienda,
            'codigo'=>$request->numero_eta,
            'especialidad'=>$request->especialidad,
            'updated_at' => now(),
            ]);


if($imageBlob!=null){
               $tecnico->update(
['logo'=>$imageBlob,
]);

}

            DB::commit();

            return redirect()->route('tecnico.lista')->with('success', 'Tecnico registrado exitosamente.');


        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar cliente existente - Persona ID: ' . $request->persona_id . ' - Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el cliente.');
        }
    }

    public function obtenerdetalless(Request $request){

        try {
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

    public function inventariotecnicoorden($tecbucket)
    {
        $orden = Expedientetecnico::where('id', $tecbucket)
            ->where(function($query) {
                $query->where('Status', 'I')
                    ->orWhere('Status', 'S');
            })
            ->first();


        $tecnico = Tecnico::find($orden->fkTecnico)->first();


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
        $iss=$request->id1;
$sqlll = "
WITH RECURSIVE nodo_padre AS (
    -- Caso base: nodo raíz conocido
    SELECT id, padre_id, nombre, sku
    FROM arbolmanoobra
    WHERE id = ? and fkTienda= ?
    UNION ALL
    -- Recorriendo hacia abajo (hijos)
    SELECT a.id, a.padre_id, a.nombre, a.sku
    FROM arbolmanoobra a
    INNER JOIN nodo_padre np ON a.padre_id = np.id
    WHERE a.fkTienda= ?
),

cte_raiz AS (
    SELECT * FROM nodo_padre
)

SELECT DISTINCT
ams.nombre, ams.sku, ams.limite, ams.minimo, ams.fkTienda, ams.padre_id
FROM cte_raiz as r
JOIN treematerialescategoria AS am
ON am.nombre COLLATE utf8mb4_unicode_ci = r.nombre
JOIN treematerialescategoria AS ams ON ams.padre_id = am.id

";
$stmt = $pdo->prepare($sqlll);
$stmt->execute([$request->id1, $fkTienda, $fkTienda]);


        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$resultado = [];
$mat="";
foreach ($detallecomprobante as $key => $value) {
    $cantidadTotal = MovimientoMaterial::where('fkTienda', $fkTienda)
        ->where('SKU', $value['sku'])
        ->sum('cantidad');

        $mat=$mat.','.$value['sku'];
}

$mat = substr($mat, 1);



    $resultado = MovimientoMaterial::join('treematerialescategoria as tmc', 'tmc.sku', '=', 'movimientomateriales.SKU')
    ->where('movimientomateriales.fkTienda', $fkTienda)
    ->where('fkTecnico', $request->id2)
    ->where('movimientomateriales.STATUS', 'I')
    ->whereIn('movimientomateriales.SKU', explode(',', $mat))
    ->select(
        'movimientomateriales.id',
        'movimientomateriales.serie',
        'tmc.nombre as categoria_nombre',
        'tmc.sku as sku',
        DB::raw('IFNULL(movimientomateriales.cantidad, 1) as cantidad')
    )
    ->get();

        return response()->json($resultado->toArray());


            } catch (Exception $e) {
            dd($e);
            DB::rollBack();
        }

    }

    public function update(UpdateTecnicoRequest $request, Tecnico $tecnico)
    {
        try {
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
            DB::beginTransaction();
            $items = $request->input('items', []);

                $iditems = $request->input('arrayiditem', []);
                $contar=0;


                foreach ($iditems as $iditem) {

                    MovimientoMaterial::where('id', $iditems)
                        ->where('cantidad','>=',$request->input('arraycantidad')[$contar])
                        ->update([
                            'Estatus' => 'S',
                            'updated_at' => now(),
                            'Status' => 'C',
                        ]);

//obtiene datos de movimienot de materiales con iditem seleccionado por tecnico y los guarda en $mat, esta variable se utilizara para actualizar inventario del registro del item y compararlo con lo que el tenico selecciona.
                       $mat= MovimientoMaterial::where('id', $iditem)
                        ->get();

                        //validamos que la cantidad en inventario sea mayor o igual a la que el tecnico selecciona
                            $valor=$mat[0]->cantidad-$request->input('arraycantidad')[$contar];
                            if($valor>=0){

                                //si es meno o igual, validamos el material (mano de obra o material) actualizamos el stock, y en el caso de lo seriado lo pasamos a estatus "C" y "S", y actualizamos la cantidad en lo no seriado o mano de obra
                            MovimientoMaterial::where('id', $iditem)
                            ->whereNot('TIPO', 'MO')
                            ->where('serie', '-')
                            ->update([
                                'Estatus' => 'S',
                                'updated_at' => now(),
                                'cantidad'=>$valor
                            ]);

                            MovimientoMaterial::where('id', $iditem)
                            ->where('serie','<>', '-')
                            ->whereNot('TIPO', 'MO')
                            ->update([
                                'Estatus' => 'S',
                                'updated_at' => now(),
                                'Status' => 'C',
                            ]);

                            } else{
                            MovimientoMaterial::where('id', $iditem)
                            ->whereNot('TIPO', 'MO')
                            ->update([
                                'Estatus' => 'S',
                                'updated_at' => now(),
                                'Status' => 'C',
                                'cantidad'=>0
                                ]);
                            }
                        MovimientoMaterial::insert([
                            'fkTienda' => $expediente->fkTienda,
                            'SKU' => $request->input('SKU',$request->input('arraysku')[$contar]) ?? '-',
                            'serie' => $request->input('serie',$request->input('arrayserie')[$contar]) ?? '-',
                            'cantidad' => $request->input('cantidad',$request->input('arraycantidad')[$contar]) ?? 1,
                            'Centro' => 'CF',
                            'ESTATUS' => $mat[0]->ESTATUS ?? 'C',
                            'created_at' => now(),
                            'Creado_por' => Auth::user()->name,
                            'TIPO' => $mat[0]->TIPO,
                            'almacen'  => $mat[0]->almacen ?? 'ALMA',
                            'TIPOMOVIMIENTO'=>221,
                            'Naturaleza'=>'H',
                            'MAC1'=>$mat[0]->MAC1 ?? 'N/A',
                            'MAC2'=>$mat[0]->MAC2 ?? 'N/A',
                            'MAC3'=>$mat[0]->MAC3 ?? 'N/A',
                            'COSTO'=>$mat[0]->COSTO,
                            'Modificado_el'=>now(),
                            'Modificado_por'=>Auth::user()->name,
                            'creado_el'=>now(),
                            'Status'=>'C',
                            'unidadmedida'=>$mat[0]->unidadmedida ?? 'UNIDAD',
                            'Lote'=>$mat[0]->lote ?? 'A00',
                            'fkExpediente'=>$expediente->id,
                        ]);

                        Pagotecnico::insert([
                            'Orden' => $expediente->Orden,
                            'SKU' => $request->input('SKU',$request->input('arraysku')[$contar]),
                            'Descripcion' => $request->input('arraynameProducto')[$contar],
                            'OBS' => 'Pago por servicio tecnico',
                            'Cantidad' => $request->input('cantidad',$request->input('arraycantidad')[$contar]),
                            'COSTOPAGO' => $request->input('COSTOPAGO',$request->input('arraycantidad')[$contar]*$mat[0]->COSTO),
                            'created_at' => now(),
                            'fkTienda' => $expediente->fkTienda,
                            'fkTecnico' => $expediente->fkTecnico,
                            'Naturaleza'=>'D',
                            'Status'=>'C',
                        ]);

                Expedientetecnico::where('id', $expediente->id)
                ->update([
                    'Status' => 'S',
                    'FECHAINSTALACION' => now(),
                    'OBS'=>$expediente->OBS.' ||OBS TECNICO: '.$request->input('obs'),
                ]);

        $cantidad = $request['arraycantidad'][$contar] ?? 1;
                    $items = $request->input('items', [])[$contar] ?? [];

        $photos   = $items['photos'] ?? [];
        $names   = $items['names'] ?? [];



              foreach ($photos as $i => $photoBase64) {
            // 1. Separar metadata del base64
            @list($type, $fileData) = explode(';', $photoBase64);
            @list(, $fileData) = explode(',', $fileData);

            if ($fileData) {
                $fileData = base64_decode($fileData);

                // 2. Obtener extensión
                $extension = str_contains($type, 'png') ? 'png' : 'jpg';

                // 3. Definir nombre único
                $fileName = $names[$i] .'_'.$request->input('arraynameProducto')[$contar];
                $fileName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $fileName).'.'. $extension;


                // 4. Guardar en storage/app/public/fotos/ordenes/{id}
                $path = "public/fotos/ordenes/{$expediente->Orden}";

                Expedientefotograficotecnico::create([
                    'fkTienda' => $expediente->fkTienda,
                    'Orden' => $expediente->Orden,
                    'fotografia' => "storage/fotos/ordenes/{$expediente->Orden}/{$fileName}",
                    'created_at' => now()
                ]);

                Storage::put("{$path}/{$fileName}", $fileData);
            }
        }



                        $contar=$contar+1;
                }




            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }

        return redirect()->route('tecnico.buckettecnico')->with('success', 'Orden actualizada');
    }

        public function bucket($id)
    {
        try {
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
            ->paginate(10);

                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)
            ->where('Status','S')
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->paginate(10);
                };
                    }else{
                if ($Estatus == 'ER') {

            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)
            ->where('Status','S')
            ->where('fkTecnico',$idtecnico)->paginate(100000);

                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)
            ->where('Status','S')
            ->where('fkTecnico',$idtecnico)->paginate(100000);
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
                    $Estatus = session('user_estatus');
                    $fkTienda = session('user_fkTienda');
                    $idtecnico= $request->input('id');


   $relacion = MovimientoMaterial::with(['treematerialcategoria' => function($query) {
                // Solo traer columnas necesarias
                $query->select('SKU', 'nombre as descripcion');
            }])
            ->where('fkTienda', $fkTienda)
            ->where('ESTATUS', 'A')
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
                    ->where('ESTATUS','I')
                    ->get();
                    $tecnico=null;
                } else {
                    $tecnico=null;
                    $tecnico=Tecnico::where('fkTienda',$fkTienda)
                    ->where('id',$idtecnico)->first();
                    $expediente=Expedientetecnico::where('fkTienda',$fkTienda)
                    ->where('ESTATUS','I')
                    ->where('fkTecnico',$idtecnico)->get();
                };

            DB::commit();

            return view('tecnico.bucketlista', compact('tecnicos','tecnico','expediente','Estatus'));

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }


    }
public function importarMAMO(Request $request)
{
    $fkTienda = session('user_fkTienda');
    $id = $request->input('id');

    // Validar archivo
    $request->validate([
        'archivo' => 'required|file|mimes:csv,txt',
    ]);

    $file = fopen($request->file('archivo')->getRealPath(), 'r');
    $encabezado = fgetcsv($file); // leer encabezados

    DB::beginTransaction();

    try {
        $fila = 1; // para loggear el número de fila
        while (($linea = fgetcsv($file)) !== false) {
            $fila++;
            $data = array_combine($encabezado, $linea);

            // Validar campos mínimos
            if (!isset($data['Orden']) || !isset($data['virtual']) || !isset($data['NOMBRECLIENTE'])) continue;

            // Convertir campos a UTF-8
            $orden         = mb_convert_encoding($data['Orden'] ?? '', 'UTF-8', 'ISO-8859-1');
            $virtual       = mb_convert_encoding($data['virtual'] ?? '', 'UTF-8', 'ISO-8859-1');
            $status        = mb_convert_encoding($data['Status'] ?? '', 'UTF-8', 'ISO-8859-1');
            $tiposervicio  = mb_convert_encoding($data['Tipo_servicio'] ?? '', 'UTF-8', 'ISO-8859-1');
            $tipoorden     = mb_convert_encoding($data['Tipo_orden'] ?? '', 'UTF-8', 'ISO-8859-1');
            $nombrecliente = mb_convert_encoding($data['NOMBRECLIENTE'] ?? '', 'UTF-8', 'ISO-8859-1');
            $direccion     = mb_convert_encoding($data['DIRECCION'] ?? '', 'UTF-8', 'ISO-8859-1');
            $obs           = mb_convert_encoding($data['OBS'] ?? '', 'UTF-8', 'ISO-8859-1');
            $siglas        = mb_convert_encoding($data['SIGLASCENTRAL'] ?? '', 'UTF-8', 'ISO-8859-1');
            $area          = mb_convert_encoding($data['AREA'] ?? '', 'UTF-8', 'ISO-8859-1');
            $fechainst     = mb_convert_encoding($data['FECHAINSTALACION'] ?? '', 'UTF-8', 'ISO-8859-1');
            $autoriza      = mb_convert_encoding($data['AUTORIZA'] ?? '', 'UTF-8', 'ISO-8859-1');
            $estatus       = mb_convert_encoding($data['ESTATUS'] ?? '', 'UTF-8', 'ISO-8859-1');
            $tecnologia    = mb_convert_encoding($data['TECNOLOGIA'] ?? '', 'UTF-8', 'ISO-8859-1');

            // Convertir fecha de forma segura
            $fecha = null;
            if (!empty($fechainst)) {
                try {
                    $fecha = Carbon::createFromFormat('d/m/Y', $fechainst)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Fila {$fila} - Fecha inválida: {$fechainst} (Orden: {$orden})");
                    $fecha = null; // o puedes asignar hoy: now()->format('Y-m-d')
                }
            }

            // Insertar o actualizar
            DB::table('expedientetecnico')->updateOrInsert(
                [
                    'orden' => $orden,
                    'virtual' => $virtual,
                    'fkTienda' => $fkTienda,
                    'fkTecnico' => $id,
                    'FECHAINSTALACION' => $fecha,
                ],
                [
                    'status' => $status,
                    'fkTecnico' => $id,
                    'tipo_servicio' => $tiposervicio,
                    'tipo_orden' => $tipoorden,
                    'nombrecliente' => $nombrecliente,
                    'direccion' => $direccion,
                    'obs' => $obs,
                    'SIGLASCENTRAL' => $siglas,
                    'area' => $area,
                    'FECHAINSTALACION' => $fecha,
                    'autoriza' => $autoriza,
                    'Estatus' => $estatus,
                    'TECNOLOGIA' => $tecnologia,
                    'updated_at' => now(),
                ]
            );
        }

        DB::commit();
        return back()->with('success', 'Mano de Obra o Materiales de Eta importados correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error al importar CSV: ' . $e->getMessage());
        return back()->with('error', 'Error al importar: ' . $e->getMessage());
    }
}

public function importarInvTecnico(Request $request)
{
    $fkTienda = session('user_fkTienda');
    $id = $request->input('id');
    $nombreUsuario=session('nombreUsuario');
    $user = User::find(Auth::user()->id);
    // Validar archivo
    $request->validate([
        'archivoinv' => 'required|file|mimes:csv,txt',
    ]);

    $file = fopen($request->file('archivoinv')->getRealPath(), 'r');
    $encabezado = fgetcsv($file); // leer encabezados

    DB::beginTransaction();

    try {
        $fila = 1; // para loggear el número de fila
        while (($linea = fgetcsv($file)) !== false) {
            $fila++;
            $data = array_combine($encabezado, $linea);

            // Validar campos mínimos
            if (!isset($data['serie']) || !isset($data['SKU']) || !isset($data['MAC1'])) continue;

            // Convertir campos a UTF-8
            $serie         = mb_convert_encoding($data['serie'] ?? '', 'UTF-8', 'ISO-8859-1');
            $SKU       = mb_convert_encoding($data['SKU'] ?? '', 'UTF-8', 'ISO-8859-1');
            $almacen        = mb_convert_encoding($data['almacen'] ?? '', 'UTF-8', 'ISO-8859-1');
            $Lote  = mb_convert_encoding($data['Lote'] ?? '', 'UTF-8', 'ISO-8859-1');
            $MAC1     = mb_convert_encoding($data['MAC1'] ?? '', 'UTF-8', 'ISO-8859-1');
            $MAC2 = mb_convert_encoding($data['MAC2'] ?? '', 'UTF-8', 'ISO-8859-1');
            $MAC3     = mb_convert_encoding($data['MAC3'] ?? '', 'UTF-8', 'ISO-8859-1');
            $ESTATUS          = mb_convert_encoding($data['ESTATUS'] ?? '', 'UTF-8', 'ISO-8859-1');
            $COSTO        = mb_convert_encoding($data['COSTO'] ?? '', 'UTF-8', 'ISO-8859-1');
            $CENTRO         = mb_convert_encoding($data['CENTRO'] ?? '', 'UTF-8', 'ISO-8859-1');
            $TIPO     = mb_convert_encoding($data['TIPO'] ?? '', 'UTF-8', 'ISO-8859-1');
            $unidadmedida      = mb_convert_encoding($data['unidadmedida'] ?? '', 'UTF-8', 'ISO-8859-1');
            $TIPOMOVIMIENTO       = mb_convert_encoding($data['TIPOMOVIMIENTO'] ?? '', 'UTF-8', 'ISO-8859-1');
            $Naturaleza   = mb_convert_encoding($data['Naturaleza'] ?? '', 'UTF-8', 'ISO-8859-1');
            $Status   = mb_convert_encoding($data['Status'] ?? '', 'UTF-8', 'ISO-8859-1');
            $cantidad   = mb_convert_encoding($data['cantidad'] ?? '', 'UTF-8', 'ISO-8859-1');


            // Convertir fecha de forma segura
            $fecha = now();
            if (!empty($fechainst)) {
                try {
                    $fecha = Carbon::createFromFormat('d/m/Y', $fechainst)->format('Y-m-d');
                } catch (\Exception $e) {
                    Log::warning("Fila {$fila} - Fecha inválida: {$fechainst} (Orden: {$serie})");
                    $fecha = null; // o puedes asignar hoy: now()->format('Y-m-d')
                }
            }

            // Insertar o actualizar
            DB::table('movimientomateriales')->updateOrInsert(
                [
                    'serie' => $serie,
                    'SKU' => $SKU,
                    'fkTienda' => $fkTienda,
                    'fkTecnico' => $id,
                    'almacen' => $almacen,
                    'Lote' => $Lote,
                    'MAC1' => $MAC1,
                    'MAC2' => $MAC2,
                    'MAC3' => $MAC3,
                    'ESTATUS' => $ESTATUS,
                    'COSTO' => $COSTO,
                    'CENTRO' => $CENTRO,
                    'TIPO' => $TIPO,
                    'unidadmedida' => $unidadmedida,
                    'TIPOMOVIMIENTO' => $TIPOMOVIMIENTO,
                    'Naturaleza' => $Naturaleza,
                    'Status' => $Status,
                    'Modificado_el'=> $fecha,
                    'Modificado_por' => $nombreUsuario,
                    'Creado_el' => $fecha,
                    'Creado_por' => $nombreUsuario,
                    'cantidad' => $cantidad,
                ],
                [
                    'fkTienda' => $fkTienda,
                    'fkTecnico' => $id,
                    'Lote' => $Lote,
                    'MAC1' => $MAC1,
                    'MAC2' => $MAC2,
                    'MAC3' => $MAC3,
                    'COSTO' => $COSTO,
                    'CENTRO' => $CENTRO,
                    'unidadmedida' => $unidadmedida,
                    'TIPOMOVIMIENTO' => $TIPOMOVIMIENTO,
                    'Naturaleza' => $Naturaleza,
                    'Status' => $Status,
                    'updated_at' => now(),
                    'Modificado_el'=> $fecha,
                    'Modificado_por' => $nombreUsuario,
                    'cantidad' => $cantidad,
                ]
            );
        }

        DB::commit();
        return back()->with('success', 'Mano de Obra o Materiales de Eta importados correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error al importar CSV: ' . $e->getMessage());
        return back()->with('error', 'Error al importar: ' . $e->getMessage());
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
