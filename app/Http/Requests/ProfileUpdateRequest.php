<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[A-Za-zÑñ .\'-]+$/',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'pos_layout' => ['nullable', 'string', Rule::in(['left', 'right'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Name may only contain letters, spaces, Ñ, ñ, hyphen, apostrophe, and period.',
            'name.min' => 'Name must be at least 2 characters.',
            'name.max' => 'Name must not exceed 100 characters.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email must not exceed 255 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->name),
            'email' => strtolower(trim($this->email)),
        ]);
    }
}
