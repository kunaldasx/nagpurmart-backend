
<form id="rzpPaymentForm" method="POST" action="#" style="display:none;">
    @csrf
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
    <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" value="{{ $orderId }}">
    <input type="hidden" name="razorpay_signature" id="razorpay_signature">
    <input type="hidden" name="subscription_transaction_id" value="{{ $transactionId }}">
    <input type="hidden" name="seller_subscription_id" value="{{ $subscriptionId }}">
    <input type="hidden" name="seller_id" value="{{ $sellerId }}">
    <input type="hidden" name="gateway" value="razorpay">
</form>

@push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        (function () {
            let btn = document.getElementById('payBtn');
            if (!btn) return;
            btn.addEventListener('click', function () {
                var options = {
                    key: "{{ $keyId }}",
                    amount: {{ (int)($amountPaise ?? 0) }},
                    currency: "{{ $currency ?? 'INR' }}",
                    name: "Seller Subscription",
                    description: "{{ $planName ?? 'Subscription' }}",
                    order_id: "{{ $orderId }}",
                    notes: {
                        user_id: "{{ $sellerId }}",
                        type: 'subscription_payment',
                        subscription_transaction_id: "{{ $transactionId }}",
                        seller_subscription_id: "{{ $subscriptionId }}",
                        seller_id: "{{ $sellerId }}"
                    },
                    handler: function () {
                        try { if (window.disableLeaveWarning) window.disableLeaveWarning(); } catch (e) {}
                        window.location.href = "{{ route('subscription-payment.status', ['transaction' => $transactionUuid]) }}";
                    },
                    theme: {color: "#0ea5e9"}
                };
                let rzp1 = new Razorpay(options);
                rzp1.open();
            });
        })();
    </script>
@endpush
