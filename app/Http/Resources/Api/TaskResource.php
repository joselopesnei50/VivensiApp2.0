<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => $this->status,
            'priority'    => $this->priority,
            'project_id'  => $this->project_id,
            'assigned_to' => $this->assigned_to,
            'due_date'    => $this->due_date?->toDateString(),
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
