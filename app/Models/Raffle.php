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

    // Members of this raffle
    public function members()
    {
        return $this->belongsToMany(Member::class, 'raffle_member', 'raffle_id', 'member_id')
                    ->withTimestamps();
    }

    // Items included in this raffle
    public function items()
    {
        return $this->belongsToMany(Items::class, 'raffle_item' ,'raffle_id', 'item_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    // Distributions of items for this raffle
    public function distributions()
    {
        return $this->hasMany(RaffleDistribution::class);
    }

    // Calculate total items count
    public function calculateItemsCount(): int
    {
        return $this->items()->sum('raffle_item.quantity');
    }

    public function getMembersCountAttribute()
    {
        return $this->members()->count();
    }

    public function getItemsCountAttribute()
    {
        return $this->items()->sum('quantity');
    }
}
