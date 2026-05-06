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
