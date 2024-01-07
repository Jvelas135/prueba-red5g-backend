<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{

    public function leerRoles(){
        try {
            $sql = sprintf("SELECT * FROM roles");
            $roles = DB::select($sql);
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
