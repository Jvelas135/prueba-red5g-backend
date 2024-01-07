<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{

     /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Obtener la lista de roles",
     *     @OA\Response(response="200", description="Lista de roles"),
     * )
     */
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
