<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class UserStoreMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        try{
        // Verificar si el usuario está autenticado
        if (Auth::check()) {
            // Obtener el usuario autenticado
            $user = Auth::user();
            $userId = Auth::id();
            $tiendaId = session('user_fkTienda');

            // Obtener los datos necesarios (puedes ajustar la consulta según tu estructura)
            $userStoreData = DB::table('users as u')
                ->join('usuario_tienda as ut', 'u.id', '=', 'ut.fkUsuario')
                ->select('u.email', 'ut.fkUsuario', 'ut.Estatus', 'ut.fkTienda')
                ->where('u.id', $userId)
                ->where(function ($query) use ($tiendaId) {
                    $query->Where('ut.fkTienda', $tiendaId);
                })
                ->first();

            // Guardar los datos en la sesión
            if ($userStoreData) {
                session([
                    'user_email' => $userStoreData->email,
                    'user_fkUsuario' => $userStoreData->fkUsuario,
                    'user_estatus' => $userStoreData->Estatus,
                    'user_fkTienda' => $userStoreData->fkTienda,
                ]);
            }
        }

        return $next($request);
        }catch(Exception $e){

            DB::rollBack();
            Log::error('Error al registrar cliente existente - Persona ID: ' . $request->persona_id . ' - Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el cliente.');
        }
    }
}
