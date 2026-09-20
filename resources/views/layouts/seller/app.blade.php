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
                <div class="modal-header bg-primary text-white">
                    <h3 class="modal-title">New regular order</h3>
                    <div id="new-order-timer-ring" aria-label="Order waiting time"><span><small>WAITING</small><strong id="new-order-timer">00:00</strong></span></div>
                </div>
                <div class="modal-body">
                    <div id="new-order-summary"></div>
                    <div id="new-order-items" class="mt-3"></div>
                    <div class="alert alert-danger d-none mt-3 mb-0" id="new-order-error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-lg w-100" id="new-order-accept">Accept and prepare order</button>
                </div>
            </div>
        </div>
    </div>
    <style>
        #new-order-timer-ring { --timer-progress: 0deg; width: 104px; height: 104px; border-radius: 50%; display: grid; place-items: center; background: conic-gradient(#fff var(--timer-progress), rgba(255,255,255,.3) 0deg); position: relative; }
        #new-order-timer-ring::before { content: ""; position: absolute; inset: 7px; border-radius: 50%; background: var(--tblr-primary); }
        #new-order-timer-ring > span { position: relative; z-index: 1; color: #fff; text-align: center; line-height: 1.1; }
        #new-order-timer-ring small { display: block; font-size: .62rem; letter-spacing: .08em; }
        #new-order-timer { display: block; color: #fff; font-size: 1.35rem; font-weight: 700; }
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
