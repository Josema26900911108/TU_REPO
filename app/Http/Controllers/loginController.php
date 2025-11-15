<?php

namespace App\Http\Controllers;

use App\Http\Requests\loginRequest;
use Illuminate\Support\Facades\Auth;

class loginController extends Controller
{
    public function index(){
        if(Auth::check()){
            return redirect()->route('panel');
        }
        return view('auth.login');
    }

    public function login(loginRequest $request)
    {
        // Validar credenciales
        if (!Auth::validate($request->only('email', 'password'))) {
            return redirect()->to('login')->withErrors('Credenciales incorrectas');
        }

        // Crear una sesión
        $user = Auth::getProvider()->retrieveByCredentials($request->only('email', 'password'));
        Auth::login($user);
        $user->idtienda=$request->idTienda;
        // Obtener información adicional, como la tienda a la que está asignado el usuario
        // Supongamos que el usuario tiene una relación con la tienda
        $tienda = $request->idTienda; // Aquí asumiendo que hay una relación definida

        // Almacenar en la sesión

        session([
            'nombreUsuario' => $user->name,
            'user_fkTienda' => $tienda ? $tienda : null, // Verificamos si hay una tienda asignada
            'idTienda' => $tienda ? $tienda : null, // Verificamos si hay una tienda asignada
            'logo' => $tienda ? $tienda : null, // Verificamos si hay una tienda asignada
        ]);

            return redirect()->route('panel')->with('success', 'Bienvenido ' . $user->name);
    }

}
