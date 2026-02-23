<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class MemberController extends Controller
{
    public function index()
    {
        $members = Member::orderByRaw("
                        CASE 
                            WHEN role = 'leader' THEN 1
                            WHEN role = 'elder' THEN 2
                            WHEN role = 'member' THEN 3
                            ELSE 4
                        END
                    ")
                    ->orderBy('name', 'asc') // A → Z
                    ->get();

        return response()->json(
            [
                'success' => true,
                'data' => $members,
                'message' => 'Members retrieved successfully.',
            ]
        );
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255|unique:members,name',
            'class' => 'required|string|max:255',
            'level' => 'required|integer|min:1',
            'role' => 'required|string|max:255',
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

        // should be the response
        // for success response
        //   "success": true,
        //   "data": {
        //     "id": 123,
        //     "type": "member",
        //     "attributes": {
        //       "name": "Warrior",
        //       "level": 10,
        //       "power": 250,
        //       "created_at": "2026-01-14T06:19:00Z",
        //       "updated_at": "2026-01-14T06:19:00Z",
        //       "role": "fighter",
        //       "class": "melee"
        //     }
        //   },
        //   "message": "Member created successfully.",
        //   "meta": {
        //     "request_id": "abc123",
        //     "timestamp": "2026-01-14T06:19:00Z",
        //     "pagination": {
        //       "page": 1,
        //       "per_page": 20,
        //       "total": 100
        //     }
        //   }
        // }
        
        // for error response
        // {
        //   "success": false,
        //   "errors": [
        //     {
        //       "code": "VALIDATION_ERROR",
        //       "field": "name",
        //       "message": "The name has already been taken."
        //     }
        //   ],
        //   "message": "Validation failed",
        //   "meta": {
        //     "request_id": "abc123",
        //     "timestamp": "2026-01-14T06:19:00Z"
        //   }
        // }


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
