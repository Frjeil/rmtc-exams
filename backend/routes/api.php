<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\VoteController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:auth');

Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:auth');

Route::middleware('throttle:api')->group(function (): void {
    Route::get('/exams', [ExamController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::post('/exams/{exam}/enroll', [ExamController::class, 'enroll']);

        Route::middleware('role:user')->group(function (): void {
            Route::get('/my/exams', [ExamController::class, 'myExams']);
        });

        Route::middleware('role:admin')->group(function (): void {
            Route::post('/admin/exams', [ExamController::class, 'store']);
        });

        Route::middleware('role:supervisor')->group(function (): void {
            Route::get('/exams/{exam}/users', [ExamController::class, 'enrolledUsers']);
            Route::get('/supervisor/my/votes', [VoteController::class, 'myVotes']);
            Route::post('/supervisor/exams/{exam}/assign', [VoteController::class, 'assign']);
        });
    });
});
