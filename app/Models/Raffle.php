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
}
