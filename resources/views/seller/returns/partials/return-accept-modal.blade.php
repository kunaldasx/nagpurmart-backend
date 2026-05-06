<!-- ACCEPT MODAL -->
<div
    class="modal modal-blur fade"
    id="acceptModel"
    tabindex="-1"
    role="dialog"
    aria-hidden="true"
>
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-success"></div>
            <div class="modal-body text-center py-4">
                <!-- Download SVG icon from http://tabler.io/icons/icon/circle-check -->
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="icon mb-2 text-green icon-lg"
                >
                    <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>
                    <path d="M9 12l2 2l4 -4"/>
                </svg>
                <h3>Accept Order</h3>
                <div class="text-secondary">
                    Are you sure you want to accept this Order Item Return Request? You will be responsible for fulfilling it.
                </div>
                <div class="form-group mt-3">
                    <label for="seller_comment" class="form-label">Comment (Optional)</label>
                    <textarea class="form-control" id="seller_comment" name="seller_comment" rows="3"
                              placeholder="Add your comment here"></textarea>
                    <div class="form-text">Your comment will be visible to the customer</div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <a href="#" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancel</a>
                        </div>
                        <div class="col">
                            <a href="#" class="btn btn-success w-100" id="confirmAccept" data-bs-dismiss="modal">Accept Request</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END MODAL -->
