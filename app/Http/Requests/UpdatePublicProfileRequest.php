<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePublicProfileRequest extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'display_name' => 'nullable|string|max:255',
            'avatar_url' => 'nullable|url',
            'tagline' => 'nullable|string|max:255',
            'pricing' => 'nullable|array',
            'about' => 'nullable|string',
            'is_visible' => 'boolean',
            'hide_from_locals' => 'boolean',
            'role' => 'nullable|string|in:creator,host,service_provider',
            'location' => 'nullable|string|max:255',
        ];
    }
}
