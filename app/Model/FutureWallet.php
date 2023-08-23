<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\User;

class FutureWallet extends Model
{
    protected $fillable = [
        'wallet_name',
        'user_id',
        'coin_id',
        'coin_type',
        'balance',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
