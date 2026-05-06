@push('scripts')
    <script>
        (function(){
            const payBtn = document.getElementById('payBtn');
            const authUrl = @json($authorizationUrl ?? '');
            if (!payBtn) return;
            payBtn.addEventListener('click', function(){
                try {
                    window.disableLeaveWarning && window.disableLeaveWarning();
                    if (authUrl) {
                        window.location.href = authUrl;
                    } else {
                        alert('Authorization URL missing.');
                        window.enableLeaveWarning && window.enableLeaveWarning();
                    }
                } catch (e) {
                    window.enableLeaveWarning && window.enableLeaveWarning();
                }
            });
        })();
    </script>
@endpush
