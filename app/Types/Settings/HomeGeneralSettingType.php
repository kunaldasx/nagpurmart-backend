<?php

namespace App\Types\Settings;

use App\Enums\Category\CategoryBackgroundTypeEnum;
use App\Interfaces\SettingInterface;
use App\Traits\SettingTrait;
use Illuminate\Validation\Rules\Enum;
use phpDocumentor\Reflection\Types\Nullable;

class HomeGeneralSettingType implements SettingInterface
{
    use SettingTrait;

    public string $title = "";
    public array $searchLabels = [];
    public string $backgroundType = "";
    public string $backgroundColor = "#ffffff";
    public string $backgroundImage = '';
    public string $fontColor = "#000000";
    public string $icon = '';
    public string $activeIcon = '';

    /**
     * Get Laravel validation rules for the properties
     *
     * @return array<string, array<string>>
     */
    protected static function getValidationRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'searchLabels' => ['nullable', 'array'],
            'backgroundType' => ['nullable', new Enum(CategoryBackgroundTypeEnum::class)],
            'backgroundColor' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'backgroundImage' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'icon'       => 'nullable|mimes:jpeg,png,jpg,webp,svg',
            'activeIcon' => 'nullable|mimes:jpeg,png,jpg,webp,svg',
            'fontColor' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ];
    }
}
