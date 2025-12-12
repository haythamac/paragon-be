<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use Illuminate\Http\Request;

class RaffleController extends Controller
{
    public function index(){
        return response()->json(Raffle::all());
    }
    
    public function store(Request $request)
    {
        $raffles = Raffle::create($request->all());
        return response()->json([
            'success' => true,
            'data' => $raffles,
        ], 201);
    }
}
