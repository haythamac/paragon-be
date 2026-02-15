<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RaffleController extends Controller
{
    public function index(){
        $raffles = Raffle::withCount('members')
                    ->orderBy('date', 'desc')
                    ->get()
                    ->map(function ($raffle) {
                        return [
                            'id' => $raffle->id,
                            'name' => $raffle->name,
                            'date' => $raffle->date,
                            'status' => $raffle->status,
                            'members_count' => $raffle->members_count,
                            'items_count' => $raffle->items()->count(),
                        ];
                    });
        return response()->json([
            'success' => true,
            'data' => $raffles,
            'message' => 'Raffles retrieved successfully.',
        ]);
    }
    
    public function store(Request $request)
    {

        // create validation rules
        $rules = [
            'name' => 'required|string|max:255|unique:raffles,name',
            'date' => 'required|date',
            'description' => 'nullable|string',
            // members
            'members' => 'required|array|min:1',
            'members.*' => 'exists:members,id',
            // items
            'items' => 'required|array',
            'items.*.id' => 'exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',

            'status' => 'required|in:pending,ongoing,completed',
        ];

        // validate the request
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // get validated data
        $validated = $validator->validated();

        // use transaction to ensure data integrity
        try {
            $raffle = DB::transaction(function () use ($validated) {
                // create the raffle
                $raffle = Raffle::create([
                    'name' => $validated['name'],
                    'date' => $validated['date'],
                    'description' => $validated['description'] ?? null,
                    'status' => $validated['status'],
                ]);

                // attach members to the raffle
                $raffle->members()->attach($validated['members']);

                // attach items with their quantities
                $itemsData = [];
                foreach ($validated['items'] as $item) {
                    $itemsData[$item['id']] = [
                        'initial_quantity' => $item['quantity'],
                        'remaining_quantity' => $item['quantity'],
                        ];
                }
                $raffle->items()->attach($itemsData);

                // load relationships for the response
                $raffle->load('members', 'items');

                return $raffle;
            });

            // success response
            return response()->json([
                'success' => true,
                'message' => 'Raffle created successfully',
                'data' => $raffle,
            ], 201);

        } catch (\Exception $e) {
            // handle errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to create raffle',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    public function show($id)
    {
        $raffle = Raffle::with(['members', 'items.category'])->find($id);

        if (!$raffle) {
            return response()->json([
                'success' => false,
                'message' => 'Raffle not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $raffle,
            'message' => 'Raffle retrieved successfully.',
        ]);
    }

    public function update(Request $request, $id)
    {
        $raffle = Raffle::find($id);
        if (!$raffle) {
            return response()->json([
                'success' => false,
                'message' => 'Raffle not found',
            ], 404);
        }

        // create validation rules
        $rules = [
            'name' => 'sometimes|required|string|max:255|unique:raffles,name,' . $raffle->id,
            'date' => 'sometimes|required|date',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:pending,ongoing,completed',
            // members
            'members' => 'sometimes|required|array|min:1',
            'members.*' => 'exists:members,id',
            // items
            'items' => 'sometimes|required|array',
            'items.*.id' => 'exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];

        // validate the request
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // get validated data
        $validated = $validator->validated();

        // use transaction to ensure data integrity
        try {
            $raffle = DB::transaction(function () use ($raffle, $validated) {
                // update the raffle basic info
                $raffle->update([
                    'name' => $validated['name'] ?? $raffle->name,
                    'date' => $validated['date'] ?? $raffle->date,
                    'description' => $validated['description'] ?? $raffle->description,
                    'status' => $validated['status'] ?? $raffle->status,
                ]);

                // sync members if provided
                if (isset($validated['members'])) {
                    $raffle->members()->sync($validated['members']);
                }

                // sync items with their quantities if provided
                if (isset($validated['items'])) {
                    $itemsData = [];
                    foreach ($validated['items'] as $item) {
                        $itemsData[$item['id']] = [
                            'initial_quantity' => $item['quantity'],
                            'remaining_quantity' => $item['quantity'],
                        ];
                    }
                    $raffle->items()->sync($itemsData);
                }

                // load relationships for the response
                $raffle->load('members', 'items');
                
                return $raffle;
            });

            // success response
            return response()->json([
                'success' => true,
                'message' => 'Raffle updated successfully',
                'data' => $raffle,
            ], 200);
        } catch (\Exception $e) {
            // handle errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to update raffle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $raffle = Raffle::find($id);
        if (!$raffle) {
            return response()->json([
                'success' => false,
                'message' => 'Raffle not found',
            ], 404);
        }

        try {
            $raffle->delete();
            return response()->json([
                'success' => true,
                'message' => 'Raffle deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete raffle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function changeStatus(Request $request, $id)
    {
        $raffle = Raffle::find($id);
        if (!$raffle) {
            return response()->json([
                'success' => false,
                'message' => 'Raffle not found',
            ], 404);
        }

        $rules = [
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

        try {
            $raffle->update(['status' => $request->input('status')]);
            return response()->json([
                'success' => true,
                'message' => 'Raffle status updated successfully',
                'data' => $raffle,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update raffle status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
