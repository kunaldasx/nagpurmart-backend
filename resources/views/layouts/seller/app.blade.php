@extends('layouts.main')

{{-- @section('title', 'Admin Dashboard') --}}

@section('header_data')
    @php
        $page_title = $page_title ?? 'Seller Dashboard';
        $page_pretitle = $page_pretitle ?? 'Overview';
    @endphp
@endsection
@section('content')
    @if(empty($page) || $page != 'login')
        @include('layouts.partials._header', [
            'page_title' => $page_title ?? 'Seller Dashboard',
            'page_pretitle' => $page_pretitle ?? 'Overview',
        ])
    @endif
    @include('seller.partials._subscription-prompt')
    <div class="page-body">
        <div class="container-xl">
            @yield('seller-content')
        </div>
    </div>
    <div class="modal modal-blur fade" id="newRegularOrderModal" data-pending-url="{{ route('seller.orders.pending-regular') }}" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header new-order-header">
                    <div>
                        <span class="new-order-kicker">Incoming order</span>
                        <h3 class="modal-title">New order</h3>
                        <p class="mb-0">Accept the order, verify every item, then start preparing.</p>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="new-order-pulse text-center">
                        <div id="new-order-timer-ring" aria-label="Order waiting time"><span><small>WAITING</small><strong id="new-order-timer">00:00</strong><em>response time</em></span></div>
                    </div>
                    <div id="new-order-summary"></div>
                    <div id="new-order-items" class="mt-3"></div>
                    <div id="new-order-verification" class="mt-3 d-none"></div>
                    <div class="alert alert-danger d-none mt-3 mb-0" id="new-order-error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-lg w-100 d-none" id="new-order-dismiss">Close after fixing barcode</button>
                    <button type="button" class="btn btn-success btn-lg w-100" id="new-order-accept">Accept order</button>
                </div>
            </div>
        </div>
    </div>
    <style>
        #newRegularOrderModal .modal-content { overflow: hidden; border: 0; border-radius: 18px; box-shadow: 0 24px 70px rgba(20, 36, 61, .28); }
        .new-order-header { align-items: center; padding: 1.4rem 1.5rem 1.25rem; color: #fff; background: linear-gradient(135deg, #126fd1 0%, #0754a8 100%); border: 0; }
        .new-order-header .modal-title { margin-top: .2rem; font-size: 1.35rem; }
        .new-order-header p { color: rgba(255,255,255,.78); font-size: .86rem; }
        .new-order-kicker { display: inline-flex; align-items: center; gap: .4rem; color: #bfe0ff; font-size: .7rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .new-order-kicker::before { content: ""; width: .45rem; height: .45rem; border-radius: 50%; background: #62df83; box-shadow: 0 0 0 4px rgba(98,223,131,.18); }
        .new-order-pulse { margin: -2.9rem auto 1rem; position: relative; z-index: 2; }
        #new-order-timer-ring { --timer-progress: 0deg; width: 132px; height: 132px; margin: 0 auto; border: 7px solid #fff; border-radius: 50%; display: grid; place-items: center; background: conic-gradient(#2fb344 var(--timer-progress), #dce9f6 0deg); position: relative; box-shadow: 0 10px 26px rgba(28, 84, 137, .2); }
        #new-order-timer-ring::before { content: ""; position: absolute; inset: 7px; border-radius: 50%; background: #f7fbff; }
        #new-order-timer-ring > span { position: relative; z-index: 1; color: #16426b; text-align: center; line-height: 1.1; }
        #new-order-timer-ring small { display: block; color: #6a8297; font-size: .62rem; font-weight: 700; letter-spacing: .1em; }
        #new-order-timer { display: block; color: #126fd1; font-size: 1.65rem; font-weight: 800; }
        #new-order-timer-ring em { display: block; margin-top: .25rem; color: #8ca0b2; font-size: .58rem; font-style: normal; }
        #new-order-summary { color: #536579; }
        .new-order-summary-card { padding: 1rem; border: 1px solid #e5edf5; border-radius: 12px; background: #f8fbfe; }
        .new-order-summary-card .order-number { color: #183d63; font-size: 1.05rem; font-weight: 800; }
        .new-order-summary-card .customer-line { color: #4d6278; }
        .new-order-meta { display: grid; grid-template-columns: repeat(2, 1fr); gap: .55rem; margin-top: .9rem; }
        .new-order-meta-item { min-width: 0; padding: .65rem .7rem; border-radius: 9px; background: #fff; }
        .new-order-meta-label { display: block; color: #8a9bad; font-size: .64rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .new-order-meta-value { display: block; color: #274b6c; font-size: .78rem; font-weight: 700; line-height: 1.25; overflow-wrap: anywhere; }
        #new-order-items { color: #536579; }
        #new-order-items > div { gap: .8rem; align-items: center; }
        .new-order-verification-card { padding: 1rem; border: 1px solid #dce7f0; border-radius: 10px; background: #f8fbfe; }
        .new-order-verification-status { min-height: 1.5rem; font-weight: 700; }
        .new-order-verification-status.is-valid { color: #16803c; }
        .new-order-verification-status.is-invalid { color: #c13535; }
        .new-order-verification-card input { min-height: 48px; }
        .new-order-quantity-panel { padding: .9rem; border: 2px solid #126fd1; border-radius: 10px; background: #edf6ff; }
        .new-order-quantity-panel input { min-height: 64px; font-size: 1.5rem; font-weight: 800; text-align: center; }
        .new-order-field-error { min-height: 1.25rem; margin-top: .25rem; color: #c13535; font-size: .82rem; font-weight: 700; }
        .new-order-checklist { margin-bottom: .85rem; border: 1px solid #dce7f0; border-radius: 10px; overflow: hidden; }
        .new-order-checklist-item { display: flex; align-items: center; gap: .65rem; padding: .6rem .75rem; border-bottom: 1px solid #e7edf3; background: #fff; }
        .new-order-checklist-item:last-child { border-bottom: 0; }
        .new-order-checklist-icon { width: 1.4rem; flex: 0 0 1.4rem; font-size: 1.1rem; font-weight: 800; text-align: center; }
        .new-order-checklist-item.is-valid .new-order-checklist-icon { color: #16803c; }
        .new-order-checklist-item.is-invalid .new-order-checklist-icon { color: #c13535; }
        .new-order-checklist-item.is-pending .new-order-checklist-icon { color: #8192a3; }
        .new-order-checklist-item.is-current { background: #edf6ff; }
        .new-order-checklist-title { min-width: 0; flex: 1; font-weight: 650; overflow-wrap: anywhere; }
        .new-order-checklist-quantity { color: #536579; white-space: nowrap; }
        .new-order-item-image { width: 52px; height: 52px; flex: 0 0 52px; border: 1px solid #e2ebf3; border-radius: 9px; background: #fff; object-fit: cover; }
        .new-order-item-details { min-width: 0; flex: 1 1 auto; }
        .new-order-item-price { flex: 0 0 auto; color: #274b6c; font-weight: 700; white-space: nowrap; }
        #newRegularOrderModal .modal-footer { padding: 1rem 1.5rem 1.35rem; border-top: 1px solid #edf1f5; }
        #new-order-accept { border: 0; border-radius: 10px; box-shadow: 0 7px 14px rgba(47,179,68,.2); }
        @media (max-width: 480px) { .new-order-header { padding-left: 1rem; padding-right: 1rem; } #newRegularOrderModal .modal-body, #newRegularOrderModal .modal-footer { padding-left: 1rem; padding-right: 1rem; } }
    </style>
    <script src="{{ hyperAsset('assets/js/seller-order-alert.js') }}" defer></script>
    {{-- Mobile App Deep Link Bootstrap Modal for Seller Panel --}}
    @php
        $sellerScheme = $appSettings['sellerAppScheme'] ?? '';
        $sellerPlay = $appSettings['sellerPlaystoreLink'] ?? '';
        $sellerStore = $appSettings['sellerAppstoreLink'] ?? '';
    @endphp
    @if(!empty($sellerScheme))
        <div class="modal modal-blur fade" id="sellerAppModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                    <div class="modal-body text-center py-4">
                        <img src="{{ $systemSettings['favicon'] ?? asset('assets/img/app-icon.png') }}" class="img-fluid mb-4" style="max-width: 80px;">
                        <h3>Open Seller App</h3>
                        <div class="text-secondary">
                            For a better mobile experience, use the Seller mobile app.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100">
                            <div class="row">
                                <div class="col">
                                    <a href="#" class="btn btn-3 w-100" data-bs-dismiss="modal" id="seller-app-cancel">Continue on web</a>
                                </div>
                                <div class="col">
                                    <a href="#" class="btn btn-4 btn-primary w-100" id="seller-app-open">Open app</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            (function(){
                const currentRouteName = @json(Route::currentRouteName());
                const isMobileScreen = () => window.innerWidth <= 768 || (typeof window.matchMedia === 'function' && window.matchMedia('(max-width: 768px)').matches);
                const ua = navigator.userAgent || navigator.vendor || window.opera || '';
                const isAndroid = /Android/i.test(ua);
                const isIOS = /iPhone|iPad|iPod/i.test(ua);
                const modalEl = document.getElementById('sellerAppModal');
                const btnCancel = document.getElementById('seller-app-cancel');
                const btnOpen = document.getElementById('seller-app-open');
                const scheme = @json($sellerScheme);
                const playUrl = @json($sellerPlay);
                const storeUrl = @json($sellerStore);

                let modalInstance = null;
                function ensureModal(){
                    $('#sellerAppModal').modal('show');
                }
                function openModal(){ const inst = ensureModal(); if(inst){ inst.show(); } }
                function closeModal(){ if(modalInstance){ modalInstance.hide(); } }

                function tryDeepLink() {
                    const target = isIOS ? (scheme.includes('://') ? scheme : scheme + '://') : (scheme.includes('://') ? scheme : scheme + '://');
                    const fallback = isAndroid ? (playUrl || storeUrl) : (storeUrl || playUrl);
                    const timeout = isIOS ? 1200 : 1200;

                    let hidden = document.createElement('iframe');
                    hidden.style.display = 'none';
                    hidden.src = target;
                    document.body.appendChild(hidden);

                    const start = Date.now();
                    setTimeout(function(){
                        const elapsed = Date.now() - start;
                        // If app did not open, elapsed will be ~timeout; redirect to store
                        if (document.visibilityState === 'visible' && elapsed >= timeout - 50) {
                            if (fallback) window.location.href = fallback;
                        }
                        // cleanup
                        setTimeout(function(){ try { document.body.removeChild(hidden); } catch(e){} }, 1000);
                    }, timeout);
                }

                if (modalEl) {
                    modalEl.addEventListener('hidden.bs.modal', function(){
                        try { sessionStorage.setItem('sellerAppModalDismissed', '1'); } catch(e) {}
                    });
                }
                if (btnCancel) btnCancel.addEventListener('click', function(){ try { sessionStorage.setItem('sellerAppModalDismissed', '1'); } catch(e) {} });
                if (btnOpen) btnOpen.addEventListener('click', function(){ tryDeepLink(); });

                // Auto-show modal on first load for mobile-sized screens in seller panel
                document.addEventListener('DOMContentLoaded', function(){
                    try {
                        if (
                            isMobileScreen() &&
                            !sessionStorage.getItem('sellerAppModalDismissed') &&
                            currentRouteName !== 'seller.stores.configuration'
                        ) {
                            openModal();
                        }
                    } catch (e) { /* ignore */ }
                });
                window.addEventListener('resize', function(){
                    if (!isMobileScreen() && modalEl && modalEl.classList.contains('show')) { closeModal(); }
                });
            })();
        </script>
    @endif
@endsection
