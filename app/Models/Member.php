<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    /** @use HasFactory<\Database\Factories\MemberFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
        'power',
        'role',
        'class',
    ];

    protected $casts = [
        'level' => 'integer',
        'power' => 'integer',
    ];

    // Raffles this member has joined
    public function raffles()
    {
        return $this->belongsToMany(Raffle::class, 'raffle_member', 'member_id', 'raffle_id');
    }

    // Items this member received
    public function distributions()
    {
        return $this->hasMany(RaffleDistribution::class);
    }
    
}
