<?php

namespace Itca\HorasSociales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * SOLO PARA EL PROYECTO DE PRUEBAS (SSE_LOGIN_PRUEBA=true).
 * En la aplicación del profesor se usa su propio inicio de sesión.
 */
class IngresoPruebaController extends Controller
{
    public function formulario()
    {
        return view('horas-sociales::ingreso');
    }

    public function ingresar(Request $request)
    {
        $datos = $request->validate([
            'carnet' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
        ], [], ['carnet' => 'carnet', 'password' => 'contraseña']);

        $credenciales = [config('horas-sociales.campo_carnet') => $datos['carnet'], 'password' => $datos['password']];

        if (! Auth::attempt($credenciales)) {
            return back()->withInput($request->only('carnet'))
                ->withErrors(['carnet' => 'El carnet o la contraseña no coinciden. Revisa e intenta de nuevo.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('sse.panel'));
    }

    public function salir(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('sse.ingreso');
    }
}
