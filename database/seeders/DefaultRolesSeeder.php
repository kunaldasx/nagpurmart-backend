<?php

namespace Database\Seeders;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\GuardNameEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DefaultRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => DefaultSystemRolesEnum::SUPER_ADMIN(), 'guard_name' => GuardNameEnum::ADMIN()],
            ['name' => DefaultSystemRolesEnum::SELLER(), 'guard_name' => GuardNameEnum::SELLER()],
            ['name' => DefaultSystemRolesEnum::CUSTOMER(), 'guard_name' => GuardNameEnum::WEB()],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role['name'],
                'guard_name' => $role['guard_name'],
            ]);
        }
    }
}
