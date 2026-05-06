<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    /**
     * The cache key for the currency symbol.
     */
    protected const CURRENCY_SYMBOL_CACHE_KEY = 'currency_symbol';

    /**
     * The cache key for the currency code.
     */
    protected const CURRENCY_CODE_CACHE_KEY = 'currency_code';

    /**
     * The cache duration in seconds (1 day).
     */
    protected const CACHE_DURATION = 86400;

    /**
     * The setting service instance.
     */
    protected SettingService $settingService;

    /**
     * Request-level cache for currency symbol.
     */
    protected ?string $cachedSymbol = null;

    /**
     * Request-level cache for currency code.
     */
    protected ?string $cachedCode = null;

    /**
     * Create a new currency service instance.
     */
    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Get the currency symbol.
     */
    public function getSymbol(): string
    {
        // Return cached symbol if already retrieved in this request
        if ($this->cachedSymbol !== null) {
            return $this->cachedSymbol;
        }

        // Get from cache and store in request-level cache
        $this->cachedSymbol = Cache::remember(self::CURRENCY_SYMBOL_CACHE_KEY, self::CACHE_DURATION, function () {
            $systemSettings = $this->settingService->getSettingByVariable('system');
            return $systemSettings->value['currencySymbol'] ?? '$';
        });

        return $this->cachedSymbol;
    }

    /**
     * Get the currency code.
     */
    public function getCode(): string
    {
        // Return cached code if already retrieved in this request
        if ($this->cachedCode !== null) {
            return $this->cachedCode;
        }

        // Get from cache and store in request-level cache
        $this->cachedCode = Cache::remember(self::CURRENCY_CODE_CACHE_KEY, self::CACHE_DURATION, function () {
            $systemSettings = $this->settingService->getSettingByVariable('system');
            return $systemSettings->value['currency'] ?? 'USD';
        });

        return $this->cachedCode;
    }

    /**
     * Format a number as a currency.
     */
    public function format(?float $amount, int $decimals = 2): string
    {
        $amount = $amount ?? 0.0;
        return $this->getSymbol() . number_format($amount, $decimals);
    }

    /**
     * Clear the currency cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CURRENCY_SYMBOL_CACHE_KEY);
        Cache::forget(self::CURRENCY_CODE_CACHE_KEY);

        // Reset request-level cache
        $this->cachedSymbol = null;
        $this->cachedCode = null;
    }
}
