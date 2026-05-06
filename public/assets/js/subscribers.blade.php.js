$(document).ready(function () {

    // Prefill from URL params if provided
    try {
        const params = new URLSearchParams(window.location.search);
        const pid = params.get('plan_id');
        // const sid = params.get('seller_id');
        const st = params.get('status');
        if (pid && $('#planFilter').length) {
            $('#planFilter').val(pid);
        }
        if (st && $('#statusFilter').length) {
            $('#statusFilter').val(st);
        }
        // For seller, if option exists (server prefilled), TomSelect will pick it
    } catch (e) { console.error(e); }

    const table = $('#subscription-subscribers-table').DataTable();

    // Hook preXhr to append filters
    table.on('preXhr.dt', function (e, settings, data) {
        data.plan_id = $('#planFilter').val();
        data.seller_id = $('#select-seller').val();
        data.status = $('#statusFilter').val();
    });

    // Reload table when any filter changes, no page refresh
    $('#planFilter, #select-seller, #statusFilter').on('change', function () {
        table.ajax.reload(null, false);
    });
});
