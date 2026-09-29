<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Alta/baja/modificación de los usuarios que consumen la API (/api/auth/*).
 *
 * Reemplaza a public/usuarios_api*.php, que no cortaban la ejecución sin
 * sesión y armaban el SQL con la entrada del request. Los usuarios de la API
 * viven en `users`, igual que los "puente" que crea LegacyCookieAuth
 * (@legacy.local); esos no se muestran ni se pueden tocar desde acá.
 */
class UsuarioApiController extends Controller
{
    use Concerns\AutorizaRolAdmin;

    /** Mismo rol mínimo que exigía la pantalla legacy (getRol() < 4 → exit). */
    private const ROL_MINIMO = 4;

    private const DOMINIO_PUENTE = '@legacy.local';

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->autorizar(self::ROL_MINIMO);

        $usuarios = $this->usuariosApi()->orderBy('name')->get();
        $sucursales = DB::table('sucursales')->orderBy('nombre')->get(['id', 'nombre']);
        $asignadas = DB::table('relacion_users_sucursales')
            ->whereIn('user_id', $usuarios->pluck('id'))
            ->get(['user_id', 'sucursal_id'])
            ->groupBy('user_id');

        return view('usuarios_api.index', compact('usuarios', 'sucursales', 'asignadas'));
    }

    public function store(Request $request)
    {
        $this->autorizar(self::ROL_MINIMO);

        $datos = $this->validar($request, null);
        User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => bcrypt($datos['password']),
        ]);

        return $this->volver('Usuario de API creado.');
    }

    public function update(Request $request, int $id)
    {
        $this->autorizar(self::ROL_MINIMO);

        $user = $this->usuariosApi()->findOrFail($id);
        $datos = $this->validar($request, $user);

        $user->name = $datos['name'];
        $user->email = $datos['email'];
        if (! empty($datos['password'])) {
            $user->password = bcrypt($datos['password']);
        }
        $user->save();

        return $this->volver('Usuario de API actualizado.');
    }

    public function destroy(int $id)
    {
        $this->autorizar(self::ROL_MINIMO);

        $user = $this->usuariosApi()->findOrFail($id);

        DB::transaction(function () use ($user) {
            DB::table('oauth_access_tokens')->where('user_id', $user->id)->delete();
            DB::table('relacion_users_sucursales')->where('user_id', $user->id)->delete();
            $user->delete();
        });

        return $this->volver('Usuario de API eliminado.');
    }

    public function asignarSucursal(Request $request, int $id)
    {
        $this->autorizar(self::ROL_MINIMO);

        $user = $this->usuariosApi()->findOrFail($id);
        $datos = $request->validate([
            'sucursal_id' => 'required|integer|exists:sucursales,id',
        ]);

        $relacion = ['user_id' => $user->id, 'sucursal_id' => (int) $datos['sucursal_id']];
        if (! DB::table('relacion_users_sucursales')->where($relacion)->exists()) {
            DB::table('relacion_users_sucursales')->insert($relacion);
        }

        return $this->volver('Sucursal asignada.');
    }

    public function quitarSucursal(int $id, int $sucursalId)
    {
        $this->autorizar(self::ROL_MINIMO);

        $user = $this->usuariosApi()->findOrFail($id);
        DB::table('relacion_users_sucursales')
            ->where(['user_id' => $user->id, 'sucursal_id' => $sucursalId])
            ->delete();

        return $this->volver('Sucursal quitada.');
    }

    /** Usuarios de `users` que no son los puente del login legacy. */
    private function usuariosApi()
    {
        return User::where('email', 'not like', '%' . self::DOMINIO_PUENTE);
    }

    private function validar(Request $request, ?User $user): array
    {
        return $request->validate([
            'name' => 'required|string|max:191',
            'email' => [
                'required', 'string', 'email', 'max:191',
                'not_regex:/' . preg_quote(self::DOMINIO_PUENTE, '/') . '$/i',
                Rule::unique('users', 'email')->ignore(optional($user)->id),
            ],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:12'],
        ], [
            'email.unique' => 'Ya existe un usuario con ese email.',
            'email.not_regex' => 'Ese dominio está reservado para el login interno.',
            'password.min' => 'La clave debe tener al menos 12 caracteres.',
        ]);
    }

    private function volver(string $mensaje)
    {
        return redirect('/usuarios-api')->with('usuarios_api_msg', $mensaje);
    }
}
