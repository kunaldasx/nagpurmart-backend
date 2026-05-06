{{-- Full-screen subscription prompt for sellers without active subscription --}}
@php
    use App\Enums\SettingTypeEnum;use App\Models\Setting;
    use App\Models\SubscriptionPlan;use App\Services\SettingService;use Illuminate\Support\Facades\Cache;

    $showSubscriptionPrompt = false;

    if (
        !Setting::isSystemVendorTypeSingle()
        && Setting::isSubscriptionEnabled()
        && !session('subscription_prompt_shown')
    ) {
        $authUser = auth()->user();
        $sellerModel = $authUser?->seller();

        if ($sellerModel) {
            $hasActive = $sellerModel->activeSubscription()->exists();
            if (!$hasActive) {
                $showSubscriptionPrompt = true;
                $promptPlans = Cache::remember('subscription:plans:active:list', now()->addMinutes(10), function () {
                    return SubscriptionPlan::with('limits')
                        ->where('status', true)
                        ->orderByDesc('is_default')
                        ->orderBy('price')
                        ->get();
                });

                $subscriptionSettingService = app(SettingService::class);
                $promptSubscriptionSettings = $subscriptionSettingService->getSettingByVariable(SettingTypeEnum::SUBSCRIPTION());
                $promptSubscriptionSettingsValue = $promptSubscriptionSettings?->value ?? [];
            }
        }
    }
@endphp

@if($showSubscriptionPrompt)
    @php session(['subscription_prompt_shown' => true]); @endphp
    <div id="subscription-prompt-overlay"
         style="position:fixed;inset:0;z-index:9999;background:rgba(var(--tblr-body-color-rgb,24,36,51),.85);
                overflow-y:auto;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;padding:2rem 1rem;">
        <div style="width:100%;max-width:1140px;">
            {{-- Header --}}
            <div class="text-center mb-4">
                <h1 class="text-white fw-bold">{{ __('labels.choose_your_plan') ?? 'Choose Your Plan' }}</h1>
                <p class="text-white opacity-75">
                    {{ $promptSubscriptionSettingsValue['subscriptionDescription'] ?? __('labels.subscription_description_text') ?? 'Select a subscription plan to unlock all features.' }}
                </p>
            </div>

            {{-- Plans table --}}
            <div class="card shadow-lg">
                <div class="card-body p-0">
                    @include('components.subscription-pricing', [
                        'panel'               => 'seller',
                        'plans'               => $promptPlans,
                        'subscriptionSettings' => $promptSubscriptionSettingsValue,
                    ])
                </div>
            </div>

            {{-- Skip button --}}
            <div class="text-center mt-4">
                <button type="button" id="subscription-prompt-skip" class="btn btn-ghost-light btn-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" class="icon me-1 icon-2">
                        <path d="M18 6l-12 12"></path>
                        <path d="M6 6l12 12"></path>
                    </svg>
                    {{ __('labels.skip_for_now') ?? 'Skip for now' }}
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const overlay = document.getElementById('subscription-prompt-overlay');
            const skipBtn = document.getElementById('subscription-prompt-skip');

            if (skipBtn && overlay) {
                skipBtn.addEventListener('click', function () {
                    skipBtn.disabled = true;
                    skipBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> {{ __("labels.please_wait") ?? "Please wait…" }}';

                    axios.post('{{ route("seller.subscription-plans.skip") }}', {}, {
                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content}
                    }).then(function () {
                        overlay.style.transition = 'opacity .3s ease';
                        overlay.style.opacity = '0';
                        setTimeout(function () {
                            overlay.remove();
                        }, 300);
                    }).catch(function () {
                        overlay.style.transition = 'opacity .3s ease';
                        overlay.style.opacity = '0';
                        setTimeout(function () {
                            overlay.remove();
                        }, 300);
                    });
                });
            }
        });
    </script>
@endif
