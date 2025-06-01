<?php

namespace App\Http\Requests\TimeRegistration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Basic authorization - further checks will be in the controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => 'sometimes|date',
            'clock_in' => 'sometimes|date_format:H:i:s',
            'clock_out' => 'nullable|date_format:H:i:s|after:clock_in',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,approved,rejected',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.date' => 'The date must be a valid date.',
            'clock_in.date_format' => 'The clock-in time must be in the format HH:MM:SS.',
            'clock_out.date_format' => 'The clock-out time must be in the format HH:MM:SS.',
            'clock_out.after' => 'The clock-out time must be after the clock-in time.',
            'status.in' => 'The status must be one of: pending, approved, rejected.',
        ];
    }
}
