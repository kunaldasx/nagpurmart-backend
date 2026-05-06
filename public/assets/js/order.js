$(document).ready(function () {

    const table = $('#orders-table').DataTable();
    let currentOrderId = null;

    const updateOrderCount = () => {
        if (table.page.info() !== undefined) {
            const totalRecords = table.page.info().recordsTotal;
            $('.order-count').html("(" + totalRecords + (totalRecords > 1 ? ' Order Items' : ' Order Item') + ")");
        }
    };

    // Reload table when filters change
    $('#rangeFilter, #statusFilter, #paymentFilter').on('change', function () {
        table.ajax.reload(updateOrderCount, false);
    });
    $('#refresh').on('click', function () {
        table.ajax.reload(updateOrderCount, false);
    });

    setTimeout(function () {
        // Initial order count
        updateOrderCount();
    }, 1000)

    // Add filter params to AJAX request
    $('#orders-table').on('preXhr.dt', function (e, settings, data) {
        data.range = $('#rangeFilter').val();
        data.status = $('#statusFilter').val();
        data.payment_type = $('#paymentFilter').val();
    });

    // Capture order ID when accept/reject/preparing buttons are clicked
    $('#acceptModel, #rejectModel, #preparingModel').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        currentOrderId = button.data('id');
    });

    // Handle accept order action
    $('#confirmAccept').on('click', function () {
        if (currentOrderId) {
            axios.post('/seller/orders/' + currentOrderId + '/accept')
                .then(function (response) {
                    // Handle success
                    table.ajax.reload(updateOrderCount, false);
                    let data = response.data;
                    if (data.success === false) {
                        return Toast.fire({
                            icon: "error",
                            title: data.message
                        });
                    }
                    return Toast.fire({
                        icon: "success",
                        title: data.message
                    });
                })
                .catch(function (error) {
                    // Handle error
                    console.error('Error accepting order:', error);
                });
        }
    });

    // Handle reject order action
    $('#confirmReject').on('click', function () {
        if (currentOrderId) {
            axios.post('/seller/orders/' + currentOrderId + '/reject')
                .then(function (response) {
                    // Handle success
                    table.ajax.reload(updateOrderCount, false);
                    let data = response.data;
                    if (data.success === false) {
                        return Toast.fire({
                            icon: "error",
                            title: data.message
                        });
                    }
                    return Toast.fire({
                        icon: "success",
                        title: data.message
                    });
                })
                .catch(function (error) {
                    // Handle error
                    console.error('Error rejecting order:', error);
                });
        }
    });

    // Handle preparing order action
    $('#confirmPreparing').on('click', function () {
        if (currentOrderId) {
            axios.post('/seller/orders/' + currentOrderId + '/preparing')
                .then(function (response) {
                    // Handle success
                    table.ajax.reload(updateOrderCount, false);
                    let data = response.data;
                    if (data.success === false) {
                        return Toast.fire({
                            icon: "error",
                            title: data.message
                        });
                    }
                    return Toast.fire({
                        icon: "success",
                        title: data.message
                    });
                })
                .catch(function (error) {
                    // Handle error
                    console.error('Error marking order as preparing:', error);
                });
        }
    });
});

