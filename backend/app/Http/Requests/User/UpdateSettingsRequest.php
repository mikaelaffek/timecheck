<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Users can always update their own settings
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
            'enable_notifications' => 'sometimes|boolean',
            'auto_clock_out' => 'sometimes|boolean',
            'default_view' => 'sometimes|string|in:dashboard,time-registrations,reports',
            'time_format' => 'sometimes|string|in:12h,24h',
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
            'default_view.in' => 'The selected default view is invalid. Must be dashboard, time-registrations, or reports.',
            'time_format.in' => 'The selected time format is invalid. Must be 12h or 24h.',
        ];
    }
}
