<?php

namespace App\Http\Requests\TimeRegistration;

use Illuminate\Foundation\Http\FormRequest;

class IndexTimeRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // All authenticated users can view time registrations
        // Further authorization checks are in the controller
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
            'date' => 'sometimes|date',
            'start_date' => 'sometimes|date|required_with:end_date',
            'end_date' => 'sometimes|date|required_with:start_date|after_or_equal:start_date',
            'status' => 'sometimes|in:pending,approved,rejected',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }
}
