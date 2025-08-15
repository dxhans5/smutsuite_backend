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
        // Compute permissions as union of direct + role permissions, unique + sorted (names only)
        $permNames = collect();

        if ($this->relationLoaded('permissions')) {
            $permNames = $permNames->merge($this->permissions->pluck('name'));
        }

        if ($this->relationLoaded('roles')) {
            $permNames = $permNames->merge(
                $this->roles->flatMap(function ($role) {
                    return $role->permissions ? $role->permissions->pluck('name') : collect();
                })
            );
        }

        $permNames = $permNames->unique()->sort()->values();

        // Only include OTHER active identities in the list; the current one is provided as 'active_identity'
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
            'permissions'     => $this->when(true, fn () => $permNames),
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
