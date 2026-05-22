<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'type'            => $this->type,
            'description'     => $this->description,
            'amount'          => (float) $this->amount,
            'date'            => $this->date?->toDateString(),
            'status'          => $this->status,
            'approval_status' => $this->approval_status,
            'category_id'     => $this->category_id,
            'project_id'      => $this->project_id,
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
