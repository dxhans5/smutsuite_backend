<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the user resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'email'           => $this->email,

            // Collection of role names
            'roles'           => $this->roles?->pluck('name') ?? [],

            // Permissions should be pre-loaded via 'permissions' relationship or custom accessor
            'permissions' => collect($this->all_permissions)->pluck('name')->all(),

            // Whether the email is verified
            'email_verified'  => (bool) $this->email_verified_at,

            // The user's currently selected identity, if available
            'active_identity' => $this->whenLoaded('activeIdentity', fn () => new IdentityResource($this->activeIdentity)),
            'identities' => IdentityResource::collection(
                $this->identities ?? []
            ),

            // Timestamps
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
