@php use App\Enums\Subscription\SubscriptionPlanKeyEnum; @endphp
<div class="card card-md mt-3">
    <div class="card-body">

        <h2 class="h2 text-center mb-3">{{ __('labels.subscription_feature') }}</h2>

        <p class="text-center text-muted">
            {!! __('labels.subscription_feature_intro_html') !!}
        </p>

        <p class="text-center text-muted">
            {{ __('labels.subscription_feature_disabled_note') }}
        </p>

        <div class="my-3">
            <h4 class="mb-2">{{ __('labels.subscription_features_included') }}</h4>

            <div class="d-flex flex-wrap gap-2 justify-content-center">
                @foreach(SubscriptionPlanKeyEnum::values() as $value)
                    <span class="badge bg-primary-lt text-primary">
                                            {{ ucwords(str_replace('_', ' ', strtolower($value))) }}
                                        </span>
                @endforeach
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form id="subscription-feature-form"
              action="{{ route('admin.subscription-feature.store') }}"
              method="POST">
            @csrf

            <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">

                <!-- ENABLE -->
                <label class="form-selectgroup-item flex-fill">
                    <input type="radio"
                           name="enableSubscription"
                           value="1"
                           class="form-selectgroup-input"
                        {{ old('enableSubscription', ($settings['enableSubscription'] ?? null) === true ? 1 : '') == 1 ? 'checked' : '' }}
                        {{ ($settings['enableSubscription'] ?? null) === true ? 'disabled' : '' }}>

                    <div class="form-selectgroup-label d-flex align-items-center p-3">
                        <div class="me-3">
                            <span class="form-selectgroup-check rounded-5"></span>
                        </div>
                        <div>
                            <p class="mb-0 fw-bold">{{ __('labels.enable_subscription') }}</p>
                            <p class="mb-0 text-muted small">
                                {{ __('labels.enable_subscription_desc') }}
                            </p>
                        </div>
                    </div>
                </label>

                <!-- DISABLE -->
                <label class="form-selectgroup-item flex-fill">
                    <input type="radio"
                           name="enableSubscription"
                           value="0"
                           class="form-selectgroup-input"
                        {{ old('enableSubscription', ($settings['enableSubscription'] ?? null) === false ? 0 : '') === 0 ? 'checked' : '' }}
                        {{ ($settings['enableSubscription'] ?? null) === true ? 'disabled' : '' }}>

                    <div class="form-selectgroup-label d-flex align-items-center p-3">
                        <div class="me-3">
                            <span class="form-selectgroup-check rounded-5"></span>
                        </div>
                        <div>
                            <p class="mb-0 fw-bold">{{ __('labels.disable_subscription') }}</p>
                            <p class="mb-0 text-muted small">
                                {{ __('labels.disable_subscription_desc') }}
                            </p>
                        </div>
                    </div>
                </label>

            </div>

            <div class="text-end mt-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#subscriptionModel" {{ ($settings['enableSubscription'] ?? null) === true ? 'disabled' : '' }}>
                    {{ __('labels.confirm_and_continue') }}
                </button>
            </div>
            <div
                class="modal modal-blur fade"
                id="subscriptionModel"
                tabindex="-1"
                role="dialog"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        <div class="modal-status bg-success"></div>
                        <div class="modal-body text-center py-4">
                            <!-- Download SVG icon from http://tabler.io/icons/icon/circle-check -->
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="icon mb-2 text-green icon-lg"
                            >
                                <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>
                                <path d="M9 12l2 2l4 -4"/>
                            </svg>
                            <h3>{{__('labels.confirm_subscription_setup')}}</h3>
                            <div class="text-secondary">
                                {{__('labels.you_are_about_to_configure_subscription_feature')}}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="w-100">
                                <div class="row">
                                    <div class="col">
                                        <a href="#" class="btn btn-outline-secondary w-100"
                                           data-bs-dismiss="modal">{{__('labels.cancel')}}</a>
                                    </div>
                                    <div class="col">
                                        <button type="submit" class="btn btn-success w-100"
                                                data-bs-dismiss="modal">{{__('labels.confirm_and_continue')}}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- WARNING -->
        <div class="alert alert-warning mt-4" role="alert">
            <strong>{{ __('labels.permanent_setting_title') }}</strong>
            {{ __('labels.permanent_subscription_warning') }}
        </div>

    </div>
</div>
