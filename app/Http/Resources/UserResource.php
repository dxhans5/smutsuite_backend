<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        // Ensure we only expose OTHER active identities (exclude the active one)
        $activeIdentityId = $this->active_identity_id;

        $identities = $this->whenLoaded('identities', function () use ($activeIdentityId) {
            return $this->identities
                ->where('is_active', true)
                ->when($activeIdentityId, fn ($c) => $c->where('id', '!=', $activeIdentityId))
                ->values();
        }, collect());

        return [
            'id'              => (string) $this->id,
            'name'            => $this->name,
            'display_name'    => $this->display_name,
            'email'           => $this->email,
            'roles'           => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()),
            'permissions'     => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('name')->values()),
            'all_permissions' => $this->when(isset($this->all_permissions), fn () => $this->all_permissions->pluck('name')->values()),
            'email_verified'  => (bool) $this->email_verified_at,

            // The current identity, if loaded
            'active_identity' => $this->whenLoaded('activeIdentity', fn () => new IdentityResource($this->activeIdentity)),

            // Other active identities only (not including active_identity)
            'identities'      => IdentityResource::collection($identities),

            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
