<?php

namespace Froiden\LaravelInstaller\Controllers;

use Froiden\LaravelInstaller\Helpers\Reply;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

class SuperAdminController extends Controller
{
    /**
     * Show the Super Admin details form.
     *
     * @return \Illuminate\View\View
     */
    public function form()
    {
        return view('installer::super_admin');
    }

    /**
     * Validate and store Super Admin details in session. Actual user creation
     * happens after migrations (final step).
     *
     * @param Request $request
     * @return array
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'mobile' => 'nullable|string|max:20',
        ];

        $validated = $request->validate($rules);

        // Save to session for use after migrations (may not persist if SESSION_DRIVER=array)
        session([
            'install_super_admin' => [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'mobile' => $validated['mobile'] ?? null,
            ],
        ]);

        // Fallback: persist to a temporary file so installer continues to work without session persistence
        try {
            $payload = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'mobile' => $validated['mobile'] ?? null,
                'saved_at' => now()->toIso8601String(),
            ];
            $dir = storage_path('app');
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            File::put(storage_path('app/install_super_admin.json'), json_encode($payload));
        } catch (\Throwable $e) {
            // ignore file persistence errors so user can still proceed
        }

        return Reply::redirect(route('LaravelInstaller::requirements'), 'Super admin details saved. Continue installation.');
    }
}
