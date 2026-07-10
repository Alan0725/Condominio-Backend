<?php

use App\Http\Controllers\Api\AsambleaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\MultaController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PagoAtrasadoController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/email/resend', [AuthController::class, 'resendVerification']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

    Route::get('/multas/{multa}', [MultaController::class, 'show']);
    Route::get('/pagos-atrasados/{pagoAtrasado}', [PagoAtrasadoController::class, 'show']);
    Route::get('/asambleas', [AsambleaController::class, 'index']);
    Route::get('/asambleas/{asamblea}', [AsambleaController::class, 'show']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/users', function () {
            return User::select('id', 'name', 'departamento')->orderBy('name')->get();
        });
        Route::post('/multas', [MultaController::class, 'store']);
        Route::post('/pagos-atrasados', [PagoAtrasadoController::class, 'store']);
        Route::post('/asambleas', [AsambleaController::class, 'store']);
    });
});
