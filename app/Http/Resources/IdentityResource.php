<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdentityResource extends JsonResource
{
    /**
     * Transform the identity resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'                  => (string) $this->id,
            'user_id'             => (string) $this->user_id,

            // Core identity info
            'alias'               => $this->alias ?? null,
            'type'                => $this->type,
            'label'               => $this->label ?? null,
            'name'                => $this->name ?? null,
            'nickname'            => $this->nickname ?? null,
            'avatar_url'          => $this->avatar_url ?? null,

            // Status and lifecycle
            'status'              => $this->status ?? null,
            'is_active'           => (bool) $this->is_active,
            'verification_status' => $this->verification_status ?? null,

            // Timestamps
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
