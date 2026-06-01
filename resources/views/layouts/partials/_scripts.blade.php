{{--<script src="{{hyperAsset('assets/theme/js/bootstrap.bundle.min.js')}}" defer></script>--}}
<!-- include jQuery library -->
<script src="{{asset('assets/vendor/axios/axios.min.js')}}"></script>
<script src="{{asset('assets/vendor/jquery/jquery.js')}}"></script>

<script src="{{asset('assets/theme/js/tabler.min.js')}}" defer></script>
{{--<script src="{{asset('assets/theme/js/style.min.js')}}" defer></script>--}}


<!-- include FilePond library -->
<script src="{{asset('assets/vendor/filepond/js/filepond.min.js')}}"></script>

<!-- include FilePond plugins -->
<script src="{{asset('assets/vendor/filepond/js/filepond-plugin-image-preview.min.js')}}"></script>
<script src="{{asset('assets/vendor/filepond/js/filepond-plugin-file-validate-type.js')}}"></script>
<script src="{{asset('assets/vendor/filepond/js/filepond-plugin-file-validate-size.js')}}"></script>

<!-- include FilePond jQuery adapter -->
<script src="{{asset('assets/vendor/filepond/js/filepond.jquery.js')}}" defer></script>
<script src="{{hyperAsset('assets/js/filepond.custom.js')}}" defer></script>

{{-- light box --}}
<script src="{{asset('assets/vendor/lightbox/index.js')}}"></script>

{{-- tom select --}}
<script src="{{asset('assets/vendor/tom-select/js/tom-select.base.min.js')}}" defer></script>

{{-- sweet alert --}}
<script src="{{asset('assets/vendor/sweetalert/js/sweetalert2.all.min.js')}}"></script>

{{-- Data Table --}}
<script src="{{asset('assets/vendor/datatable/js/dataTables.js')}}" defer></script>
<script src="{{asset('assets/vendor/datatable/js/dataTables.bootstrap5.js')}}" defer></script>
<script src="{{asset('assets/vendor/datatable/js/dataTables.buttons.js')}}" defer></script>
<script src="{{asset('assets/vendor/datatable/js/buttons.bootstrap5.js')}}" defer></script>
<script src="{{asset('assets/vendor/datatable/js/buttons.colVis.min.js')}}" defer></script>
<script src="{{asset('assets/vendor/datatable/js/buttons.html5.min.js')}}" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>



{{-- hugerte text editor--}}
<script src="{{asset('assets/vendor/hugerte/hugerte.min.js')}}" defer></script>

<script src="{{hyperAsset('assets/js/datatable.custom.js')}}" defer></script>
<script src="{{hyperAsset('assets/js/custom.js')}}" defer></script>
<script type="module" src="{{hyperAsset('assets/js/firebase.js')}}" defer></script>
<script src="{{hyperAsset('assets/admin/js/custom.js')}}" defer></script>
<script src="{{hyperAsset('assets/seller/js/custom.js')}}" defer></script>
@stack('scripts')
<script>
    const base_url = document.getElementById('base_url')?.value;
    const user_id = document.getElementById('user_id')?.value;
    const currencySymbol = document.getElementById('selected-currency-symbol')?.value;
    const Toast = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });

    // Theme switching functions - LIGHT THEME ONLY (forced)
    function getCurrentTheme() {
        // Always return 'light' theme - no dark mode allowed
        return 'light';
    }

    function setTheme(theme) {
        // Force light theme always
        const forcedTheme = 'light';
        localStorage.setItem('tabler-theme', forcedTheme);

        // Always remove dark theme attribute
        document.documentElement.removeAttribute('data-bs-theme');

        // Update icon
        updateThemeIcon(forcedTheme);
    }

    function updateThemeIcon(theme) {
        const themeIcon = document.getElementById('theme-icon');
        if (themeIcon) {
            // Always show moon icon (indicating light theme)
            themeIcon.className = 'ti ti-moon fs-2';
        }
    }

    function toggleTheme() {
        // Theme toggle disabled - light theme only
        // Do nothing
        return false;
    }

    // Initialize theme icon on page load
    document.addEventListener('DOMContentLoaded', function() {
        const currentTheme = getCurrentTheme();
        updateThemeIcon(currentTheme);
    });
</script>
