<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class MemberController extends Controller
{
    public function index()
    {
        return response()->json(Member::all());
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255|unique:members,name',
            'class' => 'required|string|max:255',
            'level' => 'required|integer|min:1',
            'power' => 'required|integer|min:1',
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
        $member = Member::create($validated);

        return response()->json([
            'success' => true,
            'data' => $member,
            'message' => 'Member created successfully.',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $member = Member::find($id);
        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member not found',
            ], 404);
        }

        $rules = [
            'name' => 'sometimes|required|string|max:255|unique:members,name,' . $id,
            'level' => 'sometimes|required|integer|min:1',
            'power' => 'sometimes|required|integer|min:1',
            'role' => 'sometimes|required|string|max:255',
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
        $member->update($validated);

        return response()->json([
            'success' => true,
            'data' => $member,
            'message' => 'Member updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        $member = Member::find($id);
        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member not found',
            ], 404);
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Member deleted successfully',
        ]);
    }
}
