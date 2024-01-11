<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        //Valida que todos los campos sean requeridos
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6',
            'cedula' => 'required|unique:users',
            'celular' => 'required',
            'rol' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                "msg" => $validator->errors()->toJson(),
                "success" => false
            ]);
        }

        //Inserta el usuario en la base de datos
         $user = new User();
         $users = $user->register($request->name, $request->email, $request->password, $request->cedula, $request->celular, $request->rol);

        return response()->json([
            "msg" =>$users["msg"],
            "success" => $users["success"],
        ]);

    }

    public function login(Request $request)
    {
        //Valida que todos los campos sean requeridos
        $request->validate([
            "email" => "required|email",
            "password" => "required"
        ]);

        //Valida el usuario y contraseña 
        $user = User::where("email", "=", $request->email)->first();
        if (isset($user->id)) {
            if (Hash::check($request->password, $user->password)) {

                $customClaims = ['role' => $user->rol];
                //genera un token
                $token = JWTAuth::customClaims($customClaims)->fromUser($user);

                return response()->json([
                    "msg" => "Usuario logueado correctamente",
                    "success" => true,
                    "token" => $token,
                    "user" => $user->rol
                ], 200);

            } else {
                return response()->json([
                    'msg' => '¡Usuario o contraseña!',
                    'success' => false
                ]);
            }
        } else {
            return response()->json([
                'msg' => '¡Usuario o contraseña!',
                'success' => false
            ]);
        }

    }

    public function logout()
    {
        try {
            //inhabilita el token del usuario
            $token = JWTAuth::getToken();
            JWTAuth::invalidate($token);

            return response()->json([
                'msg' => 'Logout exitoso',
                "success" => true
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }

    public function users()
    {
        try {
           
            $user = new User();
            $users = $user->users();

            return response()->json([
                "msg" => $users["msg"],
                "success" =>  $users["success"],
                "data" => $users["data"]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }

}