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
        $item = Items::create($request->all());
        return response()->json([
            'success' => true,
            'data' => $item,
        ], 201);
    }
}
