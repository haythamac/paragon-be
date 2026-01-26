<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use Illuminate\Http\Request;

class RaffleDistributionController extends Controller
{
    public function index(Raffle $raffle)
    {
        return $raffle->distributions()->with(['member','item'])->get();
    }
    
    public function auto()
    {
        // validate raffle, member
        // pick first available item
        // create record
    }

    public function manual()
    {
        // validate raffle, member, item, quantity
        // create record
    }
}
