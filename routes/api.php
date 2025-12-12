<?php

use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\RaffleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('members', MemberController::class);
Route::apiResource('raffle', RaffleController::class);
Route::apiResource('item_category', ItemCategoryController::class);
Route::apiResource('item', ItemsController::class);
