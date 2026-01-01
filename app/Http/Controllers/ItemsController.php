<?php

namespace App\Http\Controllers;

use App\Models\Items;
use Illuminate\Http\Request;

class ItemsController extends Controller
{
    public function index()
    {
        return response()->json(Items::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'itemName' => 'required|string|max:255',
            'rarity' => 'required|string',
            'category' => 'required|exists:item_categories,id',
        ]);

        $item = Items::create([
            'name' => $validated['itemName'],
            'rarity' => $validated['rarity'],
            'item_category_id' => $validated['category'],
        ]);
        return response()->json([
            'success' => true,
            'data' => $item,
        ], 201);
    }
}
