<?php

namespace App\Http\Requests\SystemUser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemUserRequest extends FormRequest
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
        $seller = auth()->user()?->seller();

        $roleExistsRule = Rule::exists('roles', 'name');
        if ($seller) {
            $roleExistsRule = $roleExistsRule->where(function ($query) use ($seller) {
                $query->where('guard_name', 'seller')
                      ->where('team_id', $seller->id);
            });
        }

        return [
            'name' => 'required|string|max:255',
            'mobile' => 'required|numeric|unique:users,mobile,' . $this->route('id'),
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', $roleExistsRule],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string,string>
     */
    public function attributes(): array
    {
        return [
            'name' => trans('labels.name'),
            'mobile' => trans('labels.mobile'),
            'roles' => trans('labels.role'),
            'roles.*' => trans('labels.role'),
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'roles.array' => trans('validation.array', ['attribute' => trans('labels.role')]),
            'roles.*.exists' => trans('labels.role_not_found'),
        ];
    }
}
