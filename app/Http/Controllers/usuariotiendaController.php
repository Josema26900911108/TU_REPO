<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserStoreRequest;
use App\Models\Tienda;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\usuariotienda;
use Exception;
use Illuminate\Support\Facades\DB;
class usuariotiendaController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-usuariotienda|crear-tienda|editar-usuariotienda|eliminar-usuariotienda', ['only' => ['index']]);
        $this->middleware('permission:crear-usuariotienda', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-usuariotienda', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-usuariotienda', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$userstore2 = usuariotienda::all();
        $userstore2 = usuariotienda::with(['user', 'tienda'])->latest()->get();

        $ver=$userstore2;

        return view('userstore.index', compact('userstore2'));
    }

    public function create()
    {
       // $userstore = User::with('users')->latest()->get();
        //$Tienda = Tienda::with('tienda')->latest()->get();
        $userstore = User::all();
        $Tienda=Tienda::all();

        return view('userstore.create',compact('userstore','Tienda'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserStoreRequest $request)
    {

        try {
            DB::beginTransaction();

            // Crear el nuevo permiso
            usuariotienda::create(array_merge($request->validated()));

            // Asignar los permisos asociados
            DB::commit();
            return redirect()->route('userstore.index')->with('success', 'Se asigno usuario a tienda de correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('userstore.create')->with('error', 'Hubo un error al registrar el usuario a tienda.');
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(usuariotienda $userstore)
    {
        $userstore3 = User::all();
        $Tienda=Tienda::all();
        //$userstore3 = usuariotienda::with(['user', 'tienda'])->latest()->get();

        return view('userstore.edit', compact('userstore','userstore3','Tienda'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $idUsuarioTienda)
    {
       try {
            DB::beginTransaction();
$ver=$idUsuarioTienda;
$ver2=$request->Estatus;
            //Actualizar rol
            usuariotienda::where('idUsuarioTienda', $idUsuarioTienda)
                ->update([
                    'fkUsuario' => $request->fkUsuario,
                    'fkTienda' => $request->fkTienda,
                    'Estatus' => $request->Estatus,
                    'FechaActualizacion' => now(),
                ]);


            DB::commit();
        } catch (Exception $e) {
            dd($e);
            DB::rollBack();
        }

        return redirect()->route('userstore.index')->with('success', 'tienda editado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        usuariotienda::where('idUsuarioTienda', $id)->delete();

        return redirect()->route('userstore.index')->with('success', 'tienda eliminado');
    }
}
