<?php

namespace App\Http\Controllers;

use App\Models\Centro;
use App\Models\Tienda;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class profileController extends Controller
{

    function __construct()
    {
        $this->middleware('permission:ver-perfil', ['only' => ['index']]);
        $this->middleware('permission:editar-perfil', ['only' => ['update']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $user = User::find(Auth::user()->id);
        $fkTienda = session('user_fkTienda');
        $tienda=Tienda::find($fkTienda);
        
$centro = Centro::join('tienda', 'centro.id', '=', 'tienda.fkCentro')
    ->where('tienda.idTienda', session('user_fkTienda')) // Filtro importante
    ->select('centro.*', 'tienda.nombre as nombre_tienda')
    ->first();

        return view('profile.index', compact('user', 'tienda','centro'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
public function update(Request $request, User $profile)
{
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    
    $request->validate([
        'name' => 'required',
        'email' => 'required|unique:users,email,' . $profile->id,
        'password' => 'nullable',
        'firma_base64' => 'nullable' // Añadimos la validación del campo de la firma
    ]);

    // Obtener todos los datos de la petición en un arreglo para poder manipularlos
    $data = $request->all();

    /* 1. Comprobar el password y aplicar el Hash */
    if (empty($request->password)) {
        $data = Arr::except($data, array('password'));
    } else {
        $data['password'] = Hash::make($request->password);
    }

    /* 2. Procesar y limpiar la firma digital en Base64 */
    if ($request->has('firma_base64') && !empty($request->input('firma_base64'))) {
        $firmaRaw = $request->input('firma_base64');
        
        // Si el string contiene la cabecera "data:image/png;base64,", la cortamos
        if (str_contains($firmaRaw, ',')) {
            $parts = explode(',', $firmaRaw);
            $data['firma'] = trim($parts[1]); // Guardamos estrictamente el código Base64 puro
        } else {
            $data['firma'] = trim($firmaRaw);
        }
    }

    // 3. Excluir el campo temporal "firma_base64" para que no choque con las columnas de SQL
    $data = Arr::except($data, array('firma_base64'));

    // 4. Actualizar el perfil del usuario de forma masiva y segura
    $profile->update($data);

    return redirect()->route('profile.index')->with('success', 'Cambios guardados con éxito.');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
