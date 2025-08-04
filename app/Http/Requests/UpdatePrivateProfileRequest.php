<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrivateProfileRequest extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'notes' => 'nullable|array',
            'journal' => 'nullable|array',
            'favorite_kinks' => 'nullable|array',
            'mood' => 'nullable|string|max:255',
            'emotional_state' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:255',
            'custom_fields' => 'nullable|array',
        ];
    }
}
