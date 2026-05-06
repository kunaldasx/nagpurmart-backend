<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'maximum_items_allowed_in_cart_reached' => 'Maximum items allowed in cart reached.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'verify_store_info' => 'To verify a store, simply click the Eye icon from the Store table.',
    'quantity_step_size_gte_minimum_order_quantity' => 'The quantity step size must be greater than or equal to the minimum order quantity.',
    'quantity_step_size_lte_total_allowed_quantity' => 'The quantity step size must be less than or equal to the total allowed quantity.',
    'minimum_order_quantity_lte_total_allowed_quantity' => 'The minimum order quantity must be less than or equal to the total allowed quantity.',
    'google_api_key_not_found' => 'Google API Key Not Found, Please Add It From Settings > Authentication > Google API Key',
    'created_successfully' => 'Delivery zone created successfully.',
    'creation_error' => 'An error occurred while creating the delivery zone.',
    'invalid_boundary_json' => 'Invalid boundary JSON format.',
    'internal_server_error' => 'Internal server error',
    'delivery_zone_created_successfully' => 'Delivery zone created successfully.',
    'delivery_zone_retrieved_successfully' => 'Delivery zone retrieved successfully.',
    'delivery_zone_not_found' => 'Delivery zone not found.',
    'delivery_zone_found' => 'Delivery zone found successfully.',
    'delivery_zones_found' => 'Delivery zones retrieved successfully.',
    'delivery_zone_updated_successfully' => 'Delivery zone updated successfully.',
    'delivery_zone_deleted_successfully' => 'Delivery zone deleted successfully.',
    'delivery_zone_creation_error' => 'Failed to create delivery zone.',
    'delivery_zone_update_error' => 'Failed to update delivery zone.',
    'delivery_zone_deletion_error' => 'Failed to delete delivery zone.',
    'error_creating_delivery_zone' => 'Error creating delivery zone',
    'error_updating_delivery_zone' => 'Error updating delivery zone',
    'error_deleting_delivery_zone' => 'Error deleting delivery zone',

    'latitude_required' => 'The latitude field is required.',
    'latitude_numeric' => 'The latitude must be a number.',
    'latitude_between' => 'The latitude must be between -90 and 90.',
    'longitude_required' => 'The longitude field is required.',
    'longitude_numeric' => 'The longitude must be a number.',
    'longitude_between' => 'The longitude must be between -180 and 180.',
    'delivery_zone_overlap_error' => 'This delivery zone overlaps with existing zones.',
    'delivery_zone_creation_failed' => 'Failed to create delivery zone',
    'delivery_zone_update_failed' => 'Failed to update delivery zone',
    'delivery_time_per_km_info_message' => 'Average time (in minutes) needed to deliver per kilometer in this zone. Used to estimate total delivery time based on distance.',
    'buffer_time_info_message' => 'Extra time (in minutes) added to account for delays like traffic or weather.',
    'rush_delivery_enabled_info_message' => 'Enable or disable rush delivery option for this zone. Rush delivery provides faster delivery at a premium price.',
    'rush_delivery_time_per_km_info_message' => 'Average time (in minutes) needed to deliver per kilometer for rush deliveries. This is typically faster than regular delivery time.',
    'rush_delivery_charges_info_message' => 'Additional charges applied for rush delivery service. This is a premium fee for faster delivery.',
    'regular_delivery_charges_info_message' => 'Standard delivery charges applied for normal delivery service in this zone.',
    'free_delivery_amount_info_message' => 'Minimum order amount required for free delivery. Orders above this amount will have delivery charges waived.',
    'distance_based_delivery_charges_info_message' => 'Additional charges applied per kilometer for delivery. This is added to the regular delivery charges based on distance.',
    'per_store_drop_off_fee_info_message' => 'Additional fee charged for each store in a multi-store pickup order. This compensates delivery personnel for multiple stops.',
    'handling_charges_info_message' => 'Administrative fee that goes to the admin for processing the order. This is separate from delivery charges and is applied to all orders.',
    // Cart Success Messages
    'item_added_to_cart_successfully' => 'Item added to cart successfully',
    'item_removed_from_cart_successfully' => 'Item removed from cart successfully',
    'cart_item_quantity_updated_successfully' => 'Cart item quantity updated successfully',
    'cart_cleared_successfully' => 'Cart cleared successfully',
    'cart_retrieved_successfully' => 'Cart retrieved successfully',
    'cart_updated_based_on_location' => 'Cart updated based on your location. Some items were removed as they are not available for delivery to your area',
    'cart_location_verified' => 'Cart location verified. All items are available for delivery to your location',

    // Cart Error Messages
    'cart_is_empty' => 'Your cart is empty',
    'cart_item_not_found' => 'Cart item not found',
    'product_variant_not_available_in_store' => 'Product variant is not available in the selected store',
    'insufficient_stock_available' => 'Insufficient stock available',
    'store_offline_cannot_add_to_cart' => 'This store is currently offline. You cannot add items from this store to your cart right now.',
    'store_offline_cannot_place_order' => 'One or more stores in your cart are currently offline (:stores). Please remove those items to proceed with your order.',

    // Cart quantity validations
    'quantity_must_be_multiple_of_step_size' => 'The quantity must be a multiple of :step.',
    'quantity_must_be_at_least_minimum_order_quantity' => 'The quantity must be at least the minimum order quantity of :min.',
    'quantity_must_not_exceed_total_allowed_quantity' => 'The quantity must not exceed the total allowed quantity of :max.',

    // General Messages
    'something_went_wrong' => 'Something went wrong. Please try again',
    'invalid_coordinates' => 'Invalid coordinates! Please Select A valid Address',
    'required' => 'This field is required.',
    'integer' => 'This field must be an integer.',
    'string' => 'This field must be a string.',
    'max' => 'This field exceeds the maximum allowed length.',
    'min' => 'This field is below the minimum allowed value.',

    // Product validation
    'product_id_required' => 'The product field is required.',
    'product_not_found' => 'The selected product does not exist.',

    // Rating validation
    'rating_required' => 'Rating is required.',
    'rating_must_be_integer' => 'Rating must be an integer value.',
    'rating_must_be_at_least_1' => 'Rating must be at least 1.',
    'rating_must_not_exceed_5' => 'Rating must not exceed 5.',

    // Title validation
    'title_required' => 'A title is required.',
    'title_max_length' => 'The title may not be greater than 255 characters.',

    // Comment validation
    'comment_max_length' => 'The comment may not be greater than 1000 characters.',

    // Description validation
    'description_required' => 'A description is required.',
    'description_max_length' => 'The description may not be greater than 1000 characters.',

    // Seller validation
    'seller_id_required' => 'The seller field is required.',
    'seller_not_found' => 'The selected seller does not exist.',

    // Order Messages
    'invalid_payment_type' => 'Invalid payment type',
    'order_created_successfully' => 'Order created successfully',
    'order_not_found' => 'Order not found',
    'order_retrieved_successfully' => 'Order retrieved successfully',
    'orders_retrieved_successfully' => 'Orders retrieved successfully',

    // Store status toggle
    'store_status_updated_successfully' => 'Store status updated successfully.',
    'store_status_update_failed' => 'Failed to update store status.',
    'store_not_found' => 'Store not found.',

    // Seller Order Messages
    'order_status_updated_successfully' => 'Order status updated successfully',
    'unauthorized_action' => 'You are not authorized to perform this action',
    'order_status_update_failed' => 'Failed to update order status',
    'status_already_set' => 'The order item already has this status',

    // Language Names
    'languages' => [
        'english' => 'English',
        'spanish' => 'Spanish',
        'french' => 'French',
        'german' => 'German',
        'chinese' => 'Chinese',
    ],

    // Dashboard Messages
    'cannot_delete_delivery_zone_has_delivery_boys' => 'Cannot delete delivery zone as it has associated delivery',
    'require_otp_before_delivery' => "If you enable this option, OTP verification will be required before delivering the product to the customer.",
    'discount_amount_percent_or_amount' => 'If Discount Type is Percentage, then Discount Amount should be between 0 and 100. If Discount Type is Flet, then Discount Amount should be greater than 0.',
    'max_discount_amount_must_be_greater_than_discount_amount' => 'Max Discount Amount must be greater than Discount Amount.',
    'discount_amount_exceeds_min_order_total' => 'Discount Amount cannot exceed the Minimum Order Total.',

    // Promo Validation Messages
    'promo_code_required' => 'The promo code is required.',
    'promo_code_unique' => 'This promo code already exists.',
    'start_date_required' => 'The start date is required.',
    'start_date_after_or_equal' => 'The start date must be today or later.',
    'end_date_required' => 'The end date is required.',
    'end_date_after' => 'The end date must be after the start date.',
    'discount_type_required' => 'The discount type is required.',
    'discount_type_in' => 'The discount type must be either percentage or fixed.',
    'discount_amount_required' => 'The discount amount is required.',
    'discount_amount_min' => 'The discount amount must be at least 0.',
    'percentage_discount_max' => 'Percentage discount cannot be more than 100%.',
    'max_discount_value_required_for_percentage' => 'Maximum discount value is required for percentage discounts.',

    // Promo Code Application Messages
    'invalid_promo_code' => 'Invalid promo code.',
    'promo_code_expired' => 'This promo code has expired.',
    'promo_code_not_yet_active' => 'This promo code is not yet active.',
    'minimum_order_amount_not_met' => 'Minimum order amount of :amount is required to use this promo code.',
    'promo_code_usage_limit_exceeded' => 'This promo code has reached its usage limit.',
    'promo_code_user_limit_exceeded' => 'You have reached the maximum usage limit for this promo code.',
    'promo_code_applied_successfully' => 'Promo code applied successfully.',
    'promo_code_validation_error' => 'An error occurred while validating the promo code.',
    'order_amount_required' => 'Order amount is required for promo code validation.',
    'promos_retrieved_successfully' => 'Available promos retrieved successfully.',
    'cart_amount_required' => 'Cart amount is required for promo code validation.',
    'delivery_charge_required' => 'Delivery charge is required for promo code validation.',
    // Business Document Upload Notes
    'business_license_note' => 'Upload a clear copy of your business license. Accepted formats: JPEG, PNG, PDF. Max size: 2MB.',
    'articles_of_incorporation_note' => 'Provide your company\'s articles of incorporation or certificate of incorporation. File must be clear and readable.',
    'national_identity_card_note' => 'Upload a government-issued photo ID (passport, driver\'s license, or national ID card). Both front and back sides if applicable.',
    'authorized_signature_note' => 'Upload a document with authorized signature samples or signature authorization letter from your company.',

    // Order Item Cancellation Messages
    'order_item_not_found' => 'Order item not found.',
    'product_not_cancelable' => 'This product cannot be cancelled.',
    'order_item_cannot_be_cancelled_at_current_status' => 'Order item cannot be cancelled at its current status.',
    'order_item_already_in_terminal_state' => 'Order item is already in a terminal state and cannot be cancelled.',
    'order_item_cancelled_successfully' => 'Order item cancelled successfully.',
    'refund_processing_failed' => 'Failed to process refund.',
    'refund_processed_successfully' => 'Refund processed successfully.',

    'product_not_returnable' => 'This product is not eligible for return.',
    'order_item_cannot_be_returned_at_current_status' => 'This order item cannot be returned in its current status.',
    'return_already_requested' => 'A return request for this item already exists.',
    'return_request_created' => 'Your return request has been submitted successfully.',
    'return_request_sent' => 'Your return request has been sent successfully.',

    'return_request_not_found' => 'Return request not found.',
    'return_cannot_be_cancelled_now' => 'This return request can no longer be cancelled.',
    'return_request_cancelled' => 'Your return request has been successfully cancelled.',
    'return_approved_successfully' => 'Return approved successfully',
    'return_rejected_successfully' => 'Return rejected successfully',
    'return_not_found' => 'Return not found.',
    'order_item_id_required' => 'Order item ID is required.',
    'cashback_info_message' => 'Cashback will be credited once the order is delivered and the return period is completed.',
    'category_cannot_be_deactivated_with_products' => "Category can't be deactivated because it contains products.",
    'service_account_file_description' => 'The service file is stored in a private directory, so it cannot be accessed directly via URL. If you try to open it in the browser, it will show a 403 Forbidden error. This is expected behavior and there is no issue with the setup.',
    'select_zone_message' => 'Your store location must be within our available delivery areas. Please select a location inside the blue highlighted zones on the map.',
    'cron_detected' => 'Detected. The log file exists at :path.',
    'cron_log_last_updated' => 'The log file exists at :path.',

    'cron_not_detected_full' => 'Notifications are currently not functioning because the required cron job has not been configured on the server.',
    'subscription_cron_not_detected_full' => 'Subscription expiry processing is currently not functioning because the required scheduler command has not been configured on the server.',

    'add_cron_instruction' => 'Add the following cron entry on your server to process queued notifications. Output is redirected to a log file so you can verify whether the cron is running.',

    'php_path_note' => 'Note: If your PHP binary path differs from /usr/bin/php, adjust it accordingly.',

    'view_documentation' => 'For more details on configuring notifications, please refer to our documentation.',
    'log_file_not_found'        => 'The log file was not found at',
    'cron_has_not_run_yet'      => 'This likely means the cron job has not been added or has not executed yet.',
    'custom_app_schema_regex_message' => 'Spaces are not allowed in the app schema.',
    'add_subscription_expire_instruction' => 'Add the following cron to your server to process subscription expire. Output is redirected to a log file so you can verify whether the cron is running.',
    'subscription_expiry_scheduler_instruction' => 'Add the following scheduler command on your server. The scheduler will run every hour to update and expire expired subscriptions automatically. Output is redirected to a log file so you can verify that the scheduler is running.',
];
