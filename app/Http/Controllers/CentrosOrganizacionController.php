<?php

namespace App\Http\Controllers;

use App\Models\Centro;
use App\Models\CentrosOrganizacion;
use App\Models\Producto;
use App\Models\Tienda;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CentrosOrganizacionController extends Controller
{
   public function index() {
    if(!Auth::check()){
        return redirect()->route('login');
    }

$fkTienda = session('user_fkTienda');

// 1. Definimos la primera parte: Donde la tienda es DEPENDIENTE
$unionDependiente = DB::table('centros_organizacion as co')
    ->select('co.*')
    ->join('centro as c', 'c.id', '=', 'co.fkCentro')
    ->where('co.fkTiendaDependiente', $fkTienda);

// 2. Definimos la segunda parte: Donde la tienda es PRINCIPAL
$queryBase = DB::table('centros_organizacion as co')
    ->select('co.*')
    ->join('centro as c', 'c.id', '=', 'co.fkCentro')
    ->where('co.fkTiendaPrincipal', $fkTienda)
    ->unionAll($unionDependiente); // Unimos ambas condiciones

// 3. Ahora usamos esa unión como una subconsulta para poder hacer los JOINS finales de nombres
$queryFinal = DB::table(DB::raw("({$queryBase->toSql()}) as sub"))
    ->mergeBindings($queryBase) // Importante para que los IDs no se pierdan
    ->join('tienda as t', 't.idTienda', '=', 'sub.fkTiendaDependiente')
    ->join('centro as c', 'c.id', '=', 'sub.fkCentro')
    ->select(
        'sub.id',
        't.Nombre as Tienda',
        't.EstatusContable',
        'sub.status',
        'c.codigo',
        'c.nombre as Centro'
    );

// 4. Aplicamos el filtro de administrador si es necesario
// (Si no es ER, la subconsulta ya filtró por $fkTienda arriba)
if(session('user_estatus') == 'ER') {
    // Si es ER, quizás quieras ver todo sin el filtro de la subconsulta
    // En ese caso, podrías saltarte la subconsulta y usar tu query original
}

$CentroOrganizacion = $queryFinal->distinct()->get();

$Tiendas = Tienda::where('EstatusContable', 'A')->get();

    return view('centroorganizacion.index', compact('CentroOrganizacion', 'Tiendas'));
}



    public function create(){
        if(!Auth::check()){
            return redirect()->route('login');
        }
        $Tiendas=Tienda::all()->where('EstatusContable','A');
        $centros=Centro::all()->where('fkTienda',session('user_fkTienda'));
        return view('centroorganizacion.create', compact('Tiendas','centros'));
    }

        public function createTraslado(){
        if(!Auth::check()){
            return redirect()->route('login');
        }
        $TiendasPivote=CentrosOrganizacion::all()->where('fkTiendaPrincipal',session('user_fkTienda'));
        
        $TiendasDestino=Tienda::all()->where('EstatusContable','A')
        ->whereIn('idTienda',$TiendasPivote->pluck('fkTiendaDependiente')->toArray());

        $TiendasOrigen=Tienda::all()->where('EstatusContable','A')
        ->whereIn('idTienda',$TiendasPivote->pluck('fkTiendaDependiente')->toArray());
        $Productos=Producto::all()->where('fkTienda',session('user_fkTienda'));
        
        return view('centroorganizacion.traslado', compact('TiendasOrigen','TiendasDestino'));
    }



     public function storeTraslado(Request $request)
{
    // fkTiendaActual es la que envía, fkTiendaDestino es la que recibe
    $request->validate([
        'producto_id' => 'required',
        'cantidad' => 'required|numeric|min:1',
        'fkTiendaDestino' => 'required'
    ]);

    try {
        DB::beginTransaction();

        $tiendaOrigenId = Auth::user()->fkTienda; // Tienda del usuario logueado
        $productoOrigen = Producto::where('id', $request->producto_id)
                                  ->where('fkTienda', $tiendaOrigenId)
                                  ->firstOrFail();

        if ($productoOrigen->stock < $request->cantidad) {
            return back()->withErrors(['error' => 'Stock insuficiente en origen.']);
        }

        // 1. Descontar Stock Origen
        $productoOrigen->decrement('stock', $request->cantidad);

        // 2. Aumentar o Crear Stock en Destino
        // Buscamos si el producto ya existe en la tienda destino por código
        $productoDestino = Producto::where('codigo', $productoOrigen->codigo)
                                   ->where('fkTienda', $request->fkTiendaDestino)
                                   ->first();

        if ($productoDestino) {
            $productoDestino->increment('stock', $request->cantidad);
        } else {
            // Si no existe, lo clonamos a la nueva tienda
            $productoDestino = $productoOrigen->replicate();
            $productoDestino->fkTienda = $request->fkTiendaDestino;
            $productoDestino->stock = $request->cantidad;
            $productoDestino->save();
        }

        // 3. Registrar en Movimiento_Materiales
        DB::table('movimiento_materiales')->insert([
            'fkTienda' => $tiendaOrigenId,
            'fkMateriales' => $productoOrigen->id,
            'clase_movimiento' => '301', // Código estándar para traslados
            'tipo_movimiento' => 'TRASLADO',
            'origen_uso' => 'traslado_entre_bodegas',
            'cantidad' => $request->cantidad,
            'fecha_contabilizacion' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            // Agrega aquí los campos de 'centro' o 'almacen' según tus modelos de Centros
        ]);

        DB::commit();
        return redirect()->back()->with('success', 'Movimiento realizado con éxito.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
    }
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
