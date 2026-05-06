@push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        (function(){
            const publishableKey = @json($publishableKey ?? '');
            const clientSecret = @json($clientSecret ?? '');
            const payBtn = document.getElementById('payBtn');

            if (!publishableKey || !clientSecret) {
                console.error('Stripe keys not configured');
                payBtn.disabled = true;
                return;
            }

            const stripe = Stripe(publishableKey);

            const handleError = (error) => {
                const messageContainer = document.querySelector('#error-message');
                messageContainer.textContent = error.message;
                payBtn.disabled = false;
            }

            const elements = stripe.elements({ clientSecret });
            const paymentElement = elements.create("payment");
            paymentElement.mount("#payment-element");

            payBtn.addEventListener('click', async function () {
                try {
                    window.disableLeaveWarning && window.disableLeaveWarning();
                    payBtn.disabled = true;

                    const { error: submitError } = await elements.submit();
                    if (submitError) {
                        handleError(submitError);
                        window.enableLeaveWarning && window.enableLeaveWarning();
                        return;
                    }

                    const { error } = await stripe.confirmPayment({
                        elements,
                        clientSecret: clientSecret,
                        confirmParams: {
                            return_url: "{{ route('subscription-payment.status', ['transaction' => $transactionUuid]) }}",
                        }
                    });

                    if (error) {
                        handleError(error);
                        window.enableLeaveWarning && window.enableLeaveWarning();
                        return;
                    }

                    alert(@json(__('labels.payment_success_message')));
                    setTimeout(function(){ location.reload(); }, 1500);
                } catch (e) {
                    alert('Something went wrong.');
                    payBtn.disabled = false;
                    window.enableLeaveWarning && window.enableLeaveWarning();
                }
            });
        })();
    </script>
@endpush
