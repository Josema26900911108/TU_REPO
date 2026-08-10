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
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver; // O Drivers\Imagick\Driver si usas Imagick
use Intervention\Image\Encoders\WebpEncoder;

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
        // Inicializamos el gestor de Intervention Image V3
        $manager = new ImageManager(new Driver());

        // 2. ⚡ REDUCIR Y COMPRIMIR EL LOGO (IMAGEN)
        if ($request->hasFile('firma_base64') && $request->file('firma_base64')->isValid()) {
            $imageFile = $request->file('firma_base64');
            
            // Leemos la imagen subida, la redimensionamos manteniendo el aspecto
            $logoProcesado = $manager->read($imageFile->path())
                                     ->resize(800, 800, function ($constraint) {
                                         $constraint->aspectRatio();
                                         $constraint->upsize();
                                     });

            // La codificamos en formato WebP con calidad optimizada (70)
            $encodedLogo = $logoProcesado->encode(new WebpEncoder(quality: 70));

            // Guardamos el Base64 ultraligero resultante
            $data['logo'] = base64_encode($encodedLogo->toString());
        }

        // 3. ⚡ REDUCIR Y COMPRIMIR LA FIRMA DIGITAL
        if ($request->has('firma_base64') && !empty($request->input('firma_base64'))) {
            $firmaRaw = $request->input('firma_base64');
            
            // Limpiamos la cabecera del Canvas para obtener el binario puro
            if (str_contains($firmaRaw, ',')) {
                $parts = explode(',', $firmaRaw);
                $firmaBinaria = base64_decode($parts[1]); 
            } else {
                $firmaBinaria = base64_decode($firmaRaw);
            }

            // Leemos los datos binarios de la firma en memoria
            $firmaProcesada = $manager->read($firmaBinaria)
                                       ->resize(600, 600, function ($constraint) {
                                           $constraint->aspectRatio();
                                           $constraint->upsize();
                                       });

            // Codificamos la firma en WebP (ideal para trazos monocromáticos, pesa poquísimo)
            $encodedFirma = $firmaProcesada->encode(new WebpEncoder(quality: 60));

            // Guardamos el Base64 de la firma optimizada
            $data['firma'] = base64_encode($encodedFirma->toString());
        }

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
