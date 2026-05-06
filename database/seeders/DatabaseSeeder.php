<?php

namespace Database\Seeders;

use App\Enums\DefaultSystemRolesEnum;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CountriesSeeder::class,
//            CategoriesSeeder::class,
            DefaultRolesSeeder::class,
//            SystemVendorTypeSeeder::class,
        ]);
//        super_admin
//        try {
//            $user = User::find(1);
//            $user->assignRole(DefaultSystemRolesEnum::SUPER_ADMIN());
//
//        } catch (\Throwable $th) {
//            dd($th->getMessage());
//        }
    }
}
