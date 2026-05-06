<?php

namespace App\Interfaces;

interface SettingInterface
{
    public static function fromArray(array $data): self;
    public function toJson(): string;
}
