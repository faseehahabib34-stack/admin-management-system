<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $authUser = $this->user();
        $targetUser = $this->route('user');

        if (! $authUser || ! $targetUser) {
            return false;
        }

        if (! $authUser->can('edit_users')) {
            return false;
        }

        if ($authUser->id === $targetUser->id) {
            return true;
        }

        $role = $this->input('role');

        if (! is_string($role) || $role === '') {
            return false;
        }

        if ($authUser->hasRole('Super Admin')) {
            return true;
        }

        if ($authUser->hasRole('Admin')) {
            return in_array($role, ['Admin', 'User'], true);
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],

            'role' => [
                'nullable',
                'string',
                'in:Super Admin,Admin,User',
            ],

            'status' => [
                'required',
                'string',
                'in:active,suspended,deactivated',
            ],
        ];
    }

    /**
     * Get the validation error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',

            'email.required' => 'The email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already in use.',

            'password.min' => 'The password must be at least 8 characters long.',
            'password.confirmed' => 'The password confirmation does not match.',

            'role.in' => 'The selected role is invalid.',

            'status.required' => 'Please select a status for this user.',
            'status.in' => 'The selected status is invalid.',
        ];
    }
}