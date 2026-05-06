$(document).ready(function () {

    // Show/hide fields based on banner type
    function toggleFields() {
        const bannerTypeEl = document.getElementById('bannerType');
        if (!bannerTypeEl)
            return;
        const type = bannerTypeEl.value;

        // Hide all fields
        document.getElementById('productField').style.display = 'none';
        document.getElementById('categoryField').style.display = 'none';
        document.getElementById('brandField').style.display = 'none';
        document.getElementById('customField').style.display = 'none';

        // Show the selected field
        if (type === 'product') {
            document.getElementById('productField').style.display = '';
        } else if (type === 'category') {
            document.getElementById('categoryField').style.display = '';
        } else if (type === 'brand') {
            document.getElementById('brandField').style.display = '';
        } else if (type === 'custom') {
            document.getElementById('customField').style.display = '';
        }
    }

    // Initial toggle
    toggleFields();
    document.getElementById('bannerType')?.addEventListener('change', toggleFields);

    const table = $('#banners-table').DataTable();

    // Reload table when filters change
    $('#typeFilter, #positionFilter, #statusFilter,#scopeTypeFilter').on('change', function () {
        table.ajax.reload(null, false);
    });

    // Add filter params to AJAX request
    $('#banners-table').on('preXhr.dt', function (e, settings, data) {
        data.type = $('#typeFilter').val();
        data.position = $('#positionFilter').val();
        data.visibility_status = $('#statusFilter').val();
        data.scope_type = $('#scopeTypeFilter').val();
    });

    document.addEventListener('click', (e) => {
            handleDelete(e, '.delete-banner', `/${panel}/banners/`, 'You are about to delete this Banner.');
        }
    );
});
