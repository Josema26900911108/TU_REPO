<?php

namespace App\Http\Controllers;

use App\Models\Centro;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class centroController extends Controller
{
public function index() {
    if(!Auth::check()){
        return redirect()->route('login');
    }

   $fkTienda = session('user_fkTienda');

// 1. Iniciamos la consulta sobre el modelo Centro
$query = Centro::query();

// 2. Aplicamos la lógica compleja de filtrado (el CTE convertido a subquery)
if(session('user_estatus') != 'ER') {
    $query->whereIn('id', function($subquery) use ($fkTienda) {
        $subquery->select('id')
            ->from('centro')
            ->where('fkTienda', $fkTienda)
            ->union(
                DB::table('centro as c')
                    ->select('c.id')
                    ->join('centros_organizacion as co', 'c.id', '=', 'co.fkCentro')
                    ->where('co.fkTiendaDependiente', $fkTienda)
            )
            ->union(
                DB::table('centro as c')
                    ->select('c.id')
                    ->join('centros_organizacion as co', 'c.id', '=', 'co.fkCentro')
                    ->where('co.fkTiendaPrincipal', $fkTienda)
            );
    });
}

// 3. Traemos la relación con tienda y ejecutamos
$centros = $query->with('tienda')->get();


    return view('centro.index', compact('centros'));
}


    public function create(){
        if(!Auth::check()){
            return redirect()->route('login');
        }
        return view('centro.create');
    }


        public static function obtenerAlmacenPrincipal($fkTienda)
    {
        // Buscamos la tienda y cargamos su relación con centro
                $centro = Centro::join('tienda', 'centro.id', '=', 'tienda.fkCentro')
    ->where('tienda.idTienda', $fkTienda) // Filtro importante
    ->select('centro.*', 'tienda.nombre as nombre_tienda')
    ->first();

            return $centro ? $centro->codigo : 'ConfCentro'; 

        
    }

    public function store(Request $request){
        if(!Auth::check()){
            return redirect()->route('login');
        }

        $tiendaActual = session('user_fkTienda');

           $request->validate([
        'nombre' => [
            'required',
            \Illuminate\Validation\Rule::unique('centro', 'nombre')
                ->where('fkTienda', $tiendaActual)
        ],
        'codigo' => [
            'required',
            \Illuminate\Validation\Rule::unique('centro', 'codigo')
                ->where('fkTienda', $tiendaActual)
        ],
    ], [
        'nombre.unique' => 'Ya existe un centro con ese nombre en esta tienda.',
        'codigo.unique' => 'Este código ya está registrado en esta tienda.'
    ]);

        try {


            $centros = new Centro();
            $centros->nombre = $request->nombre;
            $centros->codigo = $request->codigo;
            $centros->fkTienda = session('user_fkTienda');

            $centros->save();

            return redirect()->route('centro.index', compact('centros'))->with('success', 'Centro creado exitosamente.');
        } catch (Exception $e) {
            Log::error('Error al crear centro: '.$e->getMessage());
            return back()->withErrors(['error' => 'Ocurrió un error al crear el centro. Inténtalo de nuevo.']);
        }
    }

    function edit(Centro $centro){
        if(!Auth::check()){
            return redirect()->route('login');
        }
        return view('centro.edit', compact('centro'));
    }

    function update(Request $request, Centro $centros){
        if(!Auth::check()){
            return redirect()->route('login');
        }

        try {
            $centros->nombre = $request->nombre;
            $centros->codigo = $request->codigo;

            $centros->save();

            return redirect()->route('centro.index', compact('centros'))->with('success', 'Centro actualizado exitosamente.');
        } catch (Exception $e) {
            Log::error('Error al actualizar centro: '.$e->getMessage());
            return back()->withErrors(['error' => 'Ocurrió un error al actualizar el centro. Inténtalo de nuevo.']);
        }
    }

}
