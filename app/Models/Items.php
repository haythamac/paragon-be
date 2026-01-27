<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Items extends Model
{
    /** @use HasFactory<\Database\Factories\ItemsFactory> */
    use HasFactory;
    protected $guarded = [];

    // Raffles this item belongs to
    public function raffles()
    {
        return $this->belongsToMany(Raffle::class, 'raffle_item', 'item_id', 'raffle_id')
                    ->withPivot('quantity');
    }

    // Distributions of this item
    public function distributions()
    {
        return $this->hasMany(RaffleDistribution::class);
    }
}
