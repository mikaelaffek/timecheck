<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->route('user')->id),
            ],
            'personal_id' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('users')->ignore($this->route('user')->id),
            ],
            'role' => 'sometimes|in:employee,manager,admin',
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
            'email.unique' => 'This email is already registered.',
            'personal_id.unique' => 'This personal ID is already registered.',
            'role.in' => 'The selected role is invalid. Must be employee, manager, or admin.',
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Check if role is being updated and authorize it
        if ($this->has('role')) {
            $this->user()->can('updateRole', \App\Models\User::class);
        }
    }
}
