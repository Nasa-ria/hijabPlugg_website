<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Create an admin (needs "Authorization: Bearer <ADMIN_API_TOKEN>"; off when ADMIN_API_TOKEN is empty)
Route::post('/admin/users', [\App\Http\Controllers\Api\AdminUserController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('api.admin.users.store');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
