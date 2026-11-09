<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DirectoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/csrf-cookie', fn () => response()->json(['csrf_token' => csrf_token()]));
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
    Route::middleware(['auth', 'active'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/directory', [DirectoryController::class, 'index']);
        Route::apiResource('tickets', TicketController::class)->only(['index', 'store', 'show', 'update']);
        Route::post('/tickets/{ticket}/{action}', [TicketController::class, 'action'])
            ->where('action', 'submit|assign|transfer|classify|start|request-information|messages|internal-note|resolve|reopen|close|reject|cancel|rate');
        Route::post('/tickets/{ticket}/attachments', [AttachmentController::class, 'store']);
        Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download']);
        Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read']);
        Route::get('/reports', [ReportController::class, 'index']);
        Route::get('/reports/export', [ReportController::class, 'csv']);
        Route::get('/admin/{entity}', [AdminController::class, 'index'])->where('entity', 'users|roles|departments|members|categories');
        Route::post('/admin/{entity}', [AdminController::class, 'store'])->where('entity', 'users|departments|members|categories');
        Route::put('/admin/{entity}/{id}', [AdminController::class, 'update'])->where('entity','users|roles|departments|members|categories')->whereNumber('id');
    });
});
