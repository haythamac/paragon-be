<?php

use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\RaffleController;
use App\Http\Controllers\RaffleDistributionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('members', MemberController::class);
Route::apiResource('raffle', RaffleController::class);
Route::apiResource('item_category', ItemCategoryController::class);
Route::apiResource('item', ItemsController::class);


Route::prefix('raffles/{raffle}')->group(function () {
    // Auto distribution: system picks next available item
    Route::post('distribute/auto', [RaffleDistributionController::class, 'auto']);

    // Manual distribution: admin chooses item and member
    Route::post('distribute/manual', [RaffleDistributionController::class, 'manual']);

    // Optional: view all distributions for this raffle
    Route::get('distributions', [RaffleDistributionController::class, 'index']);
});