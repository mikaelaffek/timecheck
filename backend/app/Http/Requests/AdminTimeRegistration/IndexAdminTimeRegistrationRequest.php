<?php

namespace App\Http\Requests\AdminTimeRegistration;

use Illuminate\Foundation\Http\FormRequest;

class IndexAdminTimeRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow admin and manager users to access this endpoint
        return $this->user() && ($this->user()->isAdmin() || $this->user()->isManager());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ];
    }
}
