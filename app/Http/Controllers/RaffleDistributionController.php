<?php

namespace App\Http\Controllers;

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
         * Define validation rules
         * We only validate existence & basic constraints here.
         * Business rules (member in raffle, item in raffle, quantity check)
         * are handled AFTER validation.
         */
        $rules = [
            'raffle_id' => 'required|exists:raffles,id',
            'member_id' => 'required|exists:members,id',
            'item_id'   => 'required|exists:items,id',
            'quantity'  => 'required|integer|min:1',
        ];

        /**
         * Run validator
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
         * Get validated data
         */
        $validated = $validator->validated();

        /**
         * Use DB transaction to ensure:
         * - distribution record is created
         * - item quantity is reduced
         * BOTH succeed or BOTH fail
         */
        try {
            $distribution = DB::transaction(function () use ($validated) {

                // Load raffle
                $raffle = Raffle::findOrFail($validated['raffle_id']);

                /**
                 * Ensure member belongs to this raffle
                 */
                $memberExists = $raffle->members()
                    ->where('members.id', $validated['member_id'])
                    ->exists();

                if (! $memberExists) {
                    throw new \Exception('Member not in this raffle');
                }

                /**
                 * Ensure item belongs to this raffle
                 */
                $raffleItem = $raffle->items()
                    ->where('items.id', $validated['item_id'])
                    ->first();

                if (! $raffleItem) {
                    throw new \Exception('Item not in this raffle');
                }

                /**
                 * Ensure enough quantity is available
                 */
                if ($raffleItem->pivot->quantity < $validated['quantity']) {
                    throw new \Exception('Not enough item quantity');
                }

                /**
                 *  Create distribution record (LOG of what happened)
                 */
                $distribution = RaffleDistribution::create([
                    'raffle_id' => $validated['raffle_id'],
                    'member_id' => $validated['member_id'],
                    'item_id'   => $validated['item_id'],
                    'quantity'  => $validated['quantity'],
                ]);

                /**
                 * Reduce item quantity in raffle_items pivot
                 */
                $raffle->items()->updateExistingPivot(
                    $validated['item_id'],
                    [
                        'quantity' => $raffleItem->pivot->quantity - $validated['quantity']
                    ]
                );

                return $distribution;
            });

            /**
             * Success response
             */
            return response()->json([
                'success' => true,
                'message' => 'Item distributed successfully',
                'data'    => $distribution,
            ], 201);

        } catch (\Exception $e) {
            /**
             * Any error inside the transaction
             * automatically rolls everything back
             */
            return response()->json([
                'success' => false,
                'message' => 'Failed to distribute item',
                'errors'  => $e->getMessage(),
            ], 422);
        }
    }
}
