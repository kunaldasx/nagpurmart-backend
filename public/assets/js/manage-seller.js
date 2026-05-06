$(document).ready(function() {
    // Initialize variables
    let currentCommissionId = null;
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Initialize Tom Select for Store filter (server-side)
    try {
        const storeEl = document.getElementById('storeFilter');
        if (storeEl && window.TomSelect) {
            new TomSelect(storeEl, {
                copyClassesToDropdown: false,
                dropdownParent: 'body',
                controlInput: '<input>',
                valueField: 'value',
                labelField: 'text',
                searchField: 'text',
                placeholder: (typeof labels !== 'undefined' && labels.store) ? labels.store : 'Store',
                load: function (query, callback) {
                    if (!query.length) return callback();
                    const url = (typeof panel !== 'undefined' && panel === 'admin')
                        ? `${base_url}/admin/sellers/store/search?search=${encodeURIComponent(query)}`
                        : `${base_url}/seller/stores/search?search=${encodeURIComponent(query)}`;
                    fetch(url)
                        .then(response => response.json())
                        .then(json => callback(json))
                        .catch(() => callback());
                }
            });

            // Reload tables on change
            storeEl.addEventListener('change', function () {
                if ($('#commissions-table').length) {
                    $('#commissions-table').DataTable().ajax.reload();
                }
                if ($('#debits-table').length) {
                    $('#debits-table').DataTable().ajax.reload();
                }
                if ($('#commissions-history-table').length) {
                    $('#commissions-history-table').DataTable().ajax.reload();
                }
            });
        }
    } catch (e) { console.error(e); }

    // Inject store_id into DataTables requests where applicable
    try {
        $('#commissions-table').on('preXhr.dt', function (e, settings, data) {
            data.store_id = $('#storeFilter').val();
        });
    } catch (e) {}
    try {
        $('#debits-table').on('preXhr.dt', function (e, settings, data) {
            data.store_id = $('#storeFilter').val();
        });
    } catch (e) {}
    try {
        $('#commissions-history-table').on('preXhr.dt', function (e, settings, data) {
            data.store_id = $('#storeFilter').val();
        });
    } catch (e) {}

    // Refresh button
    $('#refresh').on('click', function() {
        // Refresh any present tables on the page
        if ($('#commissions-table').length) {
            $('#commissions-table').DataTable().ajax.reload();
        }
        if ($('#debits-table').length) {
            $('#debits-table').DataTable().ajax.reload();
        }
        if ($('#commissions-history-table').length) {
            $('#commissions-history-table').DataTable().ajax.reload();
        }
    });

    // Settle single commission
    $(document).on('click', '.settle-commission-btn', function(e) {
        e.preventDefault();
        currentCommissionId = $(this).data('id');
        const orderId = $(this).data('order-id');
        const store = $(this).data('store');
        const product = $(this).data('product');
        const commission = $(this).data('commission');
        const amount = $(this).data('amount');

        // Set values in the modal
        $('#settlement-order-id').text(orderId);
        $('#settlement-product').text(product);
        $('#settlement-commission').text(commission);
        $('#settlement-amount').text(amount);

        // Show the modal
        $('#settlementModal').modal('show');
    });

    // Confirm single settlement
    $('#confirmSettlement').on('click', function() {
        if (!currentCommissionId) return;

        // Disable the button and show loading state
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

        // Process the settlement using axios
        const settleUrl = $(this).data('settle-url') || $('.settle-commission-btn[data-id="'+currentCommissionId+'"]').data('settle-url') || `${base_url}/admin/commissions/${currentCommissionId}/settle`;
        axios.post(settleUrl, {
            _token: $('meta[name="csrf-token"]').attr('content'),
        })
        .then(function(response) {
            const data = response.data;
            if (data.success) {
                Toast.fire({
                    icon: 'success',
                    title: data.message
                });
                if ($('#commissions-table').length) {
                    $('#commissions-table').DataTable().ajax.reload();
                }
                if ($('#debits-table').length) {
                    $('#debits-table').DataTable().ajax.reload();
                }
                if ($('#commissions-history-table').length) {
                    $('#commissions-history-table').DataTable().ajax.reload();
                }
            } else {
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            Toast.fire({
                icon: 'error',
                title: error.response?.data?.message || 'An error occurred while processing the settlement'
            });
        })
        .finally(function() {
            // Reset the button state
            $('#confirmSettlement').prop('disabled', false).html('Confirm');

            // Hide the modal
            $('#settlementModal').modal('hide');
        });
    });

    // Settle all commissions (credits)
    let settleAllUrl = `${base_url}/admin/commissions/settle-all`;
    $('#settle-all-btn').on('click', function() {
        settleAllUrl = `${base_url}/admin/commissions/settle-all`;
        $('#settleAllModal').modal('show');
    });
    // Settle all debit settlements
    $('#settle-all-debits-btn').on('click', function() {
        settleAllUrl = `${base_url}/admin/commissions/debits/settle-all`;
        $('#settleAllModal').modal('show');
    });

    // Confirm settle all
    $('#confirmSettleAll').on('click', function() {
        // Disable the button and show loading state
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

        // Process all settlements using axios
        axios.post(settleAllUrl, {}, {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(function(response) {
            const data = response.data;
            if (data.success) {
                Toast.fire({
                    icon: 'success',
                    title: data.message
                });
                if ($('#commissions-table').length) {
                    $('#commissions-table').DataTable().ajax.reload();
                }
                if ($('#debits-table').length) {
                    $('#debits-table').DataTable().ajax.reload();
                }
                if ($('#commissions-history-table').length) {
                    $('#commissions-history-table').DataTable().ajax.reload();
                }
            } else {
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            Toast.fire({
                icon: 'error',
                title: error.response?.data?.message || 'An error occurred while processing the settlements'
            });
        })
        .finally(function() {
            // Reset the button state
            $('#confirmSettleAll').prop('disabled', false).html('Confirm');

            // Hide the modal
            $('#settleAllModal').modal('hide');
        });
    });
});
