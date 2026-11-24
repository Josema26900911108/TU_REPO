<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class roleController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-role|crear-role|editar-role|eliminar-role', ['only' => ['index']]);
        $this->middleware('permission:crear-role', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-role', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-role', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $roles = Role::all();
        return view('role.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $permisos = Permission::all();
        return view('role.create', compact('permisos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $request->validate([
            'name' => 'required|unique:roles,name',
            'permission' => 'required'
        ]);

        try {
            $fkTienda = session('user_fkTienda');

            DB::beginTransaction();
            //Crear rol
            $rol = Role::create(['name' => $request->name, 'fkTienda' => $fkTienda]);

            //Asignar permisos
            $rol->syncPermissions($request->permission);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
        }


        return redirect()->route('roles.index')->with('success', 'Rol registrado');
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
    public function edit(Role $role)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $permisos = Permission::all();
        return view('role.edit', compact('role', 'permisos'));
    }

    /**
     * Update the specified resource in storage.
     */
public function update(Request $request, Role $role)
{
    if(!Auth::check()){
        return redirect()->route('login');
    }

    $request->validate([
        'name' => 'required|unique:roles,name,' . $role->id,
        'permission' => 'required'
    ]);

    try {
        DB::beginTransaction();

        // 1. Actualizar el nombre del rol
        $role->update([
            'name' => $request->name
        ]);

        // 2. MAGIA AQUÍ: Convertir los IDs que vienen del formulario a Nombres
        // Si el request trae IDs numéricos, buscamos sus nombres en la BD.
        $permisosParaSincronizar = [];
        foreach ($request->permission as $permiso) {
            // Si es un número (ID), lo buscamos y sacamos su nombre.
            if (is_numeric($permiso)) {
                $permisoObj = Permission::find($permiso);
                if ($permisoObj) {
                    $permisosParaSincronizar[] = $permisoObj->name;
                }
            } else {
                // Si ya viene el nombre (por ejemplo 'crear-mb51'), lo dejamos igual.
                $permisosParaSincronizar[] = $permiso;
            }
        }

        // 3. Sincronizar con los NOMBRES correctos
        $role->syncPermissions($permisosParaSincronizar);

        DB::commit();
    } catch (Exception $e) {
        DB::rollBack();
        // Quitamos el dd($e) para que no se congele la pantalla y mostramos un error amigable
        return back()->with('error', 'Error al actualizar el rol: ' . $e->getMessage());
    }

    return redirect()->route('roles.index')->with('success', 'Rol editado correctamente');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        Role::where('id', $id)->delete();

        return redirect()->route('roles.index')->with('success', 'rol eliminado');
    }
}
