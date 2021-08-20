<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Lcobucci\JWT\Parser;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
       public function index(Request $request)
    {   

        return view("login.login");
    }
    public function signup(Request $request)
    {
        $this->validate($request, [
        'name' => 'required|string',
        'email' => 'required|string|email|unique:users',
        'password' => 'required|string'
        ], [
             'name.required' => 'Ingrese un nombre de usuario',
             'name.string' => 'El nombre debe ser de caracteres',
             'email.required' => 'Ingrese un email',
             'email.string' => 'El email debe ser de caracteres',
             'email.email' => 'Ingrese un email valido',
             'email.unique' => 'El email ya esta en uso',
             'password.required' => 'Ingrese una clave/contraseña',
             'password.string' => 'La contraseña debe ser de caracteres'
        ]);

        if ($request->id != "") {
            $user = User::find($request->id);
            $user->name = $request->name;
            $user->email = $request->email;
            
            if($request->password !== "") {
                $user->password = $request->password;
            }
            
            $user->save();
        } else {
            $user = new User([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password)
            ]);

            $user->save();
        }
        $mensaje="Usuario creado con éxito";
         return response()->json([
            'mensaje' => $mensaje]);
    }
  
    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|string|email',
            'password' => 'required|string'
        ], [
             'email.required' => 'Ingrese un email',
             'email.string' => 'El email debe ser de caracteres',
             'email.email' => 'Ingrese un email válido',
             'password.required' => 'Ingrese una clave/contraseña',
             'password.string' => 'La contraseña debe ser de caracteres'
        ]);

        $credentials = request(['email', 'password']);
        if(!Auth::attempt($credentials))
            return response()->json([
                'mensaje' => 'Usuario o password incorrectas'
            ], 401);

        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        // if ($request->remember_me)
            $token->expires_at = Carbon::now()->addDays(1);

        $token->save();

        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ]);
    }
  
    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {

        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Sesion cerrada con exito!'
        ]);
    }
  
    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        header('Access-Control-Allow-Origin: *');
        $user = $request->user();

        return response()->json(['user' => $user]);
    }
}