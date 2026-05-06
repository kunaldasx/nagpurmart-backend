<?php

namespace App\Http\Requests\Roles;

use App\Enums\GuardNameEnum;
use App\Traits\PanelAware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreRoleRequest extends FormRequest
{
    use PanelAware;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Admin panel: role name unique per guard_name = admin
        if ($this->getPanel() === 'admin') {
            return [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('roles', 'name')->where(fn ($q) => $q->where('guard_name', GuardNameEnum::ADMIN())),
                ],
            ];
        }

        // Seller panel: role name unique per team_id and guard_name = seller
        if ($this->getPanel() === 'seller') {
            $seller = $this->ensureSeller();
            return [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('roles', 'name')->where(fn ($q) => $q
                        ->where('guard_name', GuardNameEnum::SELLER())
                        ->where('team_id', optional($seller)->id)
                    ),
                ],
            ];
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }
}
