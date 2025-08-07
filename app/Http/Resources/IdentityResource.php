<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IdentityResource extends JsonResource
{
    /**
     * Transform the identity resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'user_id'             => $this->user_id,

            // Core identity info
            'type'                => $this->type,
            'name'                => $this->name,
            'nickname'            => $this->nickname,
            'avatar_url'          => $this->avatar_url,

            // Status and lifecycle
            'is_active'           => (bool) $this->is_active,
            'verification_status' => $this->verification_status,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
