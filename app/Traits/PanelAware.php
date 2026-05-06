<?php

namespace App\Traits;

use App\Exceptions\SellerNotFoundException;
use App\Notifications\AdminPasswordResetNotification;
use App\Notifications\SellerPasswordResetNotification;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Notifications\ResetPassword;

trait PanelAware
{
    /**
     * Determine the current panel: 'admin', 'seller', or 'customer'
     */
    public function getPanel(): string
    {
        // Example logic: adjust as needed for your app structure.
        // You could check route prefix, guard, middleware, etc.
        if (request()->is('admin/*') || request()->routeIs('admin.*')) {
            return 'admin';
        } elseif (request()->is('seller/*') || request()->routeIs('seller.*')) {
            return 'seller';
        } elseif (request()->is('api/*') || request()->routeIs('api.*') || request()->expectsJson()) {
            return 'customer';
        }
        // Default fallback for customer/web requests
        return 'customer';
    }

    /**
     * Helper to generate the view path based on the panel.
     *
     * @param string $view   E.g. 'brands.index'
     * @return string        E.g. 'admin.brands.index' or 'seller.brands.index'
     */
    public function panelView(string $view): string
    {
        return $this->getPanel() . '.' . $view;
    }

    public function ensureSeller(): mixed
    {
        $user = auth()->user();
        if ($this->getPanel() == 'seller' && $user->seller() === null) {
            throw new SellerNotFoundException();
        }
        return $user->seller();
    }

    /**
     * Get the notification class for the current panel
     */
    public function getNotificationClass(): string
    {
        $panel = $this->getPanel();

        return match ($panel) {
            'admin' => AdminPasswordResetNotification::class,
            'seller' => SellerPasswordResetNotification::class,
            default => ResetPassword::class,
        };
    }

    /**
     * Get the login route for the current panel
     */
    public function getLoginRoute(): string
    {
        $panel = $this->getPanel();
        return match ($panel) {
            'admin' => 'admin.login',
            'seller' => 'seller.login',
            'customer' => 'login', // Default Laravel login route for customers
            default => 'login',
        };
    }

    /**
     * Get the panel-specific error message for invalid user
     */
    public function getInvalidUserMessage(): string
    {
        $panel = $this->getPanel();

        return match ($panel) {
            'admin' => 'This email is not associated with an admin account.',
            'seller' => 'This email is not associated with a seller account.',
            'customer' => 'This email is not associated with a customer account.',
            default => 'This email is not associated with a customer account.',
        };
    }

    /**
     * Validate if user belongs to the current panel
     */
    public function validateUserPanel($user): bool
    {
        if (!$user) {
            return false;
        }

        $panel = $this->getPanel();

        // For customer panel, accept users without access_panel or with null access_panel
        if ($panel === 'customer') {
            return true;
        }

        // For admin and seller panels, require specific access_panel
        if (!$user->access_panel) {
            return false;
        }

        return $user->access_panel->value === $panel;
    }
}
