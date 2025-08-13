<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'         => (string) $this->id,
            'type'       => class_basename($this->type),
            'data'       => $this->data,
            'read_at'    => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
