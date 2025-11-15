<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Exception;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Crypt;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
class userController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-user|crear-user|editar-user|eliminar-user', ['only' => ['index']]);
        $this->middleware('permission:crear-user', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-user', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-user', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function getTiendasByEmail(Request $request)
    {
        $email = $request->email;

        // Verificar que el correo electrónico no esté vacío
        if (empty($email)) {
            return response()->json(['message' => 'El correo electrónico no puede estar vacío.'], 400);
        }

        // Suponiendo que la tabla `usuario_tienda` tiene el campo `fkUsuario`
        $tiendas = DB::table('users as u')
            ->join('usuario_tienda as ut', 'u.id', '=', 'ut.fkUsuario')
            ->join('tienda as t', 'ut.fkTienda', '=', 't.idTienda')
            ->where('u.email', $email)
            ->whereNotIn('ut.Estatus', ['EB', 'EI']) // Excluir estados
            ->select('t.Nombre', 't.idTienda') // Seleccionar los campos necesarios
            ->distinct()
            ->get();

        // Comprobar si se encontraron tiendas
        if ($tiendas->isEmpty()) {
            return response()->json(['message' => 'No se encontraron tiendas para el usuario'], 404);
        }

        // Opcional: si quieres almacenar los nombres en una variable
        $nombresTiendas = $tiendas->pluck('Nombre');

        return response()->json($tiendas);
    }
    public function index()
    {
        $users = User::all();
        return view('user.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all();
        return view('user.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        try {
            $fkTienda = session('user_fkTienda');
            DB::beginTransaction();

             // Opción 1: Convertir imagen a Base64 desde el archivo subido
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageBase64 = base64_encode(file_get_contents($image->path()));
            }
                 $file = $request->file('image');
    $manager = new ImageManager(new GdDriver());


// Luego manipulas la imagen
    $image = $manager->read($file->getPathname())
        ->resize(300, 300, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })
        ->toWebp(50); // calidad 50%

        $imageBase64 = (string) $image;

            //Encriptar contraseña
            $fieldHash = Hash::make($request->password);
            //Modificar el valor de password en nuestro request
            $request->merge(['password' => $fieldHash]);

            //Crear usuario
            $user = User::create(array_merge($request->all(), ['fkTienda' => $fkTienda], ['logo'=>$imageBase64]));
            //Asignar su rol
            $user->assignRole($request->role);

            DB::commit();
        } catch (Exception $e) {
            dd('Error: '.$e->getMessage());
            DB::rollBack();
        }

        return redirect()->route('users.index')->with('success', 'usuario registrado');
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
    public function edit(User $user)
    {
        $roles = Role::all();
        return view('user.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            DB::beginTransaction();

            /*Comprobar el password y aplicar el Hash*/
            if (empty($request->password)) {
                $request = Arr::except($request, array('password'));
            } else {
                $fieldHash = Hash::make($request->password);
                $request->merge(['password' => $fieldHash]);
            }

                // Si se subió una nueva imagen, actualiza la foto
    if ($request->hasFile('foto')) {
        if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageBase64 = base64_encode(file_get_contents($image->path()));
            }

            //Encriptar contraseña
            $fieldHash = Hash::make($request->password);
            //Modificar el valor de password en nuestro request
            $request->merge(['password' => $fieldHash]);
    }else{

            $user->update(array_merge($request->all()));
    }
            /**Actualizar rol */
            $user->syncRoles([$request->role]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
        }

        return redirect()->route('users.index')->with('success','Usuario editado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        //Eliminar rol
        $rolUser = $user->getRoleNames()->first();
        $user->removeRole($rolUser);

        //Eliminar usuario
        $user->delete();

        return redirect()->route('users.index')->with('success','Usuario eliminado');
    }
}
