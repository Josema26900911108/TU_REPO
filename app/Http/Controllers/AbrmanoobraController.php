<?php

namespace App\Http\Controllers;

use App\Models\Arbmanoobra;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SebastianBergmann\LinesOfCode\Counter;

class AbrmanoobraController extends Controller
{
    public function index()
    {
        try {

                if(!Auth::check()){
            return redirect()->route('login');
        }

            $ver=session('user_fkTienda');
            $ver2 = DB::table('arbolmanoobra')
            ->where('fkTienda', $ver) // Obtener hijos
            ->get();

            if($ver2->isEmpty()){
            $this->createRootArbmanoobraIfNotExist(); // Ensure root nodes exist
        }
            $Arbmanoobra = DB::table('arbolmanoobra')
            ->whereNull('padre_id') // Obtener Arbmanoobra raíz
            ->get();

        // Inicializar un arreglo para las Arbmanoobra con hijos
        $ArbmanoobraConHijos = [];

        // Obtener hijos para cada cuenta raíz
        foreach ($Arbmanoobra as $cuenta) {
            $hijos = DB::table('arbolmanoobra')
                ->where('padre_id', $cuenta->id) // Obtener hijos
                ->get();

            // Agregar la cuenta y sus hijos al arreglo
            $ArbmanoobraConHijos[] = [
                'cuenta' => $cuenta,
                'hijos' => $hijos,
            ];
        }


            return view('arbolmanoobra.index', compact('ArbmanoobraConHijos'));
        } catch (\Exception $e) {
            Log::error('Error al crear nodos raíz: ' . $e->getMessage());
            return response()->json(['error' => 'Hubo un error al crear los nodos raíz.'], 500);
        }
    }
    public function show($id)
    {
        // Lógica para mostrar la cuenta contable
    }
    public function moveNode(Request $request)
{
    try {
        $request->validate([
            'node_id' => 'required|integer',
            'new_parent_id' => 'nullable|integer'
        ]);
$idnodo=$request->node_id;
$idnodopadre=$request->new_parent_id;

            if ($idnodo==$idnodopadre) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede mover al mismo nodo como padre'
                ], 400);
            }
        $node = Arbmanoobra::findOrFail($request->node_id);

        // Validar que no se esté moviendo a un hijo (evitar ciclos)
        if ($request->new_parent_id) {
            $newParent = Arbmanoobra::findOrFail($request->new_parent_id);

                        // Verificar si el nuevo padre es un descendiente del nodo
            if (is_null($node->padre_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede mover un nodo nodo raiz'
                ], 400);
            }

        }

        // Actualizar el padre
        $node->padre_id = $idnodopadre;
        $node->save();

        return response()->json([
            'success' => true,
            'message' => 'Nodo movido correctamente'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}
    public function createRootArbmanoobraIfNotExist()
    {

                    if(!Auth::check()){
            return redirect()->route('login');
        }

        // Check if root nodes already exist

        $rootNodesCount = DB::table('arbolmanoobra')
        ->whereNull('padre_id')
        ->where('fkTienda',session('user_fkTienda'))
        ->count();

        // If no root nodes, create "Activo" and "Pasivo"
        if ($rootNodesCount === 0) {
            // Create root node for "Activo"
            $activo = DB::table('arbolmanoobra')->insert([
                'nombre' => 'xDSL',
                'SKU' => '01', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);

            $activo = DB::table('arbolmanoobra')->insert([
                'nombre' => 'DTH',
                'SKU' => '02', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);

            $activo = DB::table('arbolmanoobra')->insert([
                'nombre' => 'HFC',
                'SKU' => '03', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);
            $activo = DB::table('arbolmanoobra')->insert([
                'nombre' => 'GPON',
                'SKU' => '04', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);
            $activo = DB::table('arbolmanoobra')->insert([
                'nombre' => 'COBRE',
                'SKU' => '05', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);
            $activo = DB::table('arbolmanoobra')->insert([
                'nombre' => 'CONECTIVIDAD WIFI',
                'SKU' => '06', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);
            $activo = DB::table('arbolmanoobra')->insert([
                'nombre' => 'WTTx',
                'SKU' => '07', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);

        }
    }
    public function fetch()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }
        // Obtener todas las Arbmanoobra raíz (aquellas que no tienen padre)
        $Arbmanoobra = DB::table('arbolmanoobra')
            ->whereNull('padre_id') // Filtra las Arbmanoobra que no tienen padre
            ->get();

        // Construir el árbol completo de Arbmanoobra
        $treeData = [];
        foreach ($Arbmanoobra as $cuenta) {
            $treeData[] = $this->buildTreeNode($cuenta);
        }

        return response()->json($treeData);
    }
    public function delete(Request $request, Arbmanoobra $cuentaContable){
    try{
            DB::beginTransaction();
            Arbmanoobra::where('id',$request->id_delete)->delete();

                DB::commit();

            } catch (Exception $e) {
                dd($e);
                DB::rollBack();
            }

            return response()->json(['success' => 'Cuenta agregada exitosamente.']);
    }


        public function importarmasivohijospadresMAMO(Request $request)
{
      DB::connection()->disableQueryLog();
      
    $request->validate([
        'archivohijospadres' => 'required|file|mimes:csv,txt,xls,xlsx', // Agregamos soporte genérico de texto/csv
    ]);

    $idTienda = session('user_fkTienda');
    $file = fopen($request->file('archivohijospadres')->getRealPath(), 'r');
    $encabezado = fgetcsv($file); 

    DB::beginTransaction();

    try {
        while (($linea = fgetcsv($file)) !== false) {
            // Evita desfases si hay líneas en blanco
            if (count($encabezado) !== count($linea)) continue;
            
            $data = array_combine($encabezado, $linea);

            // Validar campos mínimos obligatorios
            if (!isset($data['SKU']) || !isset($data['nombre'])) continue;

            // Convertir codificación para evitar caracteres rotos (¿¿ o )
            $nombre = mb_convert_encoding($data['nombre'] ?? '', 'UTF-8', 'UTF-8, ISO-8859-1');
            $SKU = str_replace("'", "", trim($data['SKU'] ?? ''));
            $Tipo_servicio    = mb_convert_encoding($data['Tipo_servicio'] ?? '', 'UTF-8', 'UTF-8, ISO-8859-1');
            $Tipo_orden    = mb_convert_encoding($data['Tipo_orden'] ?? '', 'UTF-8', 'UTF-8, ISO-8859-1');
            $aplicafotografia    = mb_convert_encoding($data['aplicafotografia'] ?? '', 'UTF-8', 'UTF-8, ISO-8859-1');
            $obs    = mb_convert_encoding($data['obs'] ?? '', 'UTF-8', 'UTF-8, ISO-8859-1');
            $skuPadre = str_replace("'", "", trim($data['SKUPADRE'] ?? ''));

            // VALIDACIÓN CRÍTICA: Buscar el ID del padre de forma segura
            $padreId = null;
            if (!empty($skuPadre) && strtoupper($skuPadre) !== 'NULL') {
                $padreNode = Arbmanoobra::where('SKU', $skuPadre)
                    ->where('fkTienda', $idTienda)
                    ->first();

                if ($padreNode) {
                    $padreId = $padreNode->id;
                }
            }

            DB::table('arbolmanoobra')->updateOrInsert(
                [
                    'fkTienda' => $idTienda,
                    'SKU'      => $SKU,
                    'created_at' => now(), 
                ],
                [
                    'nombre'     => $nombre,
                    'SKU' => $SKU,
                    'obs'        => $obs,
                    'Tipo_servicio'     => $Tipo_servicio,
                    'Tipo_orden'     => $Tipo_orden,
                    'aplicafotografia'     => $aplicafotografia,
                    'padre_id'   => $padreId,
                    'updated_at' => now(),
                ]
            );
        }

        fclose($file);
        DB::commit();
        return back()->with('success', 'Se han importado los datos para el árbol de validación de forma exitosa.');

    } catch (\Exception $e) {
        if ($file) fclose($file);
        DB::rollBack();
        return back()->with('error', 'Error al importar: ' . $e->getMessage());
    }
}

    public function descargarFormHijosPadresMAMO()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=Formato Catalogo MA/MO Masivo.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columnas = ['nombre',	'SKU',	'Tipo_servicio',	'Tipo_orden',	'aplicafotografia',	'obs',	'SKUPADRE'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        $fkTienda = session('user_fkTienda') ?? 0;
        // Línea de ejemplo opcional:
        fputcsv($file, ['ROSETA POUYET',	'01.01',	'MA',	'',	'SI',	'',	'01']);
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}


    private function buildTreeNode($cuenta)
    {
        // Obtener los hijos de la cuenta actual
        $hijos = DB::table('arbolmanoobra')
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
        // Obtenemos las Arbmanoobra contables que tienen como padre el ID dado
        $result = DB::table('arbolmanoobra')
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
            $sub_array['ts_edit'] = $row->Tipo_orden; // Mostrar el nombre de la cuenta
            $sub_array['af_edit'] = $row->aplicafotografia; // Mostrar el nombre de la cuenta
            $sub_array['nodes'] = $this->get_node_data($row->id); // Recursión para obtener los hijos
            $output[] = $sub_array; // Agregar al array de salida
        }

        return $output;
    }



    // Método auxiliar para construir el árbol
    private function buildTree($Arbmanoobra)
    {
        $tree = [];

        foreach ($Arbmanoobra as $cuenta) {
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
        $cuentaPadre = DB::table('arbolmanoobra')
            ->where('id', $padreId) // Buscamos por el id del padre, que debe ser idCuenta
            ->first(); // Obtenemos el primer registro

        if ($cuentaPadre) {
            // Obtener todos los hijos de esa cuenta padre
            $hijos = DB::table('arbolmanoobra')
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
            $Arbmanoobra = DB::table('arbolmanoobra')
        ->get();

        // Crear un arreglo para enviar como JSON
        $options = [];
        foreach ($Arbmanoobra as $cuenta) {
            $options[] = [
                'id' => $cuenta->id,
                'nombre' => $cuenta->nombre,
            ];
        }

        return response()->json($options); // Retornar JSON
    }


public function add(Request $request)
{
        
    $request->validate([
        'nombre_new' => 'required',
        'cuenta_id_new' => 'required',
    ]);

    $cuenta=DB::table('arbolmanoobra')->insert([
        'nombre' => $request->input('nombre_new'),
        'padre_id' => $request->input('padre_id'), // Puede ser null
        'SKU' => $request->input('cuenta_id_new'),
        'Tipo_servicio' => $request->input('ts_new'),
        'Tipo_orden' => $request->input('to_new'),
        'aplicafotografia' => $request->input('af_new'),
        'obs' => $request->input('obs_new'),
        'fkTienda' => session('user_fkTienda'), // Puede ser null
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Retornar una respuesta
    return response()->json(['success' => 'Cuenta agregada exitosamente.']);
}
public function update(Request $request, Arbmanoobra $cuentaContable)
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




                DB::beginTransaction();

                Arbmanoobra::where('id', $request->id_edit)
                ->update([
                    'nombre' => $request->nombre_edit,
                    'SKU' => $request->cuenta_id_edit,
                    'Tipo_servicio' => $request->ts_edit ?? '',
                    'Tipo_orden' => $request->to_edit ?? '',
                    'aplicafotografia' => $request->af_edit ?? '',
                    'obs' => $request->obs_edit ?? ''
                ]);


            DB::commit();
        } catch (Exception $e) {
            dd($e);
            DB::rollBack();
        }

        return redirect()->route('permiso.index')->with('success', 'permiso editado');
    }

}
