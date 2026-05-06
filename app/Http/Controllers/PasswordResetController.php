<?php

namespace App\Http\Controllers;

use App\Enums\SettingTypeEnum;
use App\Services\SettingService;
use App\Traits\AuthTrait;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class PasswordResetController extends Controller
{
    use AuthTrait, PanelAware;

    protected mixed $webSettings;
    public function __construct()
    {
        $webSettings = app(SettingService::class);
        $resource = $webSettings->getSettingByVariable(SettingTypeEnum::WEB());
        $this->webSettings = $resource ? ($resource->toArray(request())['value'] ?? []) : [];
    }

    /**
     * Show the forgot password form
     */
    public function showForgotPasswordForm(): View
    {
        return view($this->panelView('auth.forgot-password'));
    }

    /**
     * Handle forgot password request
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        // Find the user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            if ($request->expectsJson()) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    __('passwords.user'),
                    []
                );
            }
            return back()->withErrors(['email' => __('passwords.user')]);
        }

        // Check if user belongs to the current panel
        if (!$this->validateUserPanel($user)) {
            if ($request->expectsJson()) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    $this->getInvalidUserMessage(),
                    []
                );
            }
            return back()->withErrors(['email' => $this->getInvalidUserMessage()]);
        }

        // Generate a password reset token
        $token = app('auth.password.broker')->createToken($user);

        // Send the panel-specific password reset notification
        $notificationClass = $this->getNotificationClass();
        $user->notify(new $notificationClass($token));

        if ($request->expectsJson()) {
            return ApiResponseType::sendJsonResponse(
                true,
                __('passwords.sent'),
                []
            );
        }

        return back()->with('status', __('passwords.sent'));
    }

    /**
     * Show the reset password form
     */
    public function showResetPasswordForm(Request $request, string $token): View
    {
        return view($this->panelView('auth.reset-password'), [
            'token' => $token,
            'email' => $request->email
        ]);
    }

    /**
     * Handle password reset
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            if ($this->getLoginRoute() === 'login') {
                return redirect($this->webSettings['customerWebUrl'] ?? config('app.frontendUrl'));
            }
            return redirect()->route($this->getLoginRoute())->with('status', __($status));
        }

        return back()->withErrors(['email' => [__($status)]]);
    }
}
