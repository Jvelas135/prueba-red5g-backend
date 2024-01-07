<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use DateTime;
use Tymon\JWTAuth\Facades\JWTAuth;

class PagosController extends Controller
{
 
    public function pagosAprobados(Request $request)
    {
        $value = $request->input('some_value');

        switch ($value) {
            case 'previsualizar':
                try {
                    $file = $request->file('file');
                    $fileExtension = $file->getClientOriginalExtension();
                    if ($fileExtension == 'xlsx' || $fileExtension == 'csv') {
                        $data = $this->leerAprobados($request);
                        return response()->json([
                            'data' => $data,
                            "success" => true,
                            "msg" => "archivo cargado correctamente"
                        ], 200);
                    } else {
                        return response()->json([
                            'msg' => "El formato: " . $fileExtension . " no es valido",
                            'success' => false
                        ], 404);
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

                    $file = $request->file('file');
                    $fileExtension = $file->getClientOriginalExtension();
                    if ($fileExtension == 'xlsx' || $fileExtension == 'csv') {
                        $data = $this->leerAprobados($request);
                        foreach ($data as $pagoData) {

                            $pagoData['fecha_pago'] = str_replace('/', '-', $pagoData['fecha_pago']);
                            $pagoData['fecha_pago'] = DateTime::createFromFormat('m-d-Y', $pagoData['fecha_pago'])->format('Y-m-d');

                            $fechaActual = new DateTime();
                            $fechaActualString = $fechaActual->format('Y-m-d');

                            if (($pagoData['fecha_pago'] < $fechaActualString)) {
                                return response()->json([
                                    'msg' => "Revisar archivo se encuenta una fecha menor a la actual",
                                    'success' => false
                                ], 404);
                            }
                            if (
                                !isset($pagoData['fecha_pago']) || !isset($pagoData['id_pago']) || !isset($pagoData['documento'])
                                || !isset($pagoData['correo']) || !isset($pagoData['monto'])
                            ) {
                                return response()->json([
                                    'msg' => "Revisar archivo se encuenta campos vacios",
                                    'success' => false
                                ], 404);
                            }

                        }
                    } else {
                        return response()->json([
                            'msg' => "El formato: " . $fileExtension . " no es valido",
                            'success' => false
                        ], 404);
                    }

                    $sql = "UPDATE pagos SET id_pago = ?, estado_pago = ?, usuario_aprueba = ? WHERE documento = ? AND correo = ? AND monto = ? AND fecha_pago = ?";
                    foreach ($data as $pagoData) {
                        $user = JWTAuth::parseToken()->authenticate();

                        $pagoData['fecha_pago'] = str_replace('/', '-', $pagoData['fecha_pago']);
                        $pagoData['fecha_pago'] = DateTime::createFromFormat('m-d-Y', $pagoData['fecha_pago'])->format('Y-m-d');
                        $params = [
                            $pagoData['id_pago'],
                            'Pagado',
                            $user->email,
                            $pagoData['documento'],
                            $pagoData['correo'],
                            $pagoData['monto'],
                            $pagoData['fecha_pago']
                        ];

                        DB::statement($sql, $params);
                    }
                    return response()->json([
                        "success" => true,
                        "msg" => "Estado de pago cambiado correctamente"
                    ]);

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
                ], 404);
        }

    }

    public function pagosPendientes(Request $request)
    {
        $value = $request->input('some_value');

        switch ($value) {

            case 'previsualizar':

                try {

                    $file = $request->file('file');
                    $fileExtension = $file->getClientOriginalExtension();
                    if ($fileExtension == 'xlsx' || $fileExtension == 'csv') {
                        $data = $this->leerPendientes($request);
                        return response()->json([
                            'data' => $data,
                            "success" => true,
                            "msg" => "archivo cargado correctamente"
                        ], 200);

                    } else {
                        return response()->json([
                            'msg' => "El formato: " . $fileExtension . " no es valido",
                            'success' => false
                        ], 404);
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

                    $file = $request->file('file');
                    $fileExtension = $file->getClientOriginalExtension();
                    if ($fileExtension == 'xlsx' || $fileExtension == 'csv') {
                        $data = $this->leerPendientes($request);
                        foreach ($data as $pagoData) {

                            $pagoData['fecha_pago'] = str_replace('/', '-', $pagoData['fecha_pago']);
                            $pagoData['fecha_limite'] = str_replace('/', '-', $pagoData['fecha_limite']);
                            $pagoData['fecha_pago'] = DateTime::createFromFormat('m-d-Y', $pagoData['fecha_pago'])->format('Y-m-d');
                            $pagoData['fecha_limite'] = DateTime::createFromFormat('m-d-Y', $pagoData['fecha_limite'])->format('Y-m-d');

                            $fechaActual = new DateTime();
                            $fechaActualString = $fechaActual->format('Y-m-d');

                            if (($pagoData['fecha_pago'] < $fechaActualString) || ($pagoData['fecha_limite'] < $fechaActualString)) {
                                return response()->json([
                                    'msg' => "Revisar archivo se encuenta una fecha menor a la actual",
                                    'success' => false
                                ], 404);
                            }
                            if (($pagoData['fecha_pago'] < $pagoData['fecha_limite'])) {
                                return response()->json([
                                    'msg' => "Revisar archivo se encuenta una fecha limite menor a la fecha pago",
                                    'success' => false
                                ], 404);
                            }
                            if (
                                !isset($pagoData['fecha_pago']) || !isset($pagoData['fecha_limite']) || !isset($pagoData['documento'])
                                || !isset($pagoData['correo']) || !isset($pagoData['monto'])
                            ) {
                                return response()->json([
                                    'msg' => "Revisar archivo se encuenta campos vacios",
                                    'success' => false
                                ], 404);
                            }

                        }
                    } else {
                        return response()->json([
                            'msg' => "El formato: " . $fileExtension . " no es valido",
                            'success' => false
                        ], 404);
                    }
                    $sql = sprintf("INSERT INTO pagos (documento,nombre,correo,monto,fecha_pago,estado_pago,fecha_limite) SELECT ?,?,?,?,?,?,? WHERE NOT EXISTS (SELECT 1
                    FROM pagos p WHERE p.documento = ? AND p.nombre = ? AND p.correo = ? AND p.monto = ?  AND p.fecha_pago = ? AND p.estado_pago = ? AND p.fecha_limite = ? )");

                    foreach ($data as $pagoData) {

                        $pagoData['fecha_pago'] = str_replace('/', '-', $pagoData['fecha_pago']);
                        $pagoData['fecha_limite'] = str_replace('/', '-', $pagoData['fecha_limite']);
                        $pagoData['fecha_pago'] = DateTime::createFromFormat('m-d-Y', $pagoData['fecha_pago'])->format('Y-m-d');
                        $pagoData['fecha_limite'] = DateTime::createFromFormat('m-d-Y', $pagoData['fecha_limite'])->format('Y-m-d');

                        $params = [
                            $pagoData['documento'],
                            $pagoData['nombre'],
                            $pagoData['correo'],
                            $pagoData['monto'],
                            $pagoData['fecha_pago'],
                            'Pendiente',
                            $pagoData['fecha_limite'],
                            $pagoData['documento'],
                            $pagoData['nombre'],
                            $pagoData['correo'],
                            $pagoData['monto'],
                            $pagoData['fecha_pago'],
                            'Pendiente',
                            $pagoData['fecha_limite'],

                        ];

                        $success = DB::insert($sql, $params);
                    }
                    return response()->json([
                        "success" => $success,
                        "msg" => "Pagos pendientes guardados correctamente"
                    ]);



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
                ], 404);
        }

    }

