<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Contract extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'title',
        'content',
        'signer_name',
        'signer_email',
        'signer_address',
        'signer_phone',
        'signer_cpf',
        'signer_rg',
        'signer_ip',
        'signer_user_agent',
        'token',
        'status',
        'signature_image',
        'signed_at',
        'public_sign_expires_at',
        'public_viewed_at',
        'document_hash',
        'signature_hash',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'public_sign_expires_at' => 'datetime',
        'public_viewed_at' => 'datetime',
    ];
}
