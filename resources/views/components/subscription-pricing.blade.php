@php
    use App\Enums\ActiveInactiveStatusEnum;use App\Enums\Subscription\SubscriptionPlanKeyEnum;
    use Illuminate\Support\Str;

    $plans = $plans ?? collect();
    $keys = SubscriptionPlanKeyEnum::values();
@endphp

<div class="table-responsive">
    <table class="table table-vcenter table-bordered table-nowrap card-table">
        <thead>
        <tr>
            <td class="w-50">
                <h2>{{ $subscriptionSettings['subscriptionHeading'] ?? __('labels.subscription_plans') }}</h2>
                <div class="text-secondary text-wrap">
                    {{ $subscriptionSettings['subscriptionDescription'] ?? __('labels.subscription_description_text') }}
                </div>
            </td>
            @forelse($plans as $plan)
                @if($plan->status == ActiveInactiveStatusEnum::ACTIVE())
                    @php $limitMap = $plan->limits?->pluck('value','key')->toArray() ?? []; @endphp
                    <td class="text-center align-top position-relative {{ $plan->is_recommended ? 'border-primary border border-2' : '' }}">
                        @if($plan->is_recommended)
                            <div class="bg-primary ribbon ribbon-bookmark ribbon-top plan-ribbon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" class="icon icon-3">
                                    <path
                                        d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z"></path>
                                </svg>
                            </div>
                        @endif
                        <div class="d-flex justify-content-center"><p
                                class="text-uppercase text-secondary font-weight-medium mb-0" style="max-width: 400px;white-space: break-spaces;">{{ $plan->name }}</p></div>
                        <div class="my-2">
                            <div
                                class="display-6 fw-bold my-2">{{ $plan->is_free ? __('labels.free') : $systemSettings['currencySymbol'] . number_format((float)($plan->price ?? 0), 2) }}</div>
                        </div>
                        <div class="mb-2">
                            @if($plan->is_default)
                                <span class="badge bg-blue-lt">{{ __('labels.default_plan') }}</span>
                            @endif
                        </div>
                        <div class="small text-secondary mb-2">
                            {{ __('labels.duration') }}:
                            {{ $plan->duration_type === 'unlimited' ? __('labels.unlimited') : (($plan->duration_days ?? 0).' '.__('labels.days')) }}
                        </div>
                        <a href="{{ (!empty($panel) && $panel == 'seller') ? route('seller.subscription-plans.show', ['plan' => $plan->id]) : "javascript:void(0)" }}"
                           class="btn {{ $plan->is_recommended ? 'btn-primary' : '' }} w-100">{{ __('labels.choose_plan') ?? 'Choose plan' }}</a>

                    </td>
                @endif
            @empty
                <td class="text-center text-secondary">{{ __('labels.no_data_found') }}</td>
            @endforelse
        </tr>
        </thead>
        <tbody>
        <tr class="bg-surface-tertiary">
            <th colspan="{{ max($plans->count(), 1) + 1 }}"
                class="subheader">{{ __('labels.plan_configurations') }}</th>
        </tr>
        @foreach($keys as $key)
            <tr>
                <td class="text-capitalize">{{ Str::replace('_',' ', $key) }}</td>
                @foreach($plans as $plan)
                    @php $limitMap = $plan->limits?->pluck('value','key')->toArray() ?? [];
                    @endphp
                    <td class="text-center">{{ $limitMap[$key] ?? __('labels.unlimited') }}</td>
                @endforeach
                @if($plans->isEmpty())
                    <td class="text-center">-</td>
                @endif
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td></td>
            @foreach($plans as $plan)
                <td>
                    <a href="{{ (!empty($panel) && $panel == 'seller') ? route('seller.subscription-plans.show', ['plan' => $plan->id]) : "javascript:void(0)" }}"
                       class="btn {{ $plan->is_recommended ? 'btn-primary' : '' }} w-100">{{ __('labels.choose_plan') ?? 'Choose plan' }}</a>
                </td>
            @endforeach
            @if($plans->isEmpty())
                <td></td>
            @endif
        </tr>
        </tfoot>
    </table>
</div>
