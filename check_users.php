<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Checking Users in Database ===\n\n";

try {
    // Check total users
    $totalUsers = \App\Models\User::count();
    echo "Total users in database: {$totalUsers}\n\n";

    // Check admin users
    $adminUsers = \App\Models\User::where('access_panel', 'admin')->get();
    echo "Admin users found: {$adminUsers->count()}\n";
    foreach ($adminUsers as $admin) {
        echo "  - ID: {$admin->id}, Name: {$admin->name}, Email: {$admin->email}\n";
    }

    // Check all user roles
    echo "\nAll user roles:\n";
    $roles = \App\Models\User::select('access_panel')->distinct()->pluck('access_panel');
    foreach ($roles as $role) {
        $count = \App\Models\User::where('access_panel', $role)->count();
        echo "  - {$role}: {$count} users\n";
    }

    // Check if there are any users with email
    echo "\nUsers with email addresses:\n";
    $usersWithEmail = \App\Models\User::whereNotNull('email')->limit(5)->get();
    foreach ($usersWithEmail as $user) {
        echo "  - ID: {$user->id}, Role: {$user->role}, Email: {$user->email}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
