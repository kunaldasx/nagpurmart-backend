<div class="d-flex">
    <button type="button" class="btn btn-primary btn-sm process-cash-submission"
            data-id="{{ $id }}"
            data-order-id="{{ $order_id }}"
            data-delivery-boy-id="{{ $delivery_boy_id }}"
            data-delivery-boy-name="{{$delivery_boy_name}}"
            data-cash-collected="{{ $cod_cash_collected }}"
            data-cash-submitted="{{ $cod_cash_submitted }}"
            data-remaining-amount="{{ $remaining_amount }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-cash">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <rect x="7" y="9" width="14" height="10" rx="2" />
            <circle cx="14" cy="14" r="2" />
            <path d="M17 9v-2a2 2 0 0 0 -2 -2h-10a2 2 0 0 0 -2 2v6a2 2 0 0 0 2 2h2" />
        </svg>
        {{ __('labels.process_submission') }}
    </button>
</div>
