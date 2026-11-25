<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Item do catálogo de adicionais (fora do plano), cobrado na fatura mensal
 * junto com o plano. Cada item aponta para um produto AVULSO da AbacatePay
 * com o mesmo preço unitário.
 */
class BillingAddon extends Model
{
    public const BOT_NUMBER = 'bot_number';
    public const EMAIL_PACK = 'email_pack';

    protected $fillable = [
        'code', 'name', 'description', 'unit_price', 'unit_amount',
        'abacatepay_product_id', 'max_quantity', 'is_active',
    ];

    protected $casts = [
        'unit_price'   => 'decimal:2',
        'unit_amount'  => 'integer',
        'max_quantity' => 'integer',
        'is_active'    => 'boolean',
    ];

    public function unitCents(): int
    {
        return (int) round(((float) $this->unit_price) * 100);
    }
}
