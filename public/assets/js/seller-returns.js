/* global $, hyperDataTable */
$(document).ready(function () {
    const table = $('#returns-table').DataTable();
    let currentOrderId = null;

    // Capture order ID when accept/reject/preparing buttons are clicked
    $('#acceptModel, #rejectModel').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        currentOrderId = button.data('id');
    }).on('hidden.bs.modal', function () {
        $('#seller_comment, #seller_comment_reject').val('');
    });


    // Handle order decision actions
    $('#confirmAccept, #confirmReject').on('click', function () {
        if (currentOrderId) {
            const action = this.id === 'confirmAccept' ? 'approve' : 'reject';
            axios.post('/seller/order-returns/' + currentOrderId + '/decision', {
                action: action,
                seller_comment: action === 'approve' ? $('#seller_comment').val() : $('#seller_comment_reject').val()
            })
                .then(function (response) {
                    // Handle success
                    table.ajax.reload();
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
                    console.error('Error processing order decision:', error);
                });
        }
    });
});