    private function leerPendientes($request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        try {
            $file = $request->file('file');
            $fileExtension = $file->getClientOriginalExtension();
            if ($fileExtension == 'xlsx' || $fileExtension == 'csv') {
                $spreadsheet = IOFactory::load($file->getPathname());
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->rangeToArray('A2:' . $sheet->getHighestColumn() . $sheet->getHighestRow());
                $columnNames = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0];
            } else {
                return response()->json([
                    'msg' => "El formato: " . $fileExtension . " no es valido",
                    'success' => false
                ], 404);
            }

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $formattedData = [];

        foreach ($data as $rowData) {
            $formattedRow = [];

            foreach ($columnNames as $index => $columnName) {
                if (isset($rowData[$index])) {
                    $formattedRow[$this->mapColumnNamePendientes($columnName)] = $rowData[$index];
                }
            }

            $formattedData[] = $formattedRow;

        }
        return $formattedData;

    }

    private function leerAprobados($request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        try {
            $file = $request->file('file');
            $fileExtension = $file->getClientOriginalExtension();
            if ($fileExtension == 'xlsx' || $fileExtension == 'csv') {

                $spreadsheet = IOFactory::load($file->getPathname());
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->rangeToArray('A2:' . $sheet->getHighestColumn() . $sheet->getHighestRow());
                $columnNames = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0];

            } else {
                return response()->json([
                    'msg' => "El formato: " . $fileExtension . " no es valido",
                    'success' => false
                ], 404);
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $formattedData = [];

        foreach ($data as $rowData) {
            $formattedRow = [];

            foreach ($columnNames as $index => $columnName) {
                if (isset($rowData[$index])) {
                    $formattedRow[$this->mapColumnName($columnName)] = $rowData[$index];
                }
            }

            $formattedData[] = $formattedRow;
        }

        return $formattedData;
    }

    private function mapColumnName($originalName)
    {

        $columnMappings = [
            'nombre' => 'nombre',
            'correo' => 'correo',
            'documento' => 'documento',
            'fecha pago' => 'fecha_pago',
            'id_pago' => 'id_pago',
        ];

        return $columnMappings[$originalName] ?? $originalName;
    }

    private function mapColumnNamePendientes($originalName)
    {

        $columnMappings = [
            'nombre' => 'nombre',
            'correo' => 'correo',
            'documento' => 'documento',
            'fecha pago' => 'fecha_pago',
            'fecha limite' => 'fecha_limite',
        ];

        return $columnMappings[$originalName] ?? $originalName;
    }

    public function listar(Request $request)
    {

        $value = $request->input('some_value');

        switch ($value) {

            case 'fecha':
                try {
                    $data = $this->fecha($request->filtro);
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

                    $data = $this->documento($request->filtro);
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
                    $data = $this->todos();
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

    private function fecha()
    {
        try {
            $sql = sprintf("SELECT * FROM pagos WHERE MONTH(fecha_pago) = MONTH(CURDATE())");
            $data = DB::select($sql);
            return $data;

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }
    private function documento($request)
    {
        try {
            $sql = sprintf("SELECT * FROM pagos WHERE documento = ?");
            $data = DB::select($sql, [$request]);
            return $data;

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }
    private function todos()
    {
        try {
            $sql = sprintf("SELECT * FROM pagos");
            $data = DB::select($sql);
            return $data;

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }
}
