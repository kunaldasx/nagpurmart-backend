let table = null;
$(document).ready(function () {
    // Initialize DataTable
    table = $('#delivery-boy-withdrawals-table').DataTable();
    let historyTable = $('#withdrawal-history-table').DataTable();

    // Hook delivery boy search filter (follow existing code structure)
    $('#deliveryBoySearch').on('change', function () {
        if (table) table.ajax.reload(null, false);
        if (historyTable) historyTable.ajax.reload(null, false);
    });

    // Append filter to ajax requests
    if (table) {
        table.on('preXhr.dt', function (e, settings, data) {
            data.delivery_boy_id = $('#deliveryBoySearch').val();
        });
    }
    if (historyTable) {
        historyTable.on('preXhr.dt', function (e, settings, data) {
            data.delivery_boy_id = $('#deliveryBoySearch').val();
        });
    }
});
$(document).ready(function () {
    // Process withdrawal request button
    $(document).on('click', '.process-withdrawal-request', function () {
        const id = $(this).data('id');
        const deliveryBoyName = $(this).data('delivery-boy-name');
        const amount = $(this).data('amount');

        // Set values in the modal
        $('#withdrawal-delivery-boy').text(deliveryBoyName);
        $('#withdrawal-amount').text(amount);

        // Reset the form fields
        $('#withdrawal-status').val('approved');
        $('#withdrawal-remark').val('');

        // Store the request ID for processing
        $('#confirmWithdrawal').data('id', id);

        // Show the modal
        $('#withdrawalRequestModal').modal('show');
    });
});
$(document).ready(function () {

    // Confirm withdrawal request processing
    $('#confirmWithdrawal').on('click', function () {
        const id = $(this).data('id');
        const status = $('#withdrawal-status').val();
        const remark = $('#withdrawal-remark').val();

        // Disable the button and show loading state
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

        // Process the withdrawal request using axios
        axios.post(`/admin/delivery-boy-withdrawals/${id}/process`, {
            _token: $('meta[name="csrf-token"]').attr('content'),
            status: status,
            remark: remark
        })
            .then(function (response) {
                const data = response.data;
                if (data.success) {
                    // Show success notification using Toast
                    Toast.fire({
                        icon: 'success',
                        title: data.message
                    });

                    // Reload the table
                    if (table) {
                        table.ajax.reload();
                    }

                    // If we're on the show page, reload the page to show updated status
                    if (window.location.href.includes(`/delivery-boy-withdrawals/${id}`)) {
                        window.location.reload();
                    }
                } else {
                    // Show error notification using Toast
                    Toast.fire({
                        icon: 'error',
                        title: data.message
                    });
                }
            })
            .catch(function (error) {
                // Show error notification using Toast
                Toast.fire({
                    icon: 'error',
                    title: error.response?.data?.message || 'An error occurred while processing the withdrawal request.'
                });
                console.error(error);
            })
            .finally(function () {
                // Reset the button state
                $('#confirmWithdrawal').prop('disabled', false).html('Confirm');

                // Hide the modal
                $('#withdrawalRequestModal').modal('hide');
            });
    });
});
