$(document).ready(function () {

    let table = $('#wallet-transactions-table').DataTable();

// Filters
    $('#typeFilter').on('change', function () {
        table.ajax.reload(null, false);
    });

// Custom filter parameters
    table.on('preXhr.dt', function (e, settings, data) {
        data.transaction_type = $('#typeFilter').val();
    });
});