$(document).ready(function () {
    // Handle select all checkbox
    $('#select-all-items').on('change', function () {
        $('.item-checkbox').prop('checked', $(this).prop('checked'));
    });

    // Update select all checkbox when individual checkboxes change
    $('.item-checkbox').on('change', function () {
        if ($('.item-checkbox:checked').length === $('.item-checkbox').length) {
            $('#select-all-items').prop('checked', true);
        } else {
            $('#select-all-items').prop('checked', false);
        }
    });
});
$(document).ready(function () {
    $('#update-status-form').on('submit', function (e) {
        e.preventDefault();

        const selectedItems = $('.item-checkbox:checked');
        if (selectedItems.length === 0) {
            $('#status-update-results').html(
                '<div class="alert alert-danger">' +
                '<h4 class="alert-heading">Error</h4>' +
                '<p>Please select at least one item to update.</p>' +
                '</div>'
            );
            return;
        }

        // Clear previous results
        $('#status-update-results').empty();

        const status = $('#item-status').val();
        let successCount = 0;
        let errorCount = 0;
        let totalRequests = selectedItems.length;
        let completedRequests = 0;

        // Disable the submit button during processing
        $('#update-items-status').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...');

        // Create a progress alert
        $('#status-update-results').append(
            '<div class="alert alert-info" id="progress-alert">' +
            '<h4 class="alert-heading">Processing...</h4>' +
            '<p>Updating status for selected items. Please wait.</p>' +
            '<div class="progress mt-2">' +
            '<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>' +
            '</div>' +
            '</div>'
        );

        selectedItems.each(function () {
            const itemId = $(this).val();
            const itemRow = $(this).closest('tr');
            const productName = itemRow.find('td:eq(1)').text(); // Product name is in the second column
            const variantName = itemRow.find('td:eq(2)').text(); // Variant name is in the third column

            // Process each selected item
            axios.post('/seller/orders/' + itemId + '/' + status)
                .then(function (response) {
                    // Handle success
                    let data = response.data;
                    completedRequests++;

                    // Update progress bar
                    const progressPercentage = (completedRequests / totalRequests) * 100;
                    $('#progress-alert .progress-bar').css('width', progressPercentage + '%').attr('aria-valuenow', progressPercentage);

                    // Add item-specific result
                    const alertClass = data.success ? 'alert-success' : 'alert-danger';
                    const alertIcon = data.success ? 'check-circle' : 'x-circle';
                    const alertTitle = data.success ? 'Success' : 'Error';

                    $('#status-update-results').append(
                        '<div class="alert ' + alertClass + ' mt-2">' +
                        '<div class="d-flex">' +
                        '<div>' +
                        '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-' + alertIcon + ' me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">' +
                        '<path stroke="none" d="M0 0h24v24H0z" fill="none"></path>' +
                        (data.success ?
                            '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M9 12l2 2l4 -4"></path>' :
                            '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M10 10l4 4m0 -4l-4 4"></path>') +
                        '</svg>' +
                        '</div>' +
                        '<div>' +
                        '<h4 class="alert-title">' + alertTitle + ': ' + productName + ' - ' + variantName + '</h4>' +
                        '<p>' + data.message + '</p>' +
                        '</div>' +
                        '</div>' +
                        '</div>'
                    );

                    data.success ? successCount++ : errorCount++;

                    // Check if all requests are completed
                    if (completedRequests === totalRequests) {
                        finishProcessing(successCount, errorCount, totalRequests);
                    }
                })
                .catch(function (error) {
                    // Handle error
                    completedRequests++;
                    errorCount++;

                    // Update progress bar
                    const progressPercentage = (completedRequests / totalRequests) * 100;
                    $('#progress-alert .progress-bar').css('width', progressPercentage + '%').attr('aria-valuenow', progressPercentage);
                    // Add error message
                    const errorMessage = error.response && error.response.data && error.response.data.message
                        ? error.response.data.message
                        : 'An error occurred while updating the item status.';

                    $('#status-update-results').append(
                        '<div class="alert alert-danger mt-2">' +
                        '<div class="d-flex">' +
                        '<div>' +
                        '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x-circle me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">' +
                        '<path stroke="none" d="M0 0h24v24H0z" fill="none"></path>' +
                        '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path>' +
                        '<path d="M10 10l4 4m0 -4l-4 4"></path>' +
                        '</svg>' +
                        '</div>' +
                        '<div>' +
                        '<h4 class="alert-title">Error</h4>' +
                        '<p>' + errorMessage + '</p>' +
                        '</div>' +
                        '</div>' +
                        '</div>'
                    );

                    // Check if all requests are completed
                    if (completedRequests === totalRequests) {
                        finishProcessing(successCount, errorCount, totalRequests);
                    }
                });
        });
    });
});

function finishProcessing(successCount, errorCount, totalRequests) {
    // Re-enable the submit button
    $('#update-items-status').prop('disabled', false).html('Update Status');

    // Remove the progress alert
    $('#progress-alert').remove();

    // Add a summary alert at the top of the results
    let summaryAlertClass, summaryAlertIcon, summaryAlertTitle, summaryMessage;

    if (successCount === totalRequests) {
        summaryAlertClass = 'alert-success';
        summaryAlertIcon = 'check-circle';
        summaryAlertTitle = 'Success';
        summaryMessage = 'All selected items have been updated successfully.';

        // Reload the page after a delay
        setTimeout(function () {
            window.location.reload();
        }, 3000);
    } else if (successCount > 0) {
        summaryAlertClass = 'alert-warning';
        summaryAlertIcon = 'alert-triangle';
        summaryAlertTitle = 'Partial Success';
        summaryMessage = successCount + ' out of ' + totalRequests + ' items updated successfully.';

        // Reload the page after a delay
        setTimeout(function () {
            window.location.reload();
        }, 3000);
    } else {
        summaryAlertClass = 'alert-danger';
        summaryAlertIcon = 'x-circle';
        summaryAlertTitle = 'Error';
        summaryMessage = 'Failed to update any items. Please try again.';
    }

    // Prepend the summary alert to the results container
    $('#status-update-results').prepend(
        '<div class="alert ' + summaryAlertClass + '">' +
        '<div class="d-flex">' +
        '<div>' +
        '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-' + summaryAlertIcon + ' me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">' +
        '<path stroke="none" d="M0 0h24v24H0z" fill="none"></path>' +
        (summaryAlertIcon === 'check-circle' ?
            '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M9 12l2 2l4 -4"></path>' :
            (summaryAlertIcon === 'alert-triangle' ?
                '<path d="M12 9v2m0 4v.01"></path><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"></path>' :
                '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M10 10l4 4m0 -4l-4 4"></path>')) +
        '</svg>' +
        '</div>' +
        '<div>' +
        '<h4 class="alert-title">' + summaryAlertTitle + '</h4>' +
        '<p>' + summaryMessage + '</p>' +
        (successCount > 0 ? '<p class="mb-0"><small>Page will reload in 3 seconds...</small></p>' : '') +
        '</div>' +
        '</div>' +
        '</div>'
    );

    // Scroll to the top of the results
    $('html, body').animate({
        scrollTop: $('#status-update-results').offset().top - 100
    }, 500);
}
