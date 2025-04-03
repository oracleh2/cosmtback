<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SkinPhotoController;
use App\Http\Controllers\Api\SkinAnalysisController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RecommendationController;

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

// Аутентификация
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Защищенные маршруты
Route::middleware('auth:sanctum')->group(function () {
    // Аутентификация
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Фотографии кожи
    Route::get('/skin-photos', [SkinPhotoController::class, 'index']);
    Route::post('/skin-photos', [SkinPhotoController::class, 'store']);
    Route::get('/skin-photos/latest', [SkinPhotoController::class, 'latest']);
    Route::get('/skin-photos/{id}', [SkinPhotoController::class, 'show']);
    Route::delete('/skin-photos/{id}', [SkinPhotoController::class, 'destroy']);

    // Анализы кожи (асинхронные)
    Route::get('/skin-analyses', [SkinAnalysisController::class, 'index']);
    Route::post('/skin-photos/{photoId}/analyze', [SkinAnalysisController::class, 'requestAnalysis']);
    Route::get('/analysis-requests/{requestId}/status', [SkinAnalysisController::class, 'checkAnalysisStatus']);
    Route::get('/skin-analyses/{id}', [SkinAnalysisController::class, 'show']);
    Route::get('/skin-analyses/timeline', [SkinAnalysisController::class, 'timeline']);

    // Косметические продукты
    Route::get('/cosmetics', [ProductController::class, 'index']);
    Route::post('/cosmetics', [ProductController::class, 'store']);
    Route::get('/cosmetics/{id}', [ProductController::class, 'show']);
    Route::put('/cosmetics/{id}', [ProductController::class, 'update']);
    Route::delete('/cosmetics/{id}', [ProductController::class, 'destroy']);
    Route::post('/cosmetics/analyze-ingredients', [ProductController::class, 'analyzeIngredients']);

    // Рекомендации
    Route::get('/recommendations', [RecommendationController::class, 'index']);
    Route::get('/recommendations/latest', [RecommendationController::class, 'latest']);
    Route::get('/recommendations/{id}', [RecommendationController::class, 'show']);
    Route::post('/recommendations/compare', [RecommendationController::class, 'compare']);
});
