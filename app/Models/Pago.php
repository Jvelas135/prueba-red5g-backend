<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tymon\JWTAuth\Facades\JWTAuth;
use DateTime;

class Pago extends Model
{
    use HasFactory;

    public function fecha()
    {
        try {
            $sql = sprintf("SELECT * FROM pagos WHERE MONTH(fecha_pago) = MONTH(CURDATE()) AND estado_pago = ?");
            $data = DB::select($sql, ['Pendiente']);
            return $data;

        } catch (Exception $e) {
            return [
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }
    }
    public function documento($request)
    {
        try {
            $sql = sprintf("SELECT * FROM pagos WHERE documento = ?");
            $data = DB::select($sql, [$request]);
            return $data;

        } catch (Exception $e) {
            return[
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }
    }
    public function todos()
    {
        try {
            $sql = sprintf("SELECT * FROM pagos");
            $data = DB::select($sql);
            return $data;

        } catch (Exception $e) {
            return[
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }
    }
    public function leerExcel($request)
    {
        //valida que el archivo sea requerido
        $validator = Validator::make($request->all(), [
            'file' => 'required|required',
        ]);

        if ($validator->fails()) {
            return [
                "msg" => $validator->errors()->toJson(),
                "success" => false
            ];
        }
        try {
            $file = $request->file('file');
            //Extrae el tipo de extension de documento que se cargo
            $fileExtension = $file->getClientOriginalExtension();
            //Valida que el archivo sea xlsx o csv, si es correcto lee el archivo cargado y devuelvo un array
            if ($fileExtension == 'xlsx' || $fileExtension == 'csv') {
                $spreadsheet = IOFactory::load($file->getPathname());
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->rangeToArray('A2:' . $sheet->getHighestColumn() . $sheet->getHighestRow());
                $columnNames = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0];
            } else {
                return [
                    'msg' => "El formato: " . $fileExtension . " no es valido",
                    'success' => false
                ];
            }

        } catch (Exception $e) {
            return [
                'msg' => $e->getMessage(),
                "success" => false
            ];
        }

        $formattedData = [];
        //Se recorre el array cargado y los convierte en un json 
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
            'fecha limite' => 'fecha_limite',
        ];

        return $columnMappings[$originalName] ?? $originalName;
    }

    public function guardarAprobados($jsonString)
    {
        try {

            // Decodifica la cadena JSON en un array asociativo
            $data = json_decode($jsonString, true);

            // Verifica si la decodificación fue exitosa
            if ($data !== null) {
                foreach ($data as $pagoData) {

                    $pagoData['fecha_pago'] = str_replace('/', '-', $pagoData['fecha_pago']);
                    $pagoData['fecha_pago'] = DateTime::createFromFormat('m-d-Y', $pagoData['fecha_pago'])->format('Y-m-d');

                    // Valida que todos los campos esten lleno
                    if (
                        !isset($pagoData['fecha_pago']) || !isset($pagoData['id_pago']) || !isset($pagoData['documento'])
                        || !isset($pagoData['correo']) || !isset($pagoData['monto']) || !isset($pagoData['nombre'])
                    ) {
                        return [
                            'msg' => "Revisar archivo se encuenta campos vacios",
                            'success' => false
                        ];
                    }
                    // Valida que el monto de confirmacion sea igual al registrado
                    $sql = sprintf("SELECT COUNT(*) AS result
                    FROM pagos
                    WHERE fecha_pago = ?
                      AND documento =  ?
                      AND correo =  ?
                      AND monto =  ?
                      AND nombre =  ? ");
                    $params = [
                        $pagoData['fecha_pago'],
                        $pagoData['documento'],
                        $pagoData['correo'],
                        $pagoData['monto'],
                        $pagoData['nombre']
                    ];
                    $monto = DB::select($sql, $params);

                    if ($monto[0]->result < 0) {
                        return [
                            'msg' => [
                                "msg" => "El monto en el registro es incorrecto: ",
                                "documento" => $pagoData['documento'],
                                "nombre" => $pagoData['nombre'],
                                "correo" => $pagoData['correo'],
                                "fecha_pago" => $pagoData['fecha_pago'],
                                "monto" => $pagoData['monto']
                            ],
                            'success' => false
                        ];
                    }
                }
                /* Si el archivo cargado pasa todas las validaciones procede a buscar los registros cargado, actualiza el estado de pago,
               guarda el id_pago y el usuario que aprobado el pago
            */
                $sql = sprintf("UPDATE pagos SET id_pago = ?, estado_pago = ?, usuario_aprueba = ? WHERE documento = ? AND correo = ? AND monto = ? AND fecha_pago = ?");
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
                return [
                    "success" => true,
                    "msg" => "Estado de pago cambiado correctamente"
                ];

            } else {
                return [
                    "msg" => "No se envio el archivo",
                    "success" => false
                ];
            }

        } catch (Exception $e) {

            return [
                'msg' => $e->getMessage(),
                "success" => false
            ];

        }
    }

    public function guardarPagosPendientes($jsonString)
    {

        try {
            $data = json_decode($jsonString, true);

            // Verifica si la decodificación fue exitosa
            if ($data !== null) {
                // Ahora, $data es un array asociativo que puedes recorrer.
                foreach ($data as $pagoData) {

                    $pagoData['fecha_pago'] = str_replace('/', '-', $pagoData['fecha_pago']);
                    $pagoData['fecha_limite'] = str_replace('/', '-', $pagoData['fecha_limite']);
                    $pagoData['fecha_pago'] = DateTime::createFromFormat('m-d-Y', $pagoData['fecha_pago'])->format('Y-m-d');
                    $pagoData['fecha_limite'] = DateTime::createFromFormat('m-d-Y', $pagoData['fecha_limite'])->format('Y-m-d');

                    $fechaActual = new DateTime();
                    $fechaActualString = $fechaActual->format('Y-m-d');
                    // Valida que la fecha de pago y fecha limite de pago no sea inferior a la fecha actual 
                    if (($pagoData['fecha_pago'] < $fechaActualString) || ($pagoData['fecha_limite'] < $fechaActualString)) {
                        return [
                            'msg' => "Revisar archivo se encuenta una fecha menor a la actual",
                            'success' => false
                        ];
                    }
                    // Valida que la fecha de pago no se inferior a la fecha limite de pago
                    if (($pagoData['fecha_pago'] > $pagoData['fecha_limite'])) {
                        return [
                            'msg' => "Revisar archivo se encuenta una fecha limite menor a la fecha pago",
                            'success' => false
                        ];
                    }
                    // Valida que todos los campos esten lleno
                    if (
                        !isset($pagoData['fecha_pago']) || !isset($pagoData['fecha_limite']) || !isset($pagoData['documento'])
                        || !isset($pagoData['correo']) || !isset($pagoData['monto'])
                    ) {
                        return [
                            'msg' => "Revisar archivo se encuenta campos vacios",
                            'success' => false
                        ];
                    }

                }

                // Revisa si existe en el registro en la base de datos y si no existe lo inserta
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
                return [
                    "success" => $success,
                    "msg" => "Pagos pendientes guardados correctamente"
                ];

            } else {
                // Handle JSON decoding error
                return [
                    "msg" => "No se envio el archivo",
                    "success" => false
                ];

            }

        } catch (Exception $e) {
            return [
                'msg' => $e->getMessage(),
                "success" => false
            ];
        }
    }
}
