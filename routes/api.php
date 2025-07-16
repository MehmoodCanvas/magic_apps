<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserProfileController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/user/profile', [UserProfileController::class, 'update']);
    Route::post('/reset-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/countries', [UserProfileController::class, 'countries']);
    Route::get('/qualification', [UserProfileController::class, 'qualifications']);
    Route::get('/employment-status', [UserProfileController::class, 'employmentStatuses']);
    Route::get('/work-style', [UserProfileController::class, 'workStyles']);
    Route::get('/categories', [UserProfileController::class, 'categories']);
    Route::get('/sub-categories', [UserProfileController::class, 'subCategories']);
});
