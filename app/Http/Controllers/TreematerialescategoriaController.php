<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\Request;
use App\Models\CuentaContable;
use App\Models\Material_relaciones;
use App\Models\treematerialescategoria;
use SebastianBergmann\LinesOfCode\Counter;
use Carbon\Carbon;

use function Laravel\Prompts\text;

class TreematerialescategoriaController extends Controller
{
    public function index()
    {
        try {
            $ver=session('user_fkTienda');
            $ver2 = DB::table('treematerialescategoria')
            ->where('fkTienda', $ver) // Obtener hijos
            ->get();

            if($ver2->isEmpty()){
            $this->createRoottreematerialescategoriaesIfNotExist(); // Ensure root nodes exist
        }
            $treematerialescategoriaes = DB::table('treematerialescategoria')
            ->whereNull('padre_id') // Obtener treematerialescategoriaes raíz
            ->get();

        // Inicializar un arreglo para las treematerialescategoriaes con hijos
        $treematerialescategoriaesConHijos = [];

        // Obtener hijos para cada cuenta raíz
        foreach ($treematerialescategoriaes as $cuenta) {
            $hijos = DB::table('treematerialescategoria')
                ->where('padre_id', $cuenta->id) // Obtener hijos
                ->get();

            // Agregar la cuenta y sus hijos al arreglo
            $treematerialescategoriaesConHijos[] = [
                'cuenta' => $cuenta,
                'hijos' => $hijos,
            ];
        }


            return view('treematerialescategoria.index', compact('treematerialescategoriaesConHijos'));
        } catch (\Exception $e) {
            Log::error('Error al crear nodos raíz: ' . $e->getMessage());
            return response()->json(['error' => 'Hubo un error al crear los nodos raíz.'], 500);
        }
    }
    public function show($id)
    {
        // Lógica para mostrar la cuenta contable
    }
    public function createRoottreematerialescategoriaesIfNotExist()
    {
        // Check if root nodes already exist

        $rootNodesCount = DB::table('treematerialescategoria')
        ->whereNull('padre_id')
        ->where('fkTienda',session('user_fkTienda'))
        ->count();

        // If no root nodes, create "Activo" and "Pasivo"
        if ($rootNodesCount === 0) {
            // Create root node for "Activo"
            $activo = DB::table('treematerialescategoria')->insert([
                'nombre' => 'xDSL',
                'SKU' => '01', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);

            $activo = DB::table('treematerialescategoria')->insert([
                'nombre' => 'DTH',
                'SKU' => '02', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);

            $activo = DB::table('treematerialescategoria')->insert([
                'nombre' => 'HFC',
                'SKU' => '03', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);
            $activo = DB::table('treematerialescategoria')->insert([
                'nombre' => 'GPON',
                'SKU' => '04', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);
            $activo = DB::table('treematerialescategoria')->insert([
                'nombre' => 'COBRE',
                'SKU' => '05', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);
            $activo = DB::table('treematerialescategoria')->insert([
                'nombre' => 'CONECTIVIDAD WIFI',
                'SKU' => '06', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);
            $activo = DB::table('treematerialescategoria')->insert([
                'nombre' => 'WTTx',
                'SKU' => '07', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);

        }
    }
    public function fetch()
    {
        // Obtener todas las treematerialescategoriaes raíz (aquellas que no tienen padre)
        $treematerialescategoriaes = DB::table('treematerialescategoria')
            ->whereNull('padre_id') // Filtra las treematerialescategoriaes que no tienen padre
            ->get();

        // Construir el árbol completo de treematerialescategoriaes
        $treeData = [];
        foreach ($treematerialescategoriaes as $cuenta) {
            $treeData[] = $this->buildTreeNode($cuenta);
        }

        return response()->json($treeData);
    }
    public function delete(Request $request, Treematerialescategoria $cuentaContable){
    try{
            DB::beginTransaction();
            Treematerialescategoria::where('id',$request->id_delete)->delete();

                DB::commit();

            } catch (Exception $e) {
                dd($e);
                DB::rollBack();
            }

            return response()->json(['success' => 'Cuenta agregada exitosamente.']);
    }
    private function buildTreeNode($cuenta)
    {
        // Obtener los hijos de la cuenta actual
        $hijos = DB::table('treematerialescategoria')
            ->where('padre_id', $cuenta->id)
            ->get();

        // Construir la estructura del nodo actual
        $children = [];
        foreach ($hijos as $hijo) {
            // Llamada recursiva para construir el árbol de los hijos
            $children[] = $this->buildTreeNode($hijo);
        }

        // Retornar el nodo con sus hijos
        return [
            'nodeId'=>$cuenta->id,
            'text' => $cuenta->nombre,
            'nodes' => $children // Si no tiene hijos, 'nodes' será un arreglo vacío
        ];
    }

    public function fetch2()
    {
        // Inicializamos el ID de la categoría padre como NULL para empezar desde el nodo raíz.
        $data = $this->get_node_data(null);

        // Codificamos los datos en formato JSON para enviarlos al frontend.
        echo json_encode(array_values($data));
    }

    function get_node_data($parent_category_id)
    {
        // Obtenemos las treematerialescategoriaes contables que tienen como padre el ID dado
        $result = DB::table('treematerialescategoria')
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
            $sub_array['limite'] = $row->limite; // Mostrar el nombre de la cuenta
            $sub_array['minimo'] = $row->minimo; // Mostrar el nombre de la cuenta
            $sub_array['fotografia'] = $row->fotografia; // Mostrar el nombre de la cuenta
            $sub_array['obs'] = $row->obs; // Mostrar el nombre de la cuenta
            $sub_array['nodes'] = $this->get_node_data($row->id); // Recursión para obtener los hijos
            $output[] = $sub_array; // Agregar al array de salida
        }

        return $output;
    }



    // Método auxiliar para construir el árbol
    private function buildTree($treematerialescategoriaes)
    {
        $tree = [];

        foreach ($treematerialescategoriaes as $cuenta) {
            $node = [
                'text' => $cuenta->SKU."-".$cuenta->nombre, // El nombre que se mostrará en el árbol
                'nombre' => $cuenta->nombre, // El nombre que se mostrará en el árbol
                'Cid' => $cuenta->id, // ID de la cuenta
                'nodes' => $this->buildTree($cuenta->children) // Obtener hijos de la cuenta (usando nodes)
            ];

            $tree[] = $node;
        }

        return $tree;
    }

    public function generarNumeroCuenta(Request $request)
    {
        // Obtener el padre_id del request
        $padreId = $request->input('padre_id');

        // Buscar la cuenta padre
        $cuentaPadre = DB::table('treematerialescategoria')
            ->where('id', $padreId) // Buscamos por el id del padre, que debe ser idCuenta
            ->first(); // Obtenemos el primer registro

        if ($cuentaPadre) {
            // Obtener todos los hijos de esa cuenta padre
            $hijos = DB::table('treematerialescategoria')
                ->where('padre_id', $padreId) // Buscamos por padre_id
                ->count();

            // Generar el nuevo número de cuenta basado en el número de cuenta del padre y la cantidad de hijos
            $nuevoNumeroHijo = str_pad($hijos + 1, 2, '0', STR_PAD_LEFT); // Ej: '01', '02', etc.

            // Formatear el número de cuenta (Ej: ##.##.##.##)
            $nuevoNumeroCuenta = $cuentaPadre->SKU . '.' . $nuevoNumeroHijo;

            return response()->json(['nuevoNumeroCuenta' => $nuevoNumeroCuenta]);
        }

        return response()->json(['error' => 'No se encontró la cuenta padre.'], 404);
    }


    public function fillParentCategory()
    {
            $treematerialescategoriaes = DB::table('treematerialescategoria')
        ->get();

        // Crear un arreglo para enviar como JSON
        $options = [];
        foreach ($treematerialescategoriaes as $cuenta) {
            $options[] = [
                'id' => $cuenta->id,
                'nombre' => $cuenta->nombre,
            ];
        }

        return response()->json($options); // Retornar JSON
    }

    public function importarMAMO(Request $request)
{
    $request->validate([
        'archivo' => 'required|file|mimes:csv,txt',
    ]);

    $idTienda=session('user_fkTienda');
    $idmasivo=$request->input('id_masivo');
    $skupadre=Treematerialescategoria::where('id',$idmasivo)->first();
    $file = fopen($request->file('archivo')->getRealPath(), 'r');
    $encabezado = fgetcsv($file); // leer encabezados

    DB::beginTransaction();

    try {
        while (($linea = fgetcsv($file)) !== false) {
            $data = array_combine($encabezado, $linea);

            // Validar campos mínimos
            if (!isset($data['SKU']) || !isset($data['minimo'])  || !isset($data['maximo'])  || !isset($data['depende_SKU']) || !isset($data['nombre'])) continue;

// Convertir campos potencialmente con caracteres especiales a UTF-8

$nombre       = mb_convert_encoding($data['nombre'] ?? '', 'UTF-8', 'ISO-8859-1');
$SKU        = mb_convert_encoding($data['SKU'] ?? '', 'UTF-8', 'ISO-8859-1');
$depende_SKU        = mb_convert_encoding($data['depende_SKU'] ?? '', 'UTF-8', 'ISO-8859-1');
$tipo_relacion        = mb_convert_encoding($data['tipo_relacion'] ?? '', 'UTF-8', 'ISO-8859-1');
$maximo=$data['maximo'] ?? 1;
$minimo=$data['minimo'] ?? 0;

if($skupadre->SKU!=$SKU){
// Insertar o actualizar
DB::table('material_relaciones')->updateOrInsert(
    [
        'fkTienda' => $idTienda,
        'nombre'=> $nombre,
        'SKU' => $SKU,
        'depende_SKU' => $depende_SKU,
        'tipo_relacion' => $tipo_relacion,
        'idtree'=>$idmasivo,
        'maximo'=>$maximo,
        'minimo'=>$minimo,
    ],
    [
        'fkTienda' => $idTienda,
        'nombre'=> $nombre,
        'SKU' => $SKU,
        'depende_SKU' => $depende_SKU,
        'tipo_relacion' => $tipo_relacion,
        'maximo'=>$maximo,
        'minimo'=>$minimo,
        'updated_at'=> now(),
        'idtree'=>$idmasivo,
        'created_at' => now(),
    ]
);
}

        }

        DB::commit();
        return back()->with('success', 'Se han importado las validaciones de forma exitosa importados correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error al importar: ' . $e->getMessage());
    }
}

    public function importarmasivohijos(Request $request)
{
    $request->validate([
        'archivohijos' => 'required|file|mimes:csv,txt',
    ]);

    $idTienda=session('user_fkTienda');
    $idmasivohijos=$request->input('id_masivohijos');
    $file = fopen($request->file('archivohijos')->getRealPath(), 'r');
    $encabezado = fgetcsv($file); // leer encabezados

    DB::beginTransaction();

    try {
        while (($linea = fgetcsv($file)) !== false) {
            $data = array_combine($encabezado, $linea);

            // Validar campos mínimos
            if (!isset($data['SKU']) || !isset($data['minimo']) || !isset($data['limite']) || !isset($data['obs']) || !isset($data['nombre'])) continue;

// Convertir campos potencialmente con caracteres especiales a UTF-8

$nombre       = mb_convert_encoding($data['nombre'] ?? '', 'UTF-8', 'ISO-8859-1');
$SKU        = mb_convert_encoding($data['SKU'] ?? '', 'UTF-8', 'ISO-8859-1');
$obs        = mb_convert_encoding($data['obs'] ?? '', 'UTF-8', 'ISO-8859-1');
$limite =$data['limite'] ?? 0;
$minimo =$data['minimo'] ?? 0;


// Insertar o actualizar
DB::table('treematerialescategoria')->updateOrInsert(
    [
        'fkTienda' => $idTienda,
        'nombre'=> $nombre,
        'SKU' => $SKU,
        'obs' => $obs,
        'limite' => $limite,
        'minimo'=>$minimo,
        'padre_id'=>$idmasivohijos,
    ],
    [
        'fkTienda' => $idTienda,
        'nombre'=> $nombre,
        'SKU' => $SKU,
        'obs' => $obs,
        'limite' => $limite,
        'minimo'=>$minimo,
        'padre_id'=>$idmasivohijos,
        'updated_at'=> now(),
        'created_at' => now(),
    ]
);

        }

        DB::commit();
        return back()->with('success', 'Se han importados lso datos para el arbol de validacion de forma exitosa.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error al importar: ' . $e->getMessage());
    }
}

    public function importarmasivohijospadres(Request $request)
{
    $request->validate([
        'archivohijospadres' => 'required|file|mimes:csv,txt',
    ]);

    $idTienda=session('user_fkTienda');
    $file = fopen($request->file('archivohijospadres')->getRealPath(), 'r');
    $encabezado = fgetcsv($file); // leer encabezados



    DB::beginTransaction();

    try {
        while (($linea = fgetcsv($file)) !== false) {
            $data = array_combine($encabezado, $linea);



            // Validar campos mínimos
            if (!isset($data['SKU']) || !isset($data['minimo']) || !isset($data['limite']) || !isset($data['obs']) || !isset($data['nombre'])) continue;

// Convertir campos potencialmente con caracteres especiales a UTF-8

$nombre       = mb_convert_encoding($data['nombre'] ?? '', 'UTF-8', 'ISO-8859-1');
$SKU        = mb_convert_encoding($data['SKU'] ?? '', 'UTF-8', 'ISO-8859-1');
$obs        = mb_convert_encoding($data['obs'] ?? '', 'UTF-8', 'ISO-8859-1');
$limite =$data['limite'] ?? 0;
$minimo =$data['minimo'] ?? 0;
$idmasivohijos=Treematerialescategoria::where('SKU',$data['SKUPADRE'])->where('fkTienda',$idTienda)->first();

// Insertar o actualizar
DB::table('treematerialescategoria')->updateOrInsert(
    [
        'fkTienda' => $idTienda,
        'nombre'=> $nombre,
        'SKU' => $SKU,
        'obs' => $obs,
        'limite' => $limite,
        'minimo'=>$minimo,
        'padre_id'=>$idmasivohijos->id,
    ],
    [
        'fkTienda' => $idTienda,
        'nombre'=> $nombre,
        'SKU' => $SKU,
        'obs' => $obs,
        'limite' => $limite,
        'minimo'=>$minimo,
        'padre_id'=>$idmasivohijos->id,
        'updated_at'=> now(),
        'created_at' => now(),
    ]
);

        }

        DB::commit();
        return back()->with('success', 'Se han importados lso datos para el arbol de validacion de forma exitosa.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error al importar: ' . $e->getMessage());
    }
}

public function obtenerpadre(string $SKUPADRE, string $SKU)
    {
try {
        $fkTienda = session('user_fkTienda');
        $pdo = DB::getPdo();
        $sqlll='
SELECT DISTINCT
    tcc1.nombre,
    tcc1.id AS idpadre,
    tcc2.id,
    tcc1.SKU,
    tcc2.nombre,
    tcc2.SKU,
    tcc2.fkTienda,
    tc.SKU as SKUpadre
FROM treematerialescategoria AS tc
INNER JOIN (
    SELECT *
    FROM treematerialescategoria AS tc1
    WHERE tc1.padre_id IS NULL
) AS tcc1 ON tcc1.id = tc.padre_id
INNER JOIN (
    SELECT *
    FROM treematerialescategoria AS tc2
    WHERE tc2.padre_id > 0) AS tcc2 ON tcc2.padre_id = tc.id where tc.SKU=:SKUPADRE AND tcc2.fkTienda=:IDTIENDA and tcc2.SKU=:SKU LIMIT 1';
        $stmt = $pdo->prepare($sqlll);

        $stmt->execute(['SKUPADRE' => $SKUPADRE,'IDTIENDA'=>$fkTienda,'SKU'=>$SKU]);


        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $detallecomprobante;

            } catch (Exception $e) {
            dd($e);
            DB::rollBack();
        }

    }
   public function importarmasivorelaciones(Request $request)
{
    $request->validate([
        'archivorelaciones' => 'required|file|mimes:csv,txt',
    ]);

    $idTienda=session('user_fkTienda');
    $idmasivo=$request->input('id_masivo');
    $file = fopen($request->file('archivorelaciones')->getRealPath(), 'r');
    $encabezado = fgetcsv($file); // leer encabezados

    DB::beginTransaction();

    try {
        while (($linea = fgetcsv($file)) !== false) {
            $data = array_combine($encabezado, $linea);

            // Validar campos mínimos
            if (!isset($data['SKUPADRE']) || !isset($data['SKU']) || !isset($data['minimo'])  || !isset($data['maximo'])  || !isset($data['depende_SKU']) || !isset($data['nombre'])) continue;

            $skupadress=$data['SKUPADRE'] ?? '';
    // Convertir campos potencialmente con caracteres especiales a UTF-8




$nombre       = mb_convert_encoding($data['nombre'] ?? '', 'UTF-8', 'ISO-8859-1');
$SKU        = mb_convert_encoding($data['SKU'] ?? '', 'UTF-8', 'ISO-8859-1');
$depende_SKU        = mb_convert_encoding($data['depende_SKU'] ?? '', 'UTF-8', 'ISO-8859-1');
$tipo_relacion        = mb_convert_encoding($data['tipo_relacion'] ?? '', 'UTF-8', 'ISO-8859-1');
$maximo=$data['maximo'] ?? 1;
$minimo=$data['minimo'] ?? 0;
$skupadre=$this->obtenerpadre($skupadress, $depende_SKU);
$skuval = $skupadre[0]['SKU'] ?? '';
$idmasivo = $skupadre[0]['id'] ?? '';

if($skuval<>$SKU){
// Insertar o actualizar
DB::table('material_relaciones')->updateOrInsert(
    [
        'fkTienda' => $idTienda,
        'nombre'=> $nombre,
        'SKU' => $SKU,
        'depende_SKU' => $depende_SKU,
        'tipo_relacion' => $tipo_relacion,
        'idtree'=>$idmasivo,
        'maximo'=>$maximo,
        'minimo'=>$minimo,
    ],
    [
        'fkTienda' => $idTienda,
        'nombre'=> $nombre,
        'SKU' => $SKU,
        'depende_SKU' => $depende_SKU,
        'tipo_relacion' => $tipo_relacion,
        'maximo'=>$maximo,
        'minimo'=>$minimo,
        'updated_at'=> now(),
        'idtree'=>$idmasivo,
        'created_at' => now(),
    ]
);
}

        }

        DB::commit();
        return back()->with('success', 'Se han importado las validaciones de forma exitosa importados correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error al importar: ' . $e->getMessage());
    }
}

    public function descargarFormeta()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=Formato Material Relacionado.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columnas = ['nombre','SKU','depende_SKU','tipo_relacion','maximo','minimo'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        $fkTienda = session('user_fkTienda') ?? 0;
        // Línea de ejemplo opcional:
        fputcsv($file, ['CPE ZTE MF266 BL BLANCO','4013453','34015907','requiere',1,0]);
        fputcsv($file, ['CPE ZTE MF266 BL BLANCO','4013453','34015907','incompatible',1,0]);
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}
    public function descargarFormHijos()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=Formato Catalogo Materiales.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columnas = ['nombre','SKU','limite','minimo','obs'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        $fkTienda = session('user_fkTienda') ?? 0;
        // Línea de ejemplo opcional:
        fputcsv($file, ['CPE ZTE MF266 BL BLANCO','4013453',0,0,'ejemplo bos']);
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    public function descargarFormHijosPadres()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=Formato Catalogo Materiales Masivo.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columnas = ['nombre','SKU','limite','minimo','obs','SKUPADRE'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        $fkTienda = session('user_fkTienda') ?? 0;
        // Línea de ejemplo opcional:
        fputcsv($file, ['CPE ZTE MF266 BL BLANCO','4013453',0,0,'ejemplo bos',"'01"]);
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    public function descargarFormMasivoRelaciones()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=Formato Cat Relaciones Masivo.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columnas = ['nombre','SKU','depende_SKU','tipo_relacion','maximo','minimo','SKUPADRE'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        $fkTienda = session('user_fkTienda') ?? 0;
        // Línea de ejemplo opcional:
        fputcsv($file, ['CPE ZTE MF266 BL BLANCO','4013453','34015907','requiere',1,0,'01']);
        fputcsv($file, ['CPE ZTE MF266 BL BLANCO','4013453','34015907','incompatible',1,0,'01']);
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

public function eliminarvalidacion(Request $request){
    try{
        DB::beginTransaction();

        Material_relaciones::where('id',$request->input('ID'))->delete();

        DB::commit();

    }catch(Exception $e){
        Log::error('Error al crear nodos raíz: ' . $e->getMessage());
        return response()->json(['error' => 'Hubo un error al crear los nodos raíz.'], 500);
    }
}

    public function fetchrelacion(Request $request)
{
    $ver=session('user_fkTienda');
    $idrelacion=$request->input('id_relacion');
    $relacion =     Material_relaciones::where('tipo_relacion','requiere')->where('fkTienda',$ver)->where('idtree',$idrelacion)->paginate(10);
    $incompatible = Material_relaciones::where('tipo_relacion','incompatible')->where('fkTienda',$ver)->where('idtree',$idrelacion)->paginate(10);

    if ($request->ajax()) {
        return view('treematerialescategoria.tabla', compact('relacion','incompatible'))->render();
    }

    return view('treematerialescategoria.index', compact('relacion','incompatible'));
}

        public function fillEstructura()
    {
try {
        $fkTienda = session('user_fkTienda');
        $pdo = DB::getPdo();
        $sqlll='
        select am.nombre as catalogo, amo.id, amo.SKU, amo.nombre, amo.padre_id from arbolmanoobra as amo inner join
        (SELECT concat(am.SKU," - ",am.nombre," || ",amo.SKU," - ",amo.nombre) as nombre, amo.id, amo.SKU, amo.padre_id, am.fkTienda FROM arbolmanoobra as amo
        inner join (select ams.id, ams.SKU, ams.nombre, ams.fkTienda from arbolmanoobra as ams where isnull(ams.padre_id)) AS am on am.id=amo.padre_id) as am on
        amo.padre_id=am.id where am.fkTienda=:id
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

public function add(Request $request)
{
    $request->validate([
        'nombre_new' => 'required',
        'cuenta_id_new' => 'required',
    ]);

        if ($request->hasFile('foto_new')) {
        $imagen = $request->file('foto_new');
        $nombreImagen = time() . '.' . $imagen->getClientOriginalExtension();
        $ruta = $imagen->storeAs('public/treematerialesmo', $nombreImagen);
                

    }

    $cuenta=DB::table('treematerialescategoria')->insert([
        'nombre' => $request->input('nombre_new'),
        'padre_id' => $request->input('padre_id'), // Puede ser null
        'SKU' => $request->input('cuenta_id_new'),
        'minimo' => $request->input('mi_new') ?? NULL,
        'limite' => $request->input('lm_new') ?? NULL,
        'fotografia' => $ruta ?? '',
        'obs' => $request->input('obs_new') ?? NULL,
        'fkTienda' => session('user_fkTienda'), // Puede ser null
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $imagen->move(public_path('treematerialesmo'), $nombreImagen);


    // Retornar una respuesta
    return response()->json(['success' => 'Cuenta agregada exitosamente.']);
}
public function update(Request $request, Treematerialescategoria $cuentaContable)
{
    try {
        $fkTienda = session('user_fkTienda');

        $request->validate([
            'nombre_edit' => 'required',
            'cuenta_id_edit' => 'required'
        ], [
            'nombre_edit.required' => 'El nombre es obligatorio.',
            'cuenta_id_edit.required' => 'El SKU es obligatorio.'
        ]);

        $datos = [
            'nombre' => $request->nombre_edit,
            'SKU' => $request->cuenta_id_edit,
            'limite' => $request->lm_edit ?? 0,
            'minimo' => $request->mi_edit ?? 0,
            'obs' => $request->obs_edit ?? ''
        ];

        if ($request->hasFile('foto_edit')) {
            $imagen = $request->file('foto_edit');
            $nombreImagen = time() . '.' . $imagen->getClientOriginalExtension();
            $ruta = $imagen->storeAs('public/treematerialesmo', $nombreImagen);
            $datos['fotografia'] = $ruta;
        }

        DB::beginTransaction();

        Treematerialescategoria::where('id', $request->id_edit)->update($datos);

        DB::commit();
    } catch (Exception $e) {
        DB::rollBack();
        dd($e); // O usar Log::error para producción
    }

    return redirect()->route('treematerialescategoria.index')->with('success', 'treematerialescategoria editado');
}


}
