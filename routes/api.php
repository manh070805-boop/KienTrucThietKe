<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DirectoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth');
    Route::get('/csrf-cookie', fn () => response()->json(['csrf_token' => csrf_token()]));

    Route::middleware('auth')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/directory', [DirectoryController::class, 'index']);
        Route::apiResource('tickets', TicketController::class)->only(['index', 'store', 'show', 'update']);
        Route::post('/tickets/{ticket}/submit', [TicketController::class, 'submit']);
        Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assign']);
        Route::post('/tickets/{ticket}/start', [TicketController::class, 'start']);
        Route::post('/tickets/{ticket}/request-information', [TicketController::class, 'requestInformation']);
        Route::post('/tickets/{ticket}/messages', [TicketController::class, 'message']);
        Route::post('/tickets/{ticket}/resolve', [TicketController::class, 'resolve']);
        Route::post('/tickets/{ticket}/reopen', [TicketController::class, 'reopen']);
        Route::post('/tickets/{ticket}/close', [TicketController::class, 'close']);
        Route::post('/tickets/{ticket}/reject', [TicketController::class, 'reject']);
        Route::post('/tickets/{ticket}/cancel', [TicketController::class, 'cancel']);
        Route::post('/tickets/{ticket}/rate', [TicketController::class, 'rate']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read']);
    });
});
