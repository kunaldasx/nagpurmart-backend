@if($mode === 'settle')
    <div class="d-flex justify-content-center">
        @if($settlePermission ?? false)
            <button class="btn btn-icon btn-outline-primary settle-commission-btn"
                    data-id="{{ $id }}"
                    data-order-id="{{ $orderId ?? '' }}"
                    data-store="{{ $store_name ?? '' }}"
                    data-product="{{ $productTitle ?? '' }}"
                    data-commission="{{ $adminCommissionAmount ?? '' }}"
                    data-amount="{{ $amountToPay ?? '' }}"
                    data-settle-url="{{ $settleUrl ?? '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="icon icon-tabler icons-tabler-outline icon-tabler-cash">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <rect x="7" y="9" width="14" height="10" rx="2"/>
                    <circle cx="14" cy="14" r="2"/>
                    <path d="M17 9v-2a2 2 0 0 0 -2 -2h-10a2 2 0 0 0 -2 2v6a2 2 0 0 0 2 2h2"/>
                </svg>
            </button>
        @endif
    </div>
@else
    <div>
        <x-partial-actions modelName="{{$modelName}}" id="{{$id}}" title="{{$title}}" mode="{{$mode}}"
                           route="{{$route ?? null}}" editPermission="{{$editPermission ?? false}}"
                           deletePermission="{{$deletePermission ?? false}}"/>
    </div>
@endif
