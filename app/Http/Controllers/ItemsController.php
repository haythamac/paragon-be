<?php

namespace App\Http\Controllers;

use App\Models\Items;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemsController extends Controller
{
    public function index()
    {
        return response()->json(Items::all());
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
                                ->where('item_category_id', $request->category);
                }),
            ],
            'rarity' => ['required', Rule::in(['common','uncommon','rare','epic','legendary'])],
            'category' => 'required|exists:item_categories,id',
        ], [
            'itemName.unique' => 'This item already exists with the same rarity and category.',
        ]);
        $item = Items::create([
            'name' => $validated['itemName'],  // Map itemName to name
            'item_category_id' => $validated['category'],  // Map category to item_category_id
            'rarity' => $validated['rarity'],
        ]);

        return response()->json([
            'message' => 'Item created successfully',
            'data' => $item
        ], 201);
    }

}
