<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'days_before' => ['required', 'integer', 'min:1', 'max:30'],
            'email_enabled' => ['boolean'],
            'push_enabled' => ['boolean'],
        ];
    }
}
