<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class NgoGrantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'agency'          => $this->agency,
            'contract_number' => $this->contract_number,
            'value'           => $this->value ? (float) $this->value : null,
            'status'          => $this->status,
            'start_date'      => $this->start_date?->toDateString(),
            'deadline'        => $this->deadline?->toDateString(),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
