<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
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
            'cedula' => 'required',
            'celular' => 'required',
            'rol' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        //Inserta el usuario en la base de datos
        $sql = sprintf("INSERT INTO users (name,email,password,cedula,celular,rol) VALUES (?,?,?,?,?,?)");
        $success = DB::insert($sql, [$request->name, $request->email, Hash::make($request->password), $request->cedula, $request->celular, $request->rol]);

        return response()->json([
            "msg" => "Usuario creado correctamente",
            "success" => $success,
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
                    "token" => $token
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

            return response()->json(['message' => 'Logout exitoso']);

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
            $sql = sprintf("SELECT name,email,celular,cedula,rol FROM users");
            $users = DB::select($sql);
            return response()->json([
                "msg" => "ok",
                "success" => true,
                "data" => $users
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }

}