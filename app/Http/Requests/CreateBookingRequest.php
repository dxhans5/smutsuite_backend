<?php

namespace App\Http\Requests;

use App\Enums\BookingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'creator_identity_id' => ['required', 'exists:identities,id'],
            'requested_at' => ['required', 'date', 'after:now'],
            'booking_type' => ['required', new Enum(BookingType::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'timezone' => ['required', 'string', 'timezone'],
        ];
    }
}
