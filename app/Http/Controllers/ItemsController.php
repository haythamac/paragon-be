<?php

namespace App\Http\Controllers;

use App\Models\Items;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemsController extends Controller
{
    public function index()
    {   $items = Items::all();
        return response()->json(
            [
                'success' => true,
                'data' => $items,
                'message' => 'Items retrieved successfully.',
            ]
        );
    }

    public function store(Request $request)
    {
        
        $validated = $request->validate([
            'itemName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('items', 'name')->where(function ($query) use ($request) {
                    return $query->where('rarity', $request->rarity)
                                ->where('item_category_id', $request->category)
                                ->where('is_tradeable', $request->tradeable);

                }),
            ],
            'tradeable' => 'required',
            'rarity' => ['required', Rule::in(['common','uncommon','rare','epic','legendary'])],
            'category' => 'required|exists:item_categories,id',
        ], [
            'itemName.unique' => 'This item already exists with the same rarity, category, and tradeable status.',
        ]);

        $item = Items::create([
            'name' => $validated['itemName'],  // Map itemName to name
            'item_category_id' => $validated['category'],  // Map category to item_category_id
            'rarity' => $validated['rarity'],
            'is_tradeable' => $validated['tradeable'],  // Map tradeable to is_tradeable
        ]);

        return response()->json([
            'success' => true,
            'data' => $item,
            'message' => 'Item created successfully',
        ], 201);
    }

}
