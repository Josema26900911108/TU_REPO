<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use App\Models\Permiso;

class permisoController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-permiso|crear-permiso|editar-permiso|eliminar-permiso', ['only' => ['index']]);
        $this->middleware('permission:crear-permiso', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-permiso', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-permiso', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permiso = Permission::all();
        return view('permiso.index', compact('permiso'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('permiso.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name'
        ], [
            'name.required' => 'El nombre del permiso es obligatorio.',
            'name.unique' => 'Este nombre de permiso ya está en uso.'
        ]);

        try {
            DB::beginTransaction();

            // Crear el nuevo permiso
            $permiso = Permission::create(['name' => $request->name]);

            // Asignar los permisos asociados
            $permiso->syncPermissions($request->permission);

            DB::commit();
            return redirect()->route('permiso.index')->with('success', 'Permiso registrado correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('permiso.create')->with('error', 'Hubo un error al registrar el permiso.');
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
    public function edit(Permission $permiso)
    {
        $permisos = Permission::all();
        return view('permiso.edit', compact('permiso', 'permiso'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permiso)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name'
        ], [
            'name.required' => 'El nombre del permiso es obligatorio.',
            'name.unique' => 'Este nombre de permiso ya está en uso.'
        ]);



        try {
            DB::beginTransaction();

            //Actualizar rol
            Permission::where('id', $permiso->id)
                ->update([
                    'name' => $request->name
                ]);

            //Actualizar permisos
            $permiso->syncPermissions($request->permission);

            DB::commit();
        } catch (Exception $e) {
            dd($e);
            DB::rollBack();
        }

        return redirect()->route('permiso.index')->with('success', 'permiso editado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Permission::where('id', $id)->delete();

        return redirect()->route('permiso.index')->with('success', 'permiso eliminado');
    }
}
