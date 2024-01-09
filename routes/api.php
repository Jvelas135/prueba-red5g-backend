<?php

use App\Http\Controllers\RolController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PagosController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', [AuthController::class, 'login'])->name("login");

Route::middleware('jwt.verify')->group(function(){
    Route::get('/user',[AuthController::class, 'users'])->middleware('checkRole:ADMINISTRADOR');
    Route::post('register', [AuthController::class, 'register'])->middleware('checkRole:ADMINISTRADOR');
    Route::post('/pagos_aprobados', [PagosController::class, 'pagosAprobados'])->middleware('checkRole:ADMINISTRADOR,APROBADOR,SEMI-ADMINISTRADOR');
    Route::post('/pagos_pendientes', [PagosController::class, 'pagosPendientes'])->middleware('checkRole:ADMINISTRADOR,PENDIENTES,SEMI-ADMINISTRADOR');
    Route::post('/listar', [PagosController::class, 'listar'])->middleware('checkRole:ADMINISTRADOR,LECTOR,SEMI-ADMINISTRADOR');
    Route::get('/roles',[RolController::class, 'leerRoles']);
    Route::post('logout', [AuthController::class, 'logout']);
});
 