<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CreditTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'player_id',
        'amount',
        'source',
        'match_id',
    ];

    public function player()
    {
        return $this->belongsTo(User::class, 'player_id');
    }
}
