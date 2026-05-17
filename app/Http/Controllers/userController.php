<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Models\Tecnico;
use Exception;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
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
                        if(!Auth::check()){
            return redirect()->route('login');
        }
    $Estatus = session('user_estatus');
    if ($Estatus == 'ER') {
        $users = User::all();
    }else{
        $fkTienda = session('user_fkTienda');
        $fkusuario = Auth::id();
        $users= User::join('usuario_tienda as ut', 'users.id', '=', 'ut.fkUsuario')
            ->wherein('ut.fkTiendas', function ($query) use ($fkusuario) {
                $query->select('fkTienda')
                    ->from('usuario_tienda')
                    ->where('fkUsuario', $fkusuario);
            })
            ->select('users.*')
            ->distinct()
            ->get();
    }


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
                            if(!Auth::check()){
            return redirect()->route('login');
        }

            $fkTienda = session('user_fkTienda');
            DB::beginTransaction();

 $imageBase64 = null; // Inicializamos vacío

if ($request->hasFile('image')) {
    $file = $request->file('image');
    
    // 1. Inicializa el manager con el driver de GD correctamente
    $manager = new ImageManager(new GdDriver());

    // 2. Lee, procesa y convierte la imagen a formato WebP
    $processedImage = $manager->read($file->getPathname())
        ->resize(300, 300, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })
        ->toWebp(50); // Compresión al 50% de calidad

    // 3. CORREGIDO: Convierte el resultado final a una cadena Data URI / Base64 limpia
    $imageBase64 = $processedImage->toDataUri(); 
    // Esto genera una estructura válida: "data:image/webp;base64,iVBORw0KG..."
}

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
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $roles = Role::all();
        return view('user.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
{
    try {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        DB::beginTransaction();

        $data = $request->all();

        // --- Actualizar contraseña si viene ---
        if (!empty($request->password)) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

// --- Actualizar imagen si viene ---
if ($request->hasFile('image')) {
    $file = $request->file('image');
    
    // 1. Inicializa el manager con el driver de GD
    $manager = new ImageManager(new GdDriver());

    // 2. Lee, redimensiona y convierte la foto a WebP ligera
    $processedImage = $manager->read($file->getPathname())
        ->resize(300, 300, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })
        ->toWebp(50); // Compresión eficiente al 50%

    // 3. Generamos el Base64 limpio (Data URI completo)
    $base64String = $processedImage->toDataUri();

    // 4. Asignamos el mismo Base64 optimizado a ambos campos del arreglo
    $data['image'] = $base64String;
    $data['logo'] = $base64String;
}


        // --- Actualizar usuario ---
        $user->update($data);

        // --- Actualizar rol ---
        $user->syncRoles([$request->role]);

        DB::commit();

    } catch (Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error: '.$e->getMessage());
    }

    return redirect()->route('users.index')->with('success','Usuario editado correctamente');
}


    /**
     * Remove the specified resource from storage.
     */
public function destroy(string $id)
{
    try {
        $user = User::findOrFail($id);

                
        if ($user->status == 1) {
            User::where('id', $user->id)
                ->update([
                    'status' => 0
                ]);
            $message = 'Usuario eliminado';

                    // Opcional: Quitarle los permisos de técnico si existe
        $tecnico = Tecnico::where('fkuser', $user->id)->first();
        if ($tecnico) {
            $tecnico->update(['especialidad' => 'INACTIVO']); // O lo que prefieras
        }
        } else {
            User::where('id', $user->id)
                ->update([
                    'status' => 1
                ]);
            $message = 'Usuario restaurado';
        }


        // En lugar de borrar, desactivamos
        $user->status = 0; 
        $user->save();

        $rolUser = $user->getRoleNames()->first();
        $user->removeRole($rolUser);



        return redirect()->route('users.index')->with('success', 'Usuario desactivado correctamente.');
    } catch (\Exception $e) {
        return back()->with('error', 'Error al desactivar: ' . $e->getMessage());
    }
}

}
