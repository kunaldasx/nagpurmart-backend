<div class="d-flex">
    @if($processPermission ?? false)

        <button type="button" class="btn btn-primary btn-sm process-withdrawal-request"
                data-id="{{ $id }}"
                data-delivery-boy-id="{{ $delivery_boy_id }}"
                data-delivery-boy-name="{{$delivery_boy_name}}"
                data-amount="{{ $amount }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 class="icon icon-tabler icons-tabler-outline icon-tabler-wallet">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path
                    d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12"/>
                <path d="M20 12v4h-4a2 2 0 0 1 0 -4h4"/>
            </svg>
            {{ __('labels.process_request') }}
        </button>
    @endif
    <a href="{{ route('admin.delivery-boy-withdrawals.show', $id) }}" class="btn btn-info btn-sm ms-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="icon icon-tabler icons-tabler-outline icon-tabler-eye">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
            <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
        </svg>
        {{ __('labels.view') }}
    </a>
</div>
