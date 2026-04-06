<?php

namespace App\Http\Controllers;

use App\Http\Requests\loginRequest;
use App\Models\Tienda;
use Illuminate\Support\Facades\Auth;

class loginController extends Controller
{
    public function index(){

        return view('auth.login');
    }

    public function login(loginRequest $request)
    {

    if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'status' => 1])) {
        // Validar credenciales
        if (!Auth::validate($request->only('email', 'password'))) {
            return redirect()->to('login')->withErrors('Credenciales incorrectas');
        }

        // Crear una sesión
        $user = Auth::getProvider()->retrieveByCredentials($request->only('email', 'password'));
        Auth::login($user);
        $user->idtienda=$request->idTienda;

        $centro = Tienda::join('centro', 'tienda.idTienda', '=', 'centro.fkTienda')
            ->where('tienda.idTienda', $request->idTienda)
            ->select('centro.codigo')
            ->first();
        
        // Obtener información adicional, como la tienda a la que está asignado el usuario
        // Supongamos que el usuario tiene una relación con la tienda
        $tienda = $request->idTienda; // Aquí asumiendo que hay una relación definida

        // Almacenar en la sesión

        session([
            'nombreUsuario' => $user->name,
            'user_fkTienda' => $tienda ?? null,
            'idTienda' => $tienda ?? null,
            'logo' => $tienda ?? null,
            'centro' => ($centro && $centro->codigo) ? $centro->codigo : null,
        ]);

            return redirect()->route('panel')->with('success', 'Bienvenido ' . $user->name);
            } else {
                
                return redirect()->route('login')->with('Error', 'No se pudo iniciar sesion favor validar Correo, Contraseña, y validar si usuario esta activo en sistema.');
 
}
    }

}
