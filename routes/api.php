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
    // Auto distribution
    Route::post('distribute/auto', [RaffleDistributionController::class, 'auto']);

    // Manual distribution
    Route::post('distribute/manual', [RaffleDistributionController::class, 'manual']);

    // View all distributions for this raffle
    Route::get('distributions', [RaffleDistributionController::class, 'index']);

    // Show one distribution row (raffle + distribution id)
    Route::get('distributions/{distribution}', [RaffleDistributionController::class, 'show']);

    // Show all items won by a specific member in this raffle
    Route::get('members/{member}/items', [RaffleDistributionController::class, 'memberItems']);

    Route::get('members/items', [RaffleDistributionController::class, 'raffleMembersItems']);

    // Show all winners of a specific item in this raffle
    Route::get('items/{item}/winners', [RaffleDistributionController::class, 'itemWinners']);

    // Change raffle status
    Route::patch('change-status', [RaffleController::class, 'changeStatus']);
});
