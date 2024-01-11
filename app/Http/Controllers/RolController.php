<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use Exception;

class RolController extends Controller
{

    public function leerRoles(){
        try {
            $role = new Rol();
            $roles = $role->leerRoles();
            return response()->json([
                "msg" => "ok",
                "success" => true,
                "data" => $roles
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }
}
