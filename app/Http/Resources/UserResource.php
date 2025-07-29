<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'email'          => $this->email,
            'roles'          => $this->roles->pluck('name'),
            'permissions'    => $this->getRelation('all_permissions') ?? [],
            'email_verified' => (bool) $this->email_verified_at,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
