<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RaffleController extends Controller
{
    public function index(){
        $raffles = Raffle::all();
        return response()->json([
            'success' => true,
            'data' => $raffles,
            'message' => 'Raffles retrieved successfully.',
        ]);
    }
    
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255|unique:raffles,name',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'members' => 'required|array|min:1',
            'members.*' => 'exists:members,id',
            'members_joined' => 'nullable|integer|min:0',
            'items' => 'required|array',
            'items.*.id' => 'exists:items,id',
            'item_count' => 'required|integer|min:1',
            'status' => 'required|in:pending,ongoing,completed',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $raffle = Raffle::create($validated);

        return response()->json([
            'success' => true,
            'data' => $raffle,
            'message' => 'Raffle created successfully',
        ], 201);
    }
}
