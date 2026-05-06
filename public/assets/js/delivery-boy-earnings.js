$(document).ready(function () {
    // Initialize DataTable
    let table = $('#delivery-boy-earnings-table').DataTable();
    let historyTable = $('#payment-history-table').DataTable();

    $('#deliveryBoySearch').on('change', function () {
        table.ajax.reload(null, false);
        historyTable.ajax.reload(null, false);
    });
    table.on('preXhr.dt', function (e, settings, data) {
        data.delivery_boy_id = $('#deliveryBoySearch').val();
    });
    historyTable.on('preXhr.dt', function (e, settings, data) {
        data.delivery_boy_id = $('#deliveryBoySearch').val();
    });


    // Process payment button
    $(document).on('click', '.process-payment', function () {
        const id = $(this).data('id');
        const orderId = $(this).data('order-id');
        // const deliveryBoyId = $(this).data('delivery-boy-id');
        const deliveryBoyName = $(this).data('delivery-boy-name');
        const amount = $(this).data('amount');

        // Set values in the modal
        $('#payment-order-id').text(orderId);
        $('#payment-delivery-boy').text(deliveryBoyName);
        $('#payment-amount').text(amount);

        // Store the assignment ID for processing
        $('#confirmPayment').data('id', id);

        // Show the modal
        $('#paymentModal').modal('show');
    });

    // Confirm payment
    $('#confirmPayment').on('click', function () {
        const id = $(this).data('id');

        // Disable the button and show loading state
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

        // Process the payment using axios
        axios.post(`/admin/delivery-boy-earnings/${id}/process-payment`, {
            _token: $('meta[name="csrf-token"]').attr('content')
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
                    title: error.response?.data?.message || 'An error occurred while processing the payment.'
                });
                console.error(error);
            })
            .finally(function () {
                // Reset the button state
                $('#confirmPayment').prop('disabled', false).html('Confirm');

                // Hide the modal
                $('#paymentModal').modal('hide');
            });
    });
});
