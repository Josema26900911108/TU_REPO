<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\Request;
use App\Models\CuentaContable;
use SebastianBergmann\LinesOfCode\Counter;

class CuentaContableController extends Controller
{
    public function index()
    {
        try {
            $ver=session('user_fkTienda');
            $ver2 = DB::table('cuentas_contables')
            ->where('fkTienda', $ver) // Obtener hijos
            ->get();

            if($ver2->isEmpty()){
            $this->createRootCuentasIfNotExist(); // Ensure root nodes exist
        }
            $cuentas = DB::table('cuentas_contables')
            ->whereNull('padre_id') // Obtener cuentas raíz
            ->get();

        // Inicializar un arreglo para las cuentas con hijos
        $cuentasConHijos = [];

        // Obtener hijos para cada cuenta raíz
        foreach ($cuentas as $cuenta) {
            $hijos = DB::table('cuentas_contables')
                ->where('padre_id', $cuenta->id) // Obtener hijos
                ->get();

            // Agregar la cuenta y sus hijos al arreglo
            $cuentasConHijos[] = [
                'cuenta' => $cuenta,
                'hijos' => $hijos,
            ];
        }


            return view('cuentas.index', compact('cuentasConHijos'));
        } catch (\Exception $e) {
            Log::error('Error al crear nodos raíz: ' . $e->getMessage());
            return response()->json(['error' => 'Hubo un error al crear los nodos raíz.'], 500);
        }
    }
    public function show($id)
    {
        // Lógica para mostrar la cuenta contable
    }
    public function createRootCuentasIfNotExist()
    {
        // Check if root nodes already exist

        $rootNodesCount = DB::table('cuentas_contables')
        ->whereNull('padre_id')
        ->where('fkTienda',session('user_fkTienda'))
        ->count();

        // If no root nodes, create "Activo" and "Pasivo"
        if ($rootNodesCount === 0) {
            // Create root node for "Activo"
            $activo = DB::table('cuentas_contables')->insert([
                'nombre' => 'ACTIVO',
                'formula' => '01', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);

            $activo = DB::table('cuentas_contables')->insert([
                'nombre' => 'PASIVO',
                'formula' => '02', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);
               $activo = DB::table('cuentas_contables')->insert([
                'nombre' => 'CAPITAL',
                'formula' => '03', // O puedes usar null si no tienes una fórmula
                'fkTienda' => session('user_fkTienda') // Asegúrate de que este ID existe en la tabla 'tienda'
            ]);

        }
    }
    public function fetch()
    {
        // Obtener todas las cuentas raíz (aquellas que no tienen padre)
        $cuentas = DB::table('cuentas_contables')
            ->whereNull('padre_id') // Filtra las cuentas que no tienen padre
            ->get();

        // Construir el árbol completo de cuentas
        $treeData = [];
        foreach ($cuentas as $cuenta) {
            $treeData[] = $this->buildTreeNode($cuenta);
        }

        return response()->json($treeData);
    }
public function delete(Request $request)
{
    try {
        DB::beginTransaction();

        DB::table('cuentas_contables')
            ->where('id', $request->cid)
            ->delete();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Nodo eliminado correctamente'
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}

    private function buildTreeNode($cuenta)
    {
        // Obtener los hijos de la cuenta actual
        $hijos = DB::table('cuentas_contables')
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
        // Obtenemos las cuentas contables que tienen como padre el ID dado
        $result = DB::table('cuentas_contables')
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
            $sub_array['cuenta_id'] = $row->formula; // Usamos nodeId para cada nodo
            $sub_array['text'] = $row->formula."-".$row->nombre; // Mostrar el nombre de la cuenta
            $sub_array['nombre'] = $row->nombre; // Mostrar el nombre de la cuenta
            $sub_array['nodes'] = $this->get_node_data($row->id); // Recursión para obtener los hijos
            $output[] = $sub_array; // Agregar al array de salida
        }

        return $output;
    }



    // Método auxiliar para construir el árbol
    private function buildTree($cuentas)
    {
        $tree = [];

        foreach ($cuentas as $cuenta) {
            $node = [
                'text' => $cuenta->formula."-".$cuenta->nombre, // El nombre que se mostrará en el árbol
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
        $cuentaPadre = DB::table('cuentas_contables')
            ->where('id', $padreId) // Buscamos por el id del padre, que debe ser idCuenta
            ->first(); // Obtenemos el primer registro

        if ($cuentaPadre) {
            // Obtener todos los hijos de esa cuenta padre
            $hijos = DB::table('cuentas_contables')
                ->where('padre_id', $padreId) // Buscamos por padre_id
                ->count();

            // Generar el nuevo número de cuenta basado en el número de cuenta del padre y la cantidad de hijos
            $nuevoNumeroHijo = str_pad($hijos + 1, 2, '0', STR_PAD_LEFT); // Ej: '01', '02', etc.

            // Formatear el número de cuenta (Ej: ##.##.##.##)
            $nuevoNumeroCuenta = $cuentaPadre->formula . '.' . $nuevoNumeroHijo;

            return response()->json(['nuevoNumeroCuenta' => $nuevoNumeroCuenta]);
        }

        return response()->json(['error' => 'No se encontró la cuenta padre.'], 404);
    }


    public function fillParentCategory()
    {
        $cuentas = DB::table('cuentas_contables')
        ->get();

        // Crear un arreglo para enviar como JSON
        $options = [];
        foreach ($cuentas as $cuenta) {
            $options[] = [
                'id' => $cuenta->id,
                'nombre' => $cuenta->nombre,
            ];
        }

        return response()->json($options); // Retornar JSON
    }

public function ReporteDiario(Request $request)
{
 try {
     $idTienda=session('user_fkTienda');
     $query = DB::table('Folio AS f')
     ->join('DetalleFolio AS df', 'f.idFolio', '=', 'df.fkFolio')
     ->join('cuentas_contables AS cc', 'df.fkCuenetaContable', '=', 'cc.id')
     ->select('date(f.FechaContabilizacion) as Fecha',
     'f.idFolio',
     'f.cabecera',
     'f.descripcion',
     'f.idOrigen',
     'f.TipoMovimiento',
     'df.idDetalleFolio',
     'df.Monto',
     'df.Naturaleza',
     'df.fkCuenetaContable',
     'cc.formula',
     'cc.nombre',
     "SUM(CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE 0 END)
        OVER (PARTITION BY f.idFolio) AS HaberTotal",
"SUM(CASE WHEN df.Naturaleza = 'D' THEN df.Monto ELSE 0 END)
        OVER (PARTITION BY f.idFolio) AS DebeTotal"
     )
     ->where('fkTienda', $idTienda);
         // Filtro fecha inicio
    if ($request->inicio) {
        $query->whereDate('fecha_hora', '>=', $request->inicio);
    }

    // Filtro fecha fin
    if ($request->fin) {
        $query->whereDate('fecha_hora', '<=', $request->fin);
    }
     $query->orderBy('f.idFolio', 'desc')
     ->orderBy('df.idDetalleFolio', 'desc')
     ->get();

     return view('cuentas.reporteDiario');

}catch (\Exception $e) {
     Log::error('Error al crear nodos raíz: ' . $e->getMessage());
     DB::rollBack();
     return response()->json(['error' => 'Hubo un error al crear los nodos raíz.'], 500);
 }


}
public function add(Request $request)
{


    $cuenta=DB::table('cuentas_contables')->insert([
        'nombre' => $request->input('nombre_new'),
        'padre_id' => $request->input('padre_id'), // Puede ser null
        'formula' => $request->input('cuenta_id_new'),
        'fkTienda' => session('user_fkTienda'), // Puede ser null
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Retornar una respuesta
    return response()->json(['success' => 'Cuenta agregada exitosamente.']);
}
public function update(Request $request, CuentaContable $cuentaContable)
{


    try {
        DB::beginTransaction();

DB::table('cuentas_contables')
    ->where('id', $request->input('id_edit'))
    ->update([
        'formula'    => $request->input('cuenta_id_edit'),
        'nombre'     => $request->input('nombre_edit'),
        'updated_at' => now(),
    ]);



DB::commit();

        return response()->json(['success' => 'Cuenta modificada exitosamente.']);

    } catch (\Exception $e) {

        DB::rollBack();
        return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
    }
}



}
