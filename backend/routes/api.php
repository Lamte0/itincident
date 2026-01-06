<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Routes publiques (authentification)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);

    // Incidents
    Route::get('/incidents', [IncidentController::class, 'index']);
    Route::get('/incidents/mes-incidents', [IncidentController::class, 'mesIncidents']);
    Route::post('/incidents', [IncidentController::class, 'store']);
    Route::get('/incidents/{incident}', [IncidentController::class, 'show']);
    Route::put('/incidents/{incident}', [IncidentController::class, 'update']);
    Route::delete('/incidents/{incident}', [IncidentController::class, 'destroy']);

    // Actions sur les incidents
    Route::post('/incidents/{incident}/affecter', [IncidentController::class, 'affecter']);
    Route::post('/incidents/{incident}/prendre-en-charge', [IncidentController::class, 'prendreEnCharge']);
    Route::post('/incidents/{incident}/resoudre', [IncidentController::class, 'resoudre']);
    Route::post('/incidents/{incident}/valider', [IncidentController::class, 'valider']);
    Route::post('/incidents/{incident}/rejeter', [IncidentController::class, 'rejeter']);

    // Utilisateurs (Admin)
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/maintenanciers', [UserController::class, 'maintenanciers']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::put('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    // Rapports et statistiques
    Route::get('/reports/fiche-intervention/{incident}', [ReportController::class, 'ficheIntervention']);
    Route::get('/reports/statistiques', [ReportController::class, 'statistiques']);
    Route::get('/reports/export', [ReportController::class, 'export']);
});
