<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyIdentityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if session has required data
        return session()->has('voting.branch_id') && session()->has('voting.member_id');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'share_account_last4' => [
                'required',
                'string',
                'size:4',
                'regex:/^[0-9]{4}$/',
            ],
            'middle_name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'birth_date' => [
                'nullable',
                'date',
                'before:today',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'share_account_last4.required' => 'Please enter the last 4 digits of your share account.',
            'share_account_last4.size' => 'Please enter exactly 4 digits.',
            'share_account_last4.regex' => 'Share account must contain only numbers.',
            'birth_date.before' => 'Birth date must be in the past.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Ensure at least one security question is answered
            if (empty($this->middle_name) && empty($this->birth_date)) {
                $validator->errors()->add(
                    'verification',
                    'Please answer at least one security question (middle name or birth date).'
                );
            }
        });
    }
}
