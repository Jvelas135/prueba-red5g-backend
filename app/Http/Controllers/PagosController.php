<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PagosController extends Controller
{

    public function pagosAprobados(Request $request)
    {
        $value = $request->input('some_value');

        switch ($value) {
            case 'previsualizar':
                try {

                    $validator = Validator::make($request->all(), [
                        'file' => 'required'
                    ]);
                    if ($validator->fails()) {
                        return response()->json([
                            "msg" => $validator->errors()->toJson(),
                            "success" => false
                        ]);
                    }

                    $file = $request->file('file');
                    //Extrae el tipo de extension de documento que se cargo
                    $fileExtension = $file->getClientOriginalExtension();
                    //Valida que el archivo sea xlsx o csv, si es correcto llama la function leerExcel si no te devuelve un mensaje de error
                    if ($fileExtension == 'xlsx' || $fileExtension == 'csv') {
                        $pago = new Pago();
                        $data = $pago->leerExcel($request);
                        return response()->json([
                            'data' => $data,
                            "success" => true,
                            "msg" => "archivo cargado correctamente"
                        ], 200);
                    } else {
                        return response()->json([
                            'msg' => "El formato: " . $fileExtension . " no es valido",
                            'success' => false
                        ]);
                    }
                } catch (Exception $e) {

                    return response()->json([
                        'msg' => $e->getMessage(),
                        "success" => false
                    ], 500);

                }

                break;

            case 'guardar':

                try {

                    $jsonString = $request->input('data');
                    $pago = new Pago();
                    $data = $pago->guardarAprobados($jsonString);

                    return response()->json([
                        "msg" => $data["msg"],
                        "success" => $data["success"]
                    ], 200);

                } catch (Exception $e) {

                    return response()->json([
                        'msg' => $e->getMessage(),
                        "success" => false
                    ], 500);

                }
                break;

            default:
                return response()->json([
                    "msg" => "Valor no reconocido",
                    "success" => false
                ]);
        }

    }
    public function pagosPendientes(Request $request)
    {
        $value = $request->input('some_value');

        switch ($value) {

            case 'previsualizar':

                try {

                    $validator = Validator::make($request->all(), [
                        'file' => 'required'
                    ]);
                    if ($validator->fails()) {
                        return response()->json([
                            "msg" => $validator->errors()->toJson(),
                            "success" => false
                        ]);
                    }

                    $file = $request->file('file');
                    //Extrae el tipo de extension de documento que se cargo
                    $fileExtension = $file->getClientOriginalExtension();
                    //Valida que el archivo sea xlsx o csv, si es correcto llama la function leerExcel si no te devuelve un mensaje de error
                    if ($fileExtension == 'xlsx' || $fileExtension == 'csv') {
                        $data = $pago = new Pago();
                        $data = $pago->leerExcel($request);

                        return response()->json([
                            'data' => $data,
                            "success" => true,
                            "msg" => "archivo cargado correctamente"
                        ], 200);

                    } else {
                        return response()->json([
                            'msg' => "El formato: " . $fileExtension . " no es valido",
                            'success' => false
                        ]);
                    }

                } catch (Exception $e) {

                    return response()->json([
                        'msg' => $e->getMessage(),
                        "success" => false
                    ], 500);

                }
                break;

            case 'guardar':

                try {

                    $jsonString = $request->input('data');
                    // Decode the JSON string into an associative array
                    $jsonString = $request->input('data');
                    $pago = new Pago();
                    $data = $pago->guardarPagosPendientes($jsonString);
                    return response()->json([
                        "msg" => $data["msg"],
                        "success" => $data["success"]
                    ], 200);

                } catch (Exception $e) {
                    return response()->json([
                        'msg' => $e->getMessage(),
                        "success" => false
                    ], 500);

                }
                break;

            default:
                return response()->json([
                    "msg" => "Valor no reconocido",
                    "success" => false
                ]);
        }

    }

    public function listar(Request $request)
    {

        $value = $request->input('some_value');
        //Se lee el caso que viene en la variable some_value y llama la function que se necesita segun el caso
        switch ($value) {

            case 'fecha':
                try {
                    $pago = new Pago();
                    $data = $pago->fecha();
                    return response()->json([
                        'success' => true,
                        'msg' => "ok",
                        'data' => $data
                    ], 200);

                } catch (Exception $e) {
                    return response()->json([
                        'success' => false,
                        'msg' => $e->getMessage()
                    ], 500);
                }
                break;
            case 'documento':
                try {

                    $pago = new Pago();
                    $data = $pago->documento($request->filtro);
                    return response()->json([
                        'success' => true,
                        'msg' => "ok",
                        'data' => $data
                    ], 200);
                } catch (Exception $e) {
                    return response()->json([
                        'success' => false,
                        'msg' => $e->getMessage()
                    ], 500);
                }
                break;
            case 'todos':
                try {
                    $pago = new Pago();
                    $data = $pago->todos();
                    return response()->json([
                        'success' => true,
                        'msg' => "ok",
                        'data' => $data
                    ], 200);
                } catch (Exception $e) {
                    return response()->json([
                        'success' => false,
                        'msg' => $e->getMessage()
                    ], 500);
                }
                break;
            default:
                return response()->json([
                    "msg" => "Valor no reconocido",
                    "success" => false
                ], 404);

        }
    }

}
