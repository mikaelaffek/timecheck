<?php

namespace App\Http\Requests\TimeRegistration;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Basic authorization - further checks for specific user_id will be in the controller
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
            'user_id' => 'sometimes|exists:users,id',
            'date' => 'required|date',
            'clock_in' => 'required|date_format:H:i:s',
            'clock_out' => 'nullable|date_format:H:i:s|after:clock_in',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string',
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
            'user_id.exists' => 'The selected user does not exist.',
            'date.required' => 'The date is required.',
            'date.date' => 'The date must be a valid date.',
            'clock_in.required' => 'The clock-in time is required.',
            'clock_in.date_format' => 'The clock-in time must be in the format HH:MM:SS.',
            'clock_out.date_format' => 'The clock-out time must be in the format HH:MM:SS.',
            'clock_out.after' => 'The clock-out time must be after the clock-in time.',
        ];
    }
}
