<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RaffleTicket extends Model
{
    use HasFactory;

    /** Quanto tempo a reserva fica segura esperando a ONG confirmar o PIX. */
    public const RESERVATION_HOURS = 24;
    /** Antes de vencer, a ONG recebe um aviso com as reservas pendentes. */
    public const REMINDER_HOURS_BEFORE = 4;
    /** Máximo de números por compra (evita que uma pessoa trave a rifa inteira). */
    public const MAX_PER_RESERVATION = 50;

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
        'reservation_token',
        'reminder_sent_at',
    ];

    protected $casts = [
        'reserved_at'      => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    /** Dados do comprador zerados ao liberar o número. */
    public const RELEASED_ATTRIBUTES = [
        'status'            => 'available',
        'buyer_name'        => null,
        'buyer_email'       => null,
        'buyer_phone'       => null,
        'reserved_at'       => null,
        'reservation_token' => null,
        'reminder_sent_at'  => null,
    ];

    public function expiresAt(): ?\Illuminate\Support\Carbon
    {
        return $this->reserved_at?->copy()->addHours(self::RESERVATION_HOURS);
    }

    /** Números pendentes da mesma compra (mesmo token; sem token, só ele). */
    public function siblingsQuery()
    {
        $q = static::where('raffle_id', $this->raffle_id);
        return $this->reservation_token
            ? $q->where('reservation_token', $this->reservation_token)
            : $q->whereKey($this->id);
    }

    public function raffle()
    {
        return $this->belongsTo(Raffle::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
