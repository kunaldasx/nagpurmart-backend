$(document).ready(function() {
    // Initialize DataTable
    let table = $('#delivery-boy-cash-collections-table').DataTable();
    let historyTable = $('#cash-submission-history-table').DataTable();

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

    // Refresh button
    $('#refresh').on('click', function() {
        if (table) {
            table.ajax.reload();
        }
        if (historyTable) {
            historyTable.ajax.reload();
        }
    });

    // Process cash submission button
    $(document).on('click', '.process-cash-submission', function() {
        const id = $(this).data('id');
        const orderId = $(this).data('order-id');
        const deliveryBoyId = $(this).data('delivery-boy-id');
        const deliveryBoyName = $(this).data('delivery-boy-name');
        const cashCollected = $(this).data('cash-collected');
        const cashSubmitted = $(this).data('cash-submitted');
        const remainingAmount = $(this).data('remaining-amount');

        // Set values in the modal
        $('#submission-order-id').text(orderId);
        $('#submission-delivery-boy').text(deliveryBoyName);
        $('#submission-cash-collected').text(cashCollected);
        $('#submission-cash-submitted').text(cashSubmitted);
        $('#submission-remaining-amount').text(remainingAmount);

        // Reset the amount input
        $('#submission-amount').val('');
        $('#submission-amount-error').text('').hide();

        // Store the assignment ID for processing
        $('#confirmSubmission').data('id', id);
        $('#confirmSubmission').data('remaining-amount', remainingAmount);

        // Show the modal
        $('#cashSubmissionModal').modal('show');
    });

    // Validate submission amount
    $('#submission-amount').on('input', function() {
        const amount = parseFloat($(this).val());
        const remainingAmount = parseFloat($('#confirmSubmission').data('remaining-amount'));

        if (isNaN(amount) || amount <= 0) {
            $('#submission-amount-error').text('Please enter a valid amount').show();
            $('#confirmSubmission').prop('disabled', true);
        } else if (amount > remainingAmount) {
            $('#submission-amount-error').text('Amount cannot exceed the remaining amount').show();
            $('#confirmSubmission').prop('disabled', true);
        } else {
            $('#submission-amount-error').text('').hide();
            $('#confirmSubmission').prop('disabled', false);
        }
    });

    // Confirm cash submission
    $('#confirmSubmission').on('click', function() {
        const id = $(this).data('id');
        const amount = parseFloat($('#submission-amount').val());

        // Validate amount
        if (isNaN(amount) || amount <= 0) {
            $('#submission-amount-error').text('Please enter a valid amount').show();
            return;
        }

        // Disable the button and show loading state
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

        // Process the cash submission using axios
        axios.post(`/admin/delivery-boy-cash-collections/${id}/process-submission`, {
            _token: $('meta[name="csrf-token"]').attr('content'),
            amount: amount
        })
        .then(function(response) {
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
            } else {
                // Show error notification using Toast
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
            }
        })
        .catch(function(error) {
            // Show error notification using Toast
            Toast.fire({
                icon: 'error',
                title: error.response?.data?.message || 'An error occurred while processing the cash submission.'
            });
            console.error(error);
        })
        .finally(function() {
            // Reset the button state
            $('#confirmSubmission').prop('disabled', false).html('Confirm');

            // Hide the modal
            $('#cashSubmissionModal').modal('hide');
        });
    });
});
