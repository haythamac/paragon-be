<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Items extends Model
{
    /** @use HasFactory<\Database\Factories\ItemsFactory> */
    use HasFactory;
    protected $guarded = [];

    public function raffles()
    {
        return $this->belongsToMany(Raffle::class, 'raffle_item', 'item_id', 'raffle_id')
                    ->withPivot('quantity');
    }
}
