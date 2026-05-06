$(document).ready(function () {
    const table = $('#product-approvals-table').DataTable();

    $('#refresh').on('click', function () {
        table.ajax.reload(null, false);
    });

    // Approve button click
    $(document).on('click', '.approve-product', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'Approve this product?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Approve'
        }).then((result) => {
            if (result.isConfirmed) {
                axios.post(`${base_url}/admin/product-approvals/${id}/approve`)
                    .then(function (response) {
                        table.ajax.reload(null, false);
                        const data = response.data;
                        if (data.success === false) {
                            return Toast.fire({icon: 'error', title: data.message});
                        }
                        return Toast.fire({icon: 'success', title: data.message});
                    })
                    .catch(function (error) {
                        console.error('Error approving product:', error);
                        return Toast.fire({icon: 'error', title: ('Something went wrong!')});
                    });
            }
        });
    });

    // Reject button click
    $(document).on('click', '.reject-product', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Reject Product',
            input: 'textarea',
            inputLabel: 'Rejection Reason',
            inputPlaceholder: 'Enter rejection reason',
            inputAttributes: {'aria-label': 'Rejection Reason'},
            inputValidator: (value) => {
                if (!value) {
                    return 'Rejection reason is required';
                }
            },
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Reject',
        }).then((result) => {
            if (result.isConfirmed) {
                axios.post(`${base_url}/admin/product-approvals/${id}/reject`, {
                    reason: result.value
                })
                    .then(function (response) {
                        table.ajax.reload(null, false);
                        const data = response.data;
                        if (data.success === false) {
                            return Toast.fire({icon: 'error', title: data.message});
                        }
                        return Toast.fire({icon: 'success', title: data.message});
                    })
                    .catch(function (error) {
                        console.error('Error rejecting product:', error);
                        let msg = (error.response && error.response.data && error.response.data.message) ? error.response.data.message : ('Something went wrong!');
                        return Toast.fire({icon: 'error', title: msg});
                    });
            }
        });
    });
});
