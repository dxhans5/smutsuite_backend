<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // If you want to restrict who can register, change this later
        return true;
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:40'],
            'date_of_birth' => ['required', 'date', function ($attribute, $value, $fail) {
                $age = Carbon::parse($value)->age;
                if ($age < 21) {
                    $fail(__('validation.custom.date_of_birth.age_check'));
                }
            }],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'city' => ['nullable', 'string', 'max:100'],
            'type' => ['required', Rule::in(['user','creator','service_provider','content_provider','host'])],
            'signup_context' => ['nullable', 'string', 'max:255'],
            'invited_by_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'google_id_token' => ['nullable', 'string'],
        ];
    }
}
