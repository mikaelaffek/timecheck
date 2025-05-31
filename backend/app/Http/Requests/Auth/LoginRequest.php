<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'personal_id' => 'required|string',
            'password' => 'required|string',
        ];
    }

    /**
     * Authenticate the user.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): User
    {
        // Make personal_id case-insensitive by using whereRaw with UPPER function
        $user = User::whereRaw('UPPER(personal_id) = ?', [strtoupper($this->personal_id)])->first();

        if (!$user || !Hash::check($this->password, $user->password)) {
            throw ValidationException::withMessages([
                'personal_id' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $user;
    }
}
