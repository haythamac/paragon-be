<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RaffleDistribution extends Model
{
    /** @use HasFactory<\Database\Factories\RaffleDistributionFactory> */
    use HasFactory;

    protected $fillable = [
        'raffle_id',
        'member_id',
        'item_id',
        'quantity',
    ];

    // Raffle this distribution belongs to
    public function raffle()
    {
        return $this->belongsTo(Raffle::class);
    }

    // Member who received the item
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    // Item that was distributed
    public function item()
    {
        return $this->belongsTo(Items::class);
    }
}
