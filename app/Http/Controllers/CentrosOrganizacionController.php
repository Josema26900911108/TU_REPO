<?php

namespace App\Http\Controllers;

use App\Models\Centro;
use App\Models\CentrosOrganizacion;
use App\Models\Tienda;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CentrosOrganizacionController extends Controller
{
    public function index(){
        if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');

        $Tiendas=Tienda::all()->where('EstatusContable','A');

$CentroOrganizacion = Tienda::join('centros_organizacion', 'tienda.idTienda', '=', 'centros_organizacion.fkTiendaPrincipal')
    ->join('centro', 'centros_organizacion.fkCentro', '=', 'centro.id')
    ->where('centros_organizacion.fkTiendaPrincipal', $fkTienda)
    ->select(
        'centros_organizacion.id', // <--- ESTE ID ES VITAL
        'tienda.Nombre as Tienda',
        'tienda.EstatusContable',
        'centro.codigo',
        'centro.nombre as Centro',
        'centros_organizacion.status',
    )
    ->get();



        return view('centroorganizacion.index', compact('CentroOrganizacion'));
    }

    public function create(){
        if(!Auth::check()){
            return redirect()->route('login');
        }
        $Tiendas=Tienda::all()->where('EstatusContable','A');
        $centros=Centro::all()->where('fkTienda',session('user_fkTienda'));
        return view('centroorganizacion.create', compact('Tiendas','centros'));
    }

    public function store(Request $request){
        if(!Auth::check()){
            return redirect()->route('login');
        }

        try {
            $centroorganizacion=new CentrosOrganizacion();
            $centroorganizacion->fkTiendaPrincipal = session('user_fkTienda');
            $centroorganizacion->fkTiendaDependiente=$request->fkTiendaDependiente;
            $centroorganizacion->fkCentro=$request->fkCentro;
            $centroorganizacion->status='A';

            $centroorganizacion->save();

            return redirect()->route('centroorganizacion.index', compact('centroorganizacion'))->with('success', 'Centro creado exitosamente.');
        } catch (Exception $e) {
            Log::error('Error al crear centro: '.$e->getMessage());
            return back()->withErrors(['error' => 'Ocurrió un error al crear el centro. Inténtalo de nuevo.']);
        }
    }


// Cambia temporalmente el Type-Hinting por el ID para ver si llega
public function edit($id) {
    if(!Auth::check()){
        return redirect()->route('login');
    }

    // 1. Buscamos el registro específico que queremos editar
    $centroorganizacion = CentrosOrganizacion::findOrFail($id);

    // 2. Traemos las colecciones necesarias para llenar los <select>
    // Asegúrate de usar los mismos modelos que usas en la función 'create'
    $Tiendas = Tienda::all(); // O el modelo que uses para tiendas
    $centros = Centro::all(); // O el modelo que uses para centros

    // 3. Pasamos todo a la vista
    return view('centroorganizacion.edit', compact('centroorganizacion', 'Tiendas', 'centros'));
}

   // Se corrigió el nombre de la variable de $centrooganizacion a $centroorganizacion
public function update(Request $request, CentrosOrganizacion $centroorganizacion) {
    if(!Auth::check()){
        return redirect()->route('login');
    }

    try {
        // Actualizamos el objeto existente
        $centroorganizacion->fkTiendaPrincipal = session('user_fkTienda');
        $centroorganizacion->fkTiendaDependiente = $request->fkTiendaDependiente;
        $centroorganizacion->fkCentro = $request->fkCentro;
        $centroorganizacion->status=$request->status;

        $centroorganizacion->save();


        // En el redirect no necesitas el compact('centros'), con el success basta
        return redirect()->route('centroorganizacion.index')->with('success', 'Centro actualizado exitosamente.');
    } catch (\Exception $e) {
        Log::error('Error al actualizar centro: '.$e->getMessage());
        return back()->withErrors(['error' => 'Ocurrió un error al actualizar el centro.']);
    }
}


}
