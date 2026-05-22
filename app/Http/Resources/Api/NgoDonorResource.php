<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class NgoDonorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'type'       => $this->type,
            'address'    => $this->address,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
