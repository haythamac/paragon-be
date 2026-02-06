<?php

namespace App\Http\Controllers;

use App\Models\Items;
use App\Models\Member;
use App\Models\Raffle;
use App\Models\RaffleDistribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RaffleDistributionController extends Controller
{
    public function index(Raffle $raffle)
    {
        return $raffle->distributions()->with(['member','item'])->get();
    }

    public function show(Raffle $raffle, RaffleDistribution $distribution)
    {
        if ($distribution->raffle_id !== $raffle->id) {
            abort(404);
        }

        return $distribution->load(['member','item']);
    }

    public function memberItems(Raffle $raffle, Member $member)
    {
        $distributions = RaffleDistribution::where('raffle_id', $raffle->id)
            ->where('member_id', $member->id)
            ->with('item')
            ->get();

        return [
            'raffle' => $raffle,
            'member' => $member,
            'items' => $distributions->map(function ($dist) {
                return [
                    'id' => $dist->item->id,
                    'name' => $dist->item->name,
                    'quantity' => $dist->quantity,
                ];
            }),
        ];
    }

    public function raffleMembersItems(Raffle $raffle)
    {
        $distributions = RaffleDistribution::where('raffle_id', $raffle->id)
            ->with(['member', 'item'])
            ->get()
            ->groupBy('member_id');

        return [
            'raffle' => $raffle,
            'members' => $distributions->map(function ($memberDists) {
                $member = $memberDists->first()->member;

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'items' => $memberDists->map(function ($dist) {
                        return [
                            'id' => $dist->item->id,
                            'name' => $dist->item->name,
                            'quantity' => $dist->quantity,
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }


    public function itemWinners(Raffle $raffle, Items $item)
    {
        $distributions = RaffleDistribution::where('raffle_id', $raffle->id)
            ->where('item_id', $item->id)
            ->with('member')
            ->get();

        return [
            'raffle' => $raffle,
            'item' => $item,
            'winners' => $distributions->map(function ($dist) {
                return [
                    'id' => $dist->member->id,
                    'name' => $dist->member->name,
                    'quantity' => $dist->quantity,
                ];
            }),
        ];
    }



    
    public function auto(Request $request)
    {
        /**
         * 1️⃣ Define validation rules
         * Auto distribution only needs raffle + member.
         * Item selection is handled by backend logic.
         */
        $rules = [
            'raffle_id' => 'required|exists:raffles,id',
            'member_id' => 'required|exists:members,id',
        ];

        /**
         * 2️⃣ Run validator
         */
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        /**
         * 3️⃣ Get validated data
         */
        $validated = $validator->validated();

        /**
         * 4️⃣ Use DB transaction
         * We must guarantee:
         * - item selection
         * - distribution creation
         * - quantity reduction
         * happen atomically.
         */
        try {
            $distribution = DB::transaction(function () use ($validated) {

                // Load raffle
                $raffle = Raffle::findOrFail($validated['raffle_id']);

                /**
                 * 5️⃣ Ensure member belongs to this raffle
                 */
                $memberExists = $raffle->members()
                    ->where('members.id', $validated['member_id'])
                    ->exists();

                if (! $memberExists) {
                    throw new \Exception('Member not in raffle');
                }

                /**
                 * 6️⃣ Pick the next available item
                 * Rule:
                 * - quantity > 0
                 * - first attached item (FIFO style)
                 */
                $item = $raffle->items()
                    ->wherePivot('quantity', '>', 0)
                    ->orderBy('raffle_item.id')
                    ->first();

                if (! $item) {
                    throw new \Exception('No items left to distribute');
                }

                /**
                 * 7️⃣ Create distribution record
                 * Auto distribution always gives 1 item.
                 */
                $distribution = RaffleDistribution::create([
                    'raffle_id' => $raffle->id,
                    'member_id' => $validated['member_id'],
                    'item_id'   => $item->id,
                    'quantity'  => 1,
                ]);

                /**
                 * 8️⃣ Reduce item quantity in raffle_items pivot
                 */
                $raffle->items()->updateExistingPivot(
                    $item->id,
                    [
                        'quantity' => $item->pivot->quantity - 1
                    ]
                );

                return $distribution;
            });

            /**
             * 9️⃣ Success response
             */
            return response()->json([
                'success' => true,
                'message' => 'Item auto-distributed successfully',
                'data'    => $distribution,
            ], 201);

        } catch (\Exception $e) {
            /**
             * ❌ Any exception causes rollback
             */
            return response()->json([
                'success' => false,
                'message' => 'Auto distribution failed',
                'errors'  => $e->getMessage(),
            ], 422);
        }
    }

    public function manual(Request $request)
    {
        /**
         * Validate the top-level fields and the items array
         */
        $rules = [
            'raffle_id'          => 'required|exists:raffles,id',
            'member_id'          => 'required|exists:members,id',
            'items'              => 'required|array|min:1',
            'items.*.item_id'    => 'required|exists:items,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $distributions = DB::transaction(function () use ($validated) {
                $raffle = Raffle::findOrFail($validated['raffle_id']);

                /**
                 * Ensure member belongs to this raffle (check once)
                 */
                $memberExists = $raffle->members()
                    ->where('members.id', $validated['member_id'])
                    ->exists();
                if (! $memberExists) {
                    throw new \Exception('Member not in this raffle');
                }

                $results = [];

                foreach ($validated['items'] as $item) {
                    /**
                     * Ensure item belongs to this raffle
                     */
                    $raffleItem = $raffle->items()
                        ->where('items.id', $item['item_id'])
                        ->first();
                    if (! $raffleItem) {
                        throw new \Exception("Item ID {$item['item_id']} is not in this raffle");
                    }

                    /**
                     * Ensure enough quantity is available
                     */
                    if ($raffleItem->pivot->remaining_quantity < $item['quantity']) {
                        throw new \Exception("Not enough quantity for item ID {$item['item_id']}. Available: {$raffleItem->pivot->remaining_quantity}");
                    }

                    /**
                     * Create distribution record
                     */
                    $distribution = RaffleDistribution::create([
                        'raffle_id' => $validated['raffle_id'],
                        'member_id' => $validated['member_id'],
                        'item_id'   => $item['item_id'],
                        'quantity'  => $item['quantity'],
                    ]);

                    /**
                     * Reduce remaining quantity in pivot
                     */
                    $raffle->items()->updateExistingPivot(
                        $item['item_id'],
                        [
                            'remaining_quantity' => $raffleItem->pivot->remaining_quantity - $item['quantity']
                        ]
                    );

                    $results[] = $distribution;
                }

                return $results;
            });

            return response()->json([
                'success' => true,
                'message' => 'Items distributed successfully',
                'data'    => $distributions,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to distribute items',
                'errors'  => $e->getMessage(),
            ], 422);
        }
    }
}
