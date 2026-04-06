<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RaffleTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'raffle_id',
        'number',
        'status',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'transaction_id',
        'payment_receipt_path',
        'reserved_at',
    ];

    public function raffle()
    {
        return $this->belongsTo(Raffle::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
