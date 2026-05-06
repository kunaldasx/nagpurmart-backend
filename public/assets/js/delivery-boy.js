$(document).ready(function () {

    let table = $('#delivery-boys-table').DataTable();
    let currentDeliveryBoyId = null;

// Filters
    $('#verificationStatusFilter, #statusFilter, #deliveryBoySearch').on('change', function () {
        table.ajax.reload(null, false);
    });

// Custom filter parameters
    table.on('preXhr.dt', function (e, settings, data) {
        data.verification_status = $('#verificationStatusFilter').val();
        data.status = $('#statusFilter').val();
        data.delivery_boy_id = $('#deliveryBoySearch').val();
    });

// Capture order ID when accept/reject/preparing buttons are clicked
    $('#deleteModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        currentDeliveryBoyId = button.data('id');
    });

    $('#confirmDelete').on('click', function () {
        if (currentDeliveryBoyId) {
            axios.delete(`/admin/delivery-boys/${currentDeliveryBoyId}`)
                .then(function (response) {
                    // Handle success
                    let data = response.data;
                    if (data.success === false) {
                        return Toast.fire({
                            icon: "error",
                            title: data.message
                        });
                    }
                    setTimeout(() => {
                        window.location.href = '/admin/delivery-boys';
                    }, 1000)
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
    document.querySelectorAll('.rating-stars').forEach(function (element) {
        const rating = new StarRating(element, {
            tooltip: false,
            clearable: false,
            readOnly: true,
            initialRating: element.dataset.rating,
            stars: function (el, item, index) {
                el.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon gl-star-full icon-1"><path d="M8.243 7.34l-6.38 .925l-.113 .023a1 1 0 0 0 -.44 1.684l4.622 4.499l-1.09 6.355l-.013 .11a1 1 0 0 0 1.464 .944l5.706 -3l5.693 3l.1 .046a1 1 0 0 0 1.352 -1.1l-1.091 -6.355l4.624 -4.5l.078 -.085a1 1 0 0 0 -.633 -1.62l-6.38 -.926l-2.852 -5.78a1 1 0 0 0 -1.794 0l-2.853 5.78z" /></svg>`;
            },
            classNames: {},
        });
    });
})
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (event) {
        handleDelete(event, '.delete-delivery-boy', `/${panel}/delivery-boys/`, 'You are about to delete this Delivery Boy.');
    });
});
