<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Raffle extends Model
{
    /** @use HasFactory<\Database\Factories\RaffleFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'date',
        'members_joined',
        'item_count',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function members()
    {
        return $this->belongsToMany(Member::class, 'raffle_member');
    }

    public function items()
    {
        // This just references the items catalog
        return $this->belongsToMany(Items::class, 'raffle_item')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    // Calculate total items count
    public function calculateItemsCount(): int
    {
        return $this->items()->sum('raffle_item.quantity');
    }
}
