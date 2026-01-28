<?php

namespace App\Http\Controllers;

use App\Models\Arbolmateriales;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\Request;
use App\Models\CuentaContable;
use SebastianBergmann\LinesOfCode\Counter;

class ArbolMaterialesController extends Controller
{
    public function index()
    {
        try {
            $ver=session('user_fkTienda');
            $ver2 = DB::table('arbolmaterial')
            ->where('fkTienda', $ver) // Obtener hijos
            ->get();

            if($ver2->isEmpty()){
            $this->createRootarbolmaterialesIfNotExist(); // Ensure root nodes exist
        }
            $arbolmateriales = DB::table('arbolmaterial')
            ->whereNull('padre_id') // Obtener arbolmateriales raíz
            ->get();

        // Inicializar un arreglo para las arbolmateriales con hijos
        $arbolmaterialesConHijos = [];

        // Obtener hijos para cada cuenta raíz
        foreach ($arbolmateriales as $cuenta) {
            $hijos = DB::table('arbolmaterial')
                ->where('padre_id', $cuenta->id) // Obtener hijos
                ->get();

            // Agregar la cuenta y sus hijos al arreglo
            $arbolmaterialesConHijos[] = [
                'cuenta' => $cuenta,
                'hijos' => $hijos,
            ];
        }


            return view('arbolmaterial.index', compact('arbolmaterialesConHijos'));
        } catch (\Exception $e) {
            Log::error('Error al crear nodos raíz: ' . $e->getMessage());
            return response()->json(['error' => 'Hubo un error al crear los nodos raíz.'], 500);
        }
    }
    public function show($id)
    {
        // Lógica para mostrar la cuenta contable
    }
    public function createRootarbolmaterialesIfNotExist()
    {
        // Check if root nodes already exist

        $rootNodesCount = DB::table('arbolmaterial')
        ->whereNull('padre_id')
        ->where('fkTienda',session('user_fkTienda'))
        ->count();

        // If no root nodes, create "Activo" and "Pasivo"
        if ($rootNodesCount === 0) {
            // Create root node for "Activo"
            $activo = DB::table('arbolmaterial')->insert([
                'nombre' => 'xDSL',
                'SKU' => '01', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);

            $activo = DB::table('arbolmaterial')->insert([
                'nombre' => 'DTH',
                'SKU' => '02', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);

            $activo = DB::table('arbolmaterial')->insert([
                'nombre' => 'HFC',
                'SKU' => '03', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);
            $activo = DB::table('arbolmaterial')->insert([
                'nombre' => 'GPON',
                'SKU' => '04', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);
            $activo = DB::table('arbolmaterial')->insert([
                'nombre' => 'COBRE',
                'SKU' => '05', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);
            $activo = DB::table('arbolmaterial')->insert([
                'nombre' => 'CONECTIVIDAD WIFI',
                'SKU' => '06', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);
            $activo = DB::table('arbolmaterial')->insert([
                'nombre' => 'WTTx',
                'SKU' => '07', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);

        }
    }
    public function fetch()
    {
        // Obtener todas las arbolmateriales raíz (aquellas que no tienen padre)
        $arbolmateriales = DB::table('arbolmaterial')
            ->whereNull('padre_id') // Filtra las arbolmateriales que no tienen padre
            ->get();

        // Construir el árbol completo de arbolmateriales
        $treeData = [];
        foreach ($arbolmateriales as $cuenta) {
            $treeData[] = $this->buildTreeNode($cuenta);
        }

        return response()->json($treeData);
    }
    public function delete(Request $request, Arbolmateriales $cuentaContable){
    try{
            DB::beginTransaction();
            Arbolmateriales::where('id',$request->id_delete)->delete();

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
        $hijos = DB::table('arbolmaterial')
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
            $sub_array['ts_edit'] = $row->Tipo_servicio; // Mostrar el nombre de la cuenta
            $sub_array['fotografia'] = $row->aplicafotografia; // Mostrar el nombre de la cuenta
            $sub_array['nodes'] = $this->get_node_data($row->id); // Recursión para obtener los hijos
            $sub_array['idpivote'] = $row->idpivote; // Recursión para obtener los hijos
            $output[] = $sub_array; // Agregar al array de salida
        }

        return $output;
    }



    // Método auxiliar para construir el árbol
    private function buildTree($arbolmateriales)
    {
        $tree = [];

        foreach ($arbolmateriales as $cuenta) {
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
        $cuentaPadre = DB::table('arbolmaterial')
            ->where('id', $padreId) // Buscamos por el id del padre, que debe ser idCuenta
            ->first(); // Obtenemos el primer registro

        if ($cuentaPadre) {
            // Obtener todos los hijos de esa cuenta padre
            $hijos = DB::table('arbolmaterial')
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
            $arbolmateriales = DB::table('arbolmaterial')
        ->get();

        // Crear un arreglo para enviar como JSON
        $options = [];
        foreach ($arbolmateriales as $cuenta) {
            $options[] = [
                'id' => $cuenta->id,
                'nombre' => $cuenta->nombre,
            ];
        }

        return response()->json($options); // Retornar JSON
    }

        public function fillEstructura()
    {
try {
        $fkTienda = session('user_fkTienda');
        $pdo = DB::getPdo();
        $sqlll='
        SELECT concat(am.SKU," - ",am.nombre," || ",amo.SKU," - ",amo.nombre) as nombre, amo.id, amo.SKU FROM arbolmanoobra as amo
        inner join (select ams.id, ams.SKU, ams.nombre from arbolmanoobra as ams where isnull(ams.padre_id)) AS am on am.id=amo.padre_id where amo.fkTienda=:id
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

    $cuenta=DB::table('arbolmaterial')->insert([
        'nombre' => $request->input('nombre_new'),
        'padre_id' => $request->input('padre_id'), // Puede ser null
        'SKU' => $request->input('cuenta_id_new'),
        'Tipo_servicio' => $request->input('ts_new'),
        'Tipo_orden' => $request->input('to_new'),
        'aplicafotografia' => $request->input('af_new'),
        'obs' => $request->input('obs_new'),
        'fkTienda' => session('user_fkTienda'), // Puede ser null
        'idpivote'=> $request->input('itemmamo_new'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Retornar una respuesta
    return response()->json(['success' => 'Cuenta agregada exitosamente.']);
}
public function update(Request $request, Arbolmateriales $cuentaContable)
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

                Arbolmateriales::where('id', $request->id_edit)
                ->update([
                    'nombre' => $request->nombre_edit,
                    'SKU' => $request->cuenta_id_edit,
                    'Tipo_servicio' => $request->ts_edit ?? '',
                    'Tipo_orden' => $request->to_edit ?? '',
                    'aplicafotografia' => $request->af_edit ?? '',
                    'obs' => $request->obs_edit ?? '',
                    'idpivote'=> $request->input('itemmamo_edit') ?? null
                ]);


            DB::commit();
        } catch (Exception $e) {
            dd($e);
            DB::rollBack();
        }

        return redirect()->route('permiso.index')->with('success', 'permiso editado');
    }

}
