<!-- REJECT MODAL -->
<div
    class="modal modal-blur fade"
    id="rejectModel"
    tabindex="-1"
    role="dialog"
    aria-hidden="true"
>
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <!-- Download SVG icon from http://tabler.io/icons/icon/alert-triangle -->
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
                    class="icon mb-2 text-danger icon-lg"
                >
                    <path d="M12 9v4"/>
                    <path
                        d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"
                    />
                    <path d="M12 16h.01"/>
                </svg>
                <h3>Reject Order</h3>
                <div class="text-secondary">
                    Are you sure you want to reject this Order Item Return Request? This action cannot be undone.
                </div>
                <div class="form-group mt-3">
                    <label for="seller_comment" class="form-label">Comment (Optional)</label>
                    <textarea class="form-control" id="seller_comment_reject" name="seller_comment" rows="3"
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
                            <a href="#" class="btn btn-danger w-100" id="confirmReject" data-bs-dismiss="modal">Reject
                                Request</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END MODAL -->
