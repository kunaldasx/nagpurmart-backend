<?php

namespace App\Services;

use App\Enums\DateRangeFilterEnum;
use App\Enums\DeliveryBoy\DeliveryBoyAssignmentStatusEnum;
use App\Enums\Order\OrderItemReturnPickupStatusEnum;
use App\Enums\Order\OrderItemReturnStatusEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PromoModeEnum;
use App\Enums\Seller\SellerSettlementTypeEnum;
use App\Enums\SettingTypeEnum;
use App\Events\Order\OrderDelivered;
use App\Events\Order\OrderPlaced;
use App\Events\Order\OrderStatusUpdated;
use App\Http\Resources\User\OrderItemReturnResource;
use App\Http\Resources\User\OrderResource;
use App\Http\Resources\User\ReviewResource;
use App\Models\Address;
use App\Models\Cart;
use App\Models\DeliveryBoyAssignment;
use App\Models\DeliveryBoyCashTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemReturn;
use App\Models\OrderPromoLine;
use App\Models\Promo;
use App\Models\Review;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Models\StoreProductVariant;
use App\Models\User;
use App\Types\Api\ApiResponseType;
use App\Enums\SpatieMediaCollectionName;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\SellerStatement;

class OrderService
{
    protected StockService $stockService;
    protected DeliveryBoyService $deliveryBoyService;
    protected SettingService $settingService;
    protected PaymentService $paymentService;
    protected SellerStatementService $sellerStatementService;
    protected WalletService $walletService;

    public function __construct(
        StockService           $stockService,
        DeliveryBoyService     $deliveryBoyService,
        SettingService         $settingService,
        PaymentService         $paymentService,
        SellerStatementService $sellerStatementService,
        WalletService          $walletService
    )
    {
        $this->settingService = $settingService;
        $this->stockService = $stockService;
        $this->deliveryBoyService = $deliveryBoyService;
        $this->paymentService = $paymentService;
        $this->sellerStatementService = $sellerStatementService;
        $this->walletService = $walletService;
    }

    /**
     * Create a new order from the cart
     *
     * @param User $user The user placing the order
     * @param array $data Order data including payment and address information
     * @return array Result containing success status, message, and order data
     */
    public function createOrder(User $user, array $data): array
    {
        try {
            DB::beginTransaction();
            // Step 1: Validate cart and system settings
            $cartValidation = $this->validateCartAndSettings($user);
            if (!$cartValidation['success']) {
                return $cartValidation;
            }
            $cart = $cartValidation['cart'];

            // Step 2: Validate payment method
            $paymentValidation = $this->validatePaymentMethod($data);
            if (!$paymentValidation['success']) {
                return $paymentValidation;
            }

            // Step 3: Validate address and delivery zone
            $addressValidation = $this->validateAddressAndDeliveryZone($user, $data);
            if (!$addressValidation['success']) {
                return $addressValidation;
            }
            $data = $addressValidation['data'];

            // Step 4: Validate stock and delivery availability
            $stockAndDeliveryValidation = $this->validateStockAndDeliveryAvailability($cart, $data['address']);
            if (!$stockAndDeliveryValidation['success']) {
                return $stockAndDeliveryValidation;
            }

            // Step 5: Create the order
            $order = $this->processOrderCreation($user, $cart, $data);

            // Step 6: Create order items and finalize
            $finalizationResult = $this->finalizeOrderCreation($order, $cart, $user, $data);
            if (!$finalizationResult['success']) {
                return $finalizationResult;
            }

            DB::commit();

            return [
                'success' => true,
                'message' => __('messages.order_created_successfully'),
                'data' => $finalizationResult['order']
            ];

        } catch (ModelNotFoundException $e) {
            $this->handleOrderCreationFailure($data);
            DB::rollBack();
            return [
                'success' => false,
                'message' => __('labels.model_not_found'),
                'data' => ['error' => $e->getMessage()]
            ];
        } catch (Exception $e) {
            $this->handleOrderCreationFailure($data);
            DB::rollBack();
            Log::error('Error creating order: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Validate cart and system settings
     *
     * @param User $user
     * @return array
     */
    private function validateCartAndSettings(User $user): array
    {
        $settingService = app(SettingService::class);
        $settings = $settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());

        // Get user's cart
        $cart = CartService::getUserCart($user);
        $storeCount = CartService::cartStoreCount($user);

        if ($settings->value['checkoutType'] === "single_store" && $storeCount > 1) {
            return [
                'success' => false,
                'message' => __('labels.checkout_type_single_store_error'),
                'data' => []
            ];
        }

        if (!$cart || $cart->items->isEmpty()) {
            return [
                'success' => false,
                'message' => __('messages.cart_is_empty'),
                'data' => []
            ];
        }

        $itemMaxAllowValidate = CartService::validateCartMaxItems($cart);
        if (!$itemMaxAllowValidate) {
            return [
                'success' => false,
                'message' => __('labels.cart_max_items_exceeded'),
                'data' => []
            ];
        }

        // Ensure all stores in the cart are online before proceeding
        $offlineStores = $cart->items->filter(function ($item) {
            return $item->store && method_exists($item->store, 'isOffline') && $item->store->isOffline();
        })->map(function ($item) {
            return $item->store?->name;
        })->unique()->values()->all();

        if (!empty($offlineStores)) {
            return [
                'success' => false,
                'message' => __('messages.store_offline_cannot_place_order', ['stores' => implode(', ', $offlineStores)]),
                'data' => ['stores' => $offlineStores]
            ];
        }

        return [
            'success' => true,
            'cart' => $cart
        ];
    }

    /**
     * Validate payment method
     *
     * @param array $data
     * @return array
     */
    private function validatePaymentMethod(array $data): array
    {
        $transformedSetting = $this->settingService->getSettingByVariable(SettingTypeEnum::PAYMENT());
        $paymentSettings = $transformedSetting->toArray(request())['value'] ?? [];
        $paymentMethodEnabled = $paymentSettings[$data['payment_type']] ?? false;

        if ($paymentSettings['wallet'] === false && $data['use_wallet'] === '1') {
            return [
                'success' => false,
                'message' => __('labels.wallet_not_enabled'),
                'data' => []
            ];
        }

        if ($paymentMethodEnabled === false) {
            return [
                'success' => false,
                'message' => __('labels.payment_method_not_enabled', ['payment_method' => ucfirst($data['payment_type'])]),
                'data' => []
            ];
        }

        $paymentStatus = $this->paymentService->verifyOnlinePayment($data);
        if ($paymentStatus['success'] === false) {
            return [
                'success' => false,
                'message' => $paymentStatus['message'],
                'data' => []
            ];
        }

        return ['success' => true];
    }

    /**
     * Validate address and delivery zone
     *
     * @param User $user
     * @param array $data
     * @return array
     */
    private function validateAddressAndDeliveryZone(User $user, array $data): array
    {
        $address = Address::where(['id' => $data['address_id'], 'user_id' => $user->id])->get()->first();
        if (!$address) {
            return [
                'success' => false,
                'message' => __('labels.address_not_found'),
                'data' => []
            ];
        }
        $data['address'] = $address;

        $zone = DeliveryZoneService::getZonesAtPoint($data['address']['latitude'], $data['address']['longitude']);
        if ($zone['exists'] === false) {
            return [
                'success' => false,
                'message' => __('messages.delivery_zone_not_found'),
                'data' => []
            ];
        }
        $data['zone_id'] = $zone['zone_id'];

        return [
            'success' => true,
            'data' => $data
        ];
    }

    /**
     * Validate stock and delivery availability
     *
     * @param Cart $cart
     * @param Address $address
     * @return array
     */
    private function validateStockAndDeliveryAvailability(Cart $cart, Address $address): array
    {
        // Verify stock availability for all cart items before creating order
        $stockVerification = $this->verifyCartItemsStock($cart);
        if ($stockVerification['success'] === false) {
            return $stockVerification;
        }

        // Check if all cart items are deliverable to the address location
        $deliveryCheck = DeliveryZoneService::checkDeliveryAvailability($cart, $address->latitude, $address->longitude);
        if (!empty($deliveryCheck['removed_items'])) {
            $undeliverableItems = array_map(function ($item) {
                return $item['product_name'] . ' from ' . $item['store_name'];
            }, $deliveryCheck['removed_items']);

            return [
                'success' => false,
                'message' => __('messages.items_not_deliverable_to_address'),
                'data' => [
                    'undeliverable_items' => $undeliverableItems,
                    'details' => $deliveryCheck['removed_items']
                ]
            ];
        }

        return ['success' => true];
    }

    /**
     * Process order creation
     *
     * @param User $user
     * @param Cart $cart
     * @param array $data
     * @return Order
     */
    private function processOrderCreation(User $user, Cart $cart, array $data): Order
    {
        $data['minimumCartAmount'] = $setting->value['minimumCartAmount'] ?? 1;
        return $this->createOrderFromCart($user, $cart, $data);
    }

    /**
     * Process payment transactions
     *
     * @param User $user
     * @param Order $order
     * @param array $data
     * @return array
     */
    private function processPaymentTransactions(User $user, Order $order, array $data): array
    {
        // Create payment transaction for online payments
        if ($data['use_wallet'] === '1') {
            $walletPaymentData = [
                'order_id' => $order->id,
                'amount' => $data['payment_type'] == PaymentTypeEnum::WALLET() ? $order->total_payable : $order->wallet_balance,
                'description' => "Wallet balance of $order->currency_code $order->wallet_balance was used for Order #$order->id."
            ];
            $walletTransaction = WalletService::deductBalance($user->id, $walletPaymentData);
            if ($walletTransaction['success'] === false) {
                Log::error('Error deducting wallet balance: ' . $walletTransaction['message']);
                return ['success' => false, 'message' => $walletTransaction['message']];
            }
        }
        if ($data['payment_type'] == PaymentTypeEnum::WALLET() && ($walletTransaction['success'] ?? false)) {
            $paymentInfo = [
                'transaction_id' => "wallet_" . time(),
                'amount' => $order->wallet_balance,
                'currency' => $order->currency_code,
                'payment_method' => $data['payment_type'],
                'message' => 'Payment verification done',
                'payment_status' => PaymentStatusEnum::COMPLETED(),
            ];
            $this->paymentService->makeOrderPaymentTransaction($order, $paymentInfo, PaymentStatusEnum::COMPLETED());
            Order::capturePaymentFromOrder($order);
            OrderItem::capturePayment($order->id);
            return ['success' => true, 'message' => 'Wallet payment & order capture done'];
        }

        return ['success' => true, 'message' => 'Payment verification done'];
    }

    /**
     * Finalize order creation
     *
     * @param Order $order
     * @param Cart $cart
     * @param array $data
     * @return array
     */
    private function finalizeOrderCreation(Order $order, Cart $cart, $user, array $data): array
    {
        // Create order items and seller orders
        $orderItemResponse = $this->createOrderItemsAndSellerOrders($order, $cart);
        if ($orderItemResponse['success'] === false) {
            return [
                'success' => false,
                'message' => $orderItemResponse['message'],
                'data' => []
            ];
        }
        //  Handle payment processing
        $paymentResult = $this->processPaymentTransactions($user, $order, $data);
        if (!$paymentResult['success']) {
            return [
                'success' => false,
                'message' => $paymentResult['message'],
                'data' => []
            ];
        }

        // pre-payment webhook verification
        $this->paymentService->prePaymentOrderVerification(transactionId: $data['transaction_id'] ?? "", order: $order);

        // post-payment initialization
        $postPayment = $this->paymentService->postPaymentInitialtion(order: $order, redirectUrl: $data['redirect_url'] ?? null);

        // Load order relationships for response
        $order->load(['items.product', 'items.variant', 'items.store', 'user', 'sellerOrders.seller.user']);
        $order->payment_response = $postPayment['data'] ?? null;
        $cart->items()->delete();
        event(new OrderPlaced($order));

        return [
            'success' => true,
            'order' => $order
        ];
    }

    /**
     * Handle order creation failure
     *
     * @param array $data
     * @return void
     */
    private function handleOrderCreationFailure(array $data): void
    {
        if ($data['payment_type'] !== PaymentTypeEnum::COD() && $data['payment_type'] !== PaymentTypeEnum::WALLET() && $data['payment_type'] !== PaymentTypeEnum::FLUTTERWAVE()) {
            $this->paymentService->processOrderRefund(paymentMethod: $data['payment_type'], transactionId: $data['transaction_id']);
        }
    }

    /**
     * Create an order from cart data
     *
     * @param User $user The user placing the order
     * @param Cart $cart The user's cart
     * @param array $data Order data
     * @return array The created order
     * @throws Exception
     */
    private
    function createOrderFromCart(User $user, Cart $cart, array $data): mixed
    {
        // Calculate cart totals
        $cartService = app(CartService::class);
        $paymentSummary = $cartService->getPaymentSummary(cart: $cart, latitude: $data['address']['latitude'], longitude: $data['address']['longitude'], isRushDelivery: $data['rush_delivery'] ?? false, useWallet: $data['use_wallet'] ?? false, promoCode: $data['promo_code'] ?? null);

        if ($paymentSummary['payable_amount'] < $data['minimumCartAmount'] && $data['payment_type'] !== PaymentTypeEnum::WALLET()) {
            throw new Exception(__('labels.minimum_cart_amount_not_met', ['amount' => $data['minimumCartAmount']]));
        }

        // Create order
        $order = Order::create([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'slug' => Str::slug('order-' . time() . '-' . $user->id),
            'email' => $user->email,
            'ip_address' => request()->ip(),
            'currency_code' => 'USD', // This should be dynamic based on settings
            'currency_rate' => 1, // This should be dynamic based on settings
            'payment_method' => $data['payment_type'],
            'payment_status' => PaymentStatusEnum::PENDING(),
            'fulfillment_type' => 'hyperlocal',
            'is_rush_order' => (bool)$data['rush_delivery'] ?? false,
            'estimated_delivery_time' => $paymentSummary['estimated_delivery_time'] ?? null,
            'delivery_time_slot_id' => $data['delivery_time_slot_id'] ?? null,
            'delivery_zone_id' => $data['zone_id'] ?? null,
            'delivery_boy_id' => null, // Will be assigned later
            'wallet_balance' => $paymentSummary['wallet_amount_used'] ?? 0,
            'promo_code' => $paymentSummary['promo_code'] ?? null,
            'promo_discount' => $paymentSummary['promo_discount'] ?? 0,
            'gift_card' => $data['gift_card'] ?? null,
            'gift_card_discount' => $data['gift_card_discount'] ?? 0,
            'delivery_charge' => $paymentSummary['total_delivery_charges'] ?? 0,
            'handling_charges' => $paymentSummary['handling_charges'] ?? 0,
            'per_store_drop_off_fee' => $paymentSummary['per_store_drop_off_fee'] ?? 0,
            'subtotal' => $paymentSummary['items_total'],
            'total_payable' => $paymentSummary['payable_amount'],
            'final_total' => $paymentSummary['order_total'],
            'status' => ($data['payment_type'] === PaymentTypeEnum::COD()) ? OrderStatusEnum::AWAITING_STORE_RESPONSE() : OrderStatusEnum::PENDING(),
            // Billing info
            'billing_name' => $user->name,
            'billing_address_1' => $data['address']['address_line1'],
            'billing_address_2' => $data['address']['address_line2'],
            'billing_landmark' => $data['address']['landmark'] ?? '',
            'billing_zip' => $data['address']['zipcode'],
            'billing_phone' => $data['address']['mobile'],
            'billing_address_type' => $data['address']['address_type'],
            'billing_latitude' => $data['address']['latitude'],
            'billing_longitude' => $data['address']['longitude'],
            'billing_city' => $data['address']['city'],
            'billing_state' => $data['address']['state'],
            'billing_country' => $data['address']['country'],
            'billing_country_code' => $data['address']['country_code'],

            // Shipping info (same as billing)
            'shipping_name' => $user->name,
            'shipping_address_1' => $data['address']['address_line1'],
            'shipping_address_2' => $data['address']['address_line2'],
            'shipping_landmark' => $data['address']['landmark'] ?? '',
            'shipping_zip' => $data['address']['zipcode'],
            'shipping_phone' => $data['address']['mobile'],
            'shipping_address_type' => $data['address']['address_type'],
            'shipping_latitude' => $data['address']['latitude'],
            'shipping_longitude' => $data['address']['longitude'],
            'shipping_city' => $data['address']['city'],
            'shipping_state' => $data['address']['state'],
            'shipping_country' => $data['address']['country'],
            'shipping_country_code' => $data['address']['country_code'],

            // order note
            'order_note' => $data['order_note'] ?? '',
        ]);

        if (!empty($paymentSummary['promo_applied'])) {
            Promo::where('id', $paymentSummary['promo_applied']['id'])
                ->increment('usage_count');

            OrderPromoLine::create([
                'order_id' => $order->id,
                'promo_id' => $paymentSummary['promo_applied']['id'],
                'promo_code' => $paymentSummary['promo_applied']['code'],
                'discount_amount' => $paymentSummary['promo_discount'],
                'cashback_flag' => !($paymentSummary['promo_applied']['promo_mode'] === PromoModeEnum::INSTANT()),
                'is_awarded' => $paymentSummary['promo_applied']['promo_mode'] === PromoModeEnum::INSTANT(),
            ]);
        }
        return $order;
    }

    /**
     * Create order items and seller orders from cart items
     *
     * @param Order $order The order to create items for
     * @param Cart $cart The cart containing items
     * @return array
     */
    private
    function createOrderItemsAndSellerOrders(Order $order, Cart $cart): array
    {
        try {
            // Group cart items by store
            $itemsByStore = $cart->items->groupBy('store_id');

            foreach ($itemsByStore as $storeItems) {
                $this->processStoreItems($order, $storeItems);
            }

            return [
                'success' => true,
                'message' => __('labels.success'),
                'data' => [],
            ];
        } catch (Exception $e) {
            Log::error('Error creating order items and seller orders', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => __('messages.order_creation_failed'),
                'data' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * @throws Exception
     */
    private
    function processStoreItems(Order $order, $storeItems): void
    {
        $storeTotalPrice = 0;

        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_id' => $storeItems->first()->store->seller_id,
            'total_price' => 0, // update later
        ]);

        foreach ($storeItems as $cartItem) {
            $result = $this->processCartItem($order, $cartItem, $sellerOrder, $storeTotalPrice);
            if ($result !== true) {
                throw new Exception($result['message']);
            }
        }

        $sellerOrder->update(['total_price' => $storeTotalPrice]);
    }

    private
    function processCartItem(Order $order, $cartItem, SellerOrder $sellerOrder, &$storeTotalPrice): true|array
    {
        $storeVariant = $this->getStoreVariant($cartItem);

        if ($storeVariant->stock < $cartItem->quantity) {
            return [
                'success' => false,
                'message' => ucfirst($storeVariant->productVariant->title ?? '')
                    . " " . __('messages.product_variant_not_available_in_store'),
                'data' => [],
            ];
        }

        [$subtotal, $adminCommissionAmount, $promoDiscount, $taxPercent] =
            $this->calculatePricing($order, $cartItem, $storeVariant);

        $storeTotalPrice += $subtotal;

        $orderItem = $this->createOrderItem($order, $cartItem, $storeVariant, $subtotal, $adminCommissionAmount, $promoDiscount, $taxPercent);

        // Attach required attachments to order item using Spatie Media Library
        $this->attachRequiredOrderItemAttachments($orderItem, $cartItem);
        $this->createSellerOrderItem($sellerOrder, $cartItem, $orderItem, $storeVariant);

        return true;
    }

    /**
     * Attach uploaded files to order item when the product requires attachments.
     */
    private function attachRequiredOrderItemAttachments(OrderItem $orderItem, $cartItem): void
    {
        try {
            $product = $cartItem->product;
            if (!$product) {
                return;
            }
            $requires = (string)$product->is_attachment_required === '1' || $product->is_attachment_required === 1 || $product->is_attachment_required === true;
            if (!$requires) {
                return; // Only attach when required
            }

            $productId = (string)$product->id;
            $request = request();
            // Support both attachments and attchment keys
            $files = $request->file("attachments.$productId");
            if (!$files) {
                $files = $request->file("attchment.$productId");
            }
            if (!$files) {
                return; // Validation should have caught missing files, but be safe
            }
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                if ($file) {
                    $orderItem->addMedia($file)
                        ->toMediaCollection(SpatieMediaCollectionName::ORDER_ITEM_ATTACHMENTS());
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to attach order item attachments', [
                'order_item_id' => $orderItem->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private
    function getStoreVariant($cartItem)
    {
        return $cartItem->variant->storeProductVariants
            ->where('store_id', $cartItem->store->id)
            ->first();
    }

    private
    function calculatePricing(Order $order, $cartItem, $storeVariant): array
    {
        $commission = $storeVariant->category_commission->commission ?? 0;
        $specialPrice = $storeVariant->special_price_exclude_tax;
        $specialPriceWithTax = $storeVariant->special_price;

        $subtotal = $cartItem->quantity * $specialPriceWithTax;
        $adminCommissionAmount = $subtotal * $commission / 100;

        $taxPercent = StoreProductVariant::scopeTaxPercentage($specialPrice, $specialPriceWithTax);

        $promoDiscount = 0;
        if (!empty($order['promo_code'])) {
            $promoDiscount = ((float)$subtotal / $order->subtotal) * $order->promo_discount;
        }

        return [$subtotal, $adminCommissionAmount, $promoDiscount, $taxPercent];
    }

    private
    function createOrderItem(Order $order, $cartItem, $storeVariant, $subtotal, $adminCommissionAmount, $promoDiscount, $taxPercent): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $cartItem->product_id,
            'product_variant_id' => $cartItem->product_variant_id,
            'store_id' => $cartItem->store_id,
            'title' => $cartItem->product->title,
            'variant_title' => $cartItem->variant->title,
            'gift_card_discount' => 0,
            'admin_commission_amount' => $adminCommissionAmount,
            'seller_commission_amount' => 0,
            'commission_settled' => '0',
            'return_eligible' => $cartItem->product->is_returnable == "1" ? '1' : '0',
            'returnable_days' => $cartItem->product->returnable_days ?? 0,
            'discounted_price' => 0,
            'promo_discount' => $promoDiscount,
            'discount' => 0,
            'tax_amount' => (float)$storeVariant->special_price - $storeVariant->special_price_exclude_tax,
            'tax_percent' => (float)$taxPercent,
            'sku' => $storeVariant->sku ?? "N/A",
            'quantity' => (float)$cartItem->quantity,
            'price' => (float)$storeVariant->special_price_exclude_tax,
            'subtotal' => (float)$subtotal,
            'status' => $this->determineOrderItemStatus($order),
            'otp' => $cartItem->product->requires_otp ? mt_rand(100000, 999999) : null,
        ]);
    }

    private
    function createSellerOrderItem(SellerOrder $sellerOrder, $cartItem, OrderItem $orderItem, $storeVariant): void
    {
        SellerOrderItem::create([
            'seller_order_id' => $sellerOrder->id,
            'product_id' => $cartItem->product_id,
            'product_variant_id' => $cartItem->product_variant_id,
            'order_item_id' => $orderItem->id,
            'quantity' => (float)$cartItem->quantity,
            'price' => (float)$storeVariant->special_price_exclude_tax,
        ]);
    }

    private
    function determineOrderItemStatus(Order $order): string
    {
        return in_array($order->payment_method, [PaymentTypeEnum::WALLET(), PaymentTypeEnum::COD()])
            ? OrderItemStatusEnum::AWAITING_STORE_RESPONSE()
            : OrderItemStatusEnum::PENDING();
    }

    /**
     * Verify stock availability for all cart items before creating order
     *
     * @param Cart $cart The cart to verify stock for
     * @return array Result containing success status, message, and data
     */
    private
    function verifyCartItemsStock(Cart $cart): array
    {
        try {
            $outOfStockItems = [];

            foreach ($cart->items as $cartItem) {
                $storeVariant = $this->getStoreVariant($cartItem);

                if (!$storeVariant) {
                    $outOfStockItems[] = [
                        'product' => $cartItem->product->title ?? 'Unknown Product',
                        'variant' => $cartItem->variant->title ?? 'Unknown Variant',
                        'store' => $cartItem->store->name ?? 'Unknown Store',
                        'requested_quantity' => $cartItem->quantity,
                        'available_stock' => 0,
                        'message' => 'Product variant not available in store'
                    ];
                    continue;
                }

                if ($storeVariant->stock < $cartItem->quantity) {
                    $outOfStockItems[] = [
                        'product' => $cartItem->product->title ?? 'Unknown Product',
                        'variant' => $cartItem->variant->title ?? 'Unknown Variant',
                        'store' => $cartItem->store->name ?? 'Unknown Store',
                        'requested_quantity' => $cartItem->quantity,
                        'available_stock' => $storeVariant->stock,
                        'message' => 'Insufficient stock available'
                    ];
                }
            }

            if (!empty($outOfStockItems)) {
                // Create a user-friendly message
                $itemNames = array_map(function ($item) {
                    return $item['product'] . ($item['variant'] ? ' (' . $item['variant'] . ')' : '');
                }, $outOfStockItems);

                $message = count($outOfStockItems) === 1
                    ? $itemNames[0] . ' is not available in sufficient quantity.'
                    : 'The following items are not available in sufficient quantity: ' . implode(', ', $itemNames);

                return [
                    'success' => false,
                    'message' => $message,
                    'data' => [
                        'out_of_stock_items' => $outOfStockItems
                    ]
                ];
            }

            return [
                'success' => true,
                'message' => 'All items have sufficient stock',
                'data' => []
            ];

        } catch (Exception $e) {
            Log::error('Error verifying cart items stock', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }


    /**
     * Get order details
     *
     * @param User $user The user requesting the order
     * @param string $orderSlug The order slug
     * @return array Result containing success status, message, and order data
     */
    public
    function getOrder(User $user, string $orderSlug): array
    {
        try {
            $order = Order::where('user_id', $user->id)
                ->where('slug', $orderSlug)
                ->with(['items.product', 'items.variant', 'items.store', 'sellerFeedbacks', 'sellerOrders', 'items.returns', 'promoLine'])
                ->first();

            if (!$order) {
                return [
                    'success' => false,
                    'message' => __('messages.order_not_found'),
                    'data' => []
                ];
            }

            return [
                'success' => true,
                'message' => __('messages.order_retrieved_successfully'),
                'data' => $order
            ];

        } catch (Exception $e) {
            Log::error('Error retrieving order: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Get user's orders
     *
     * @param User $user The user requesting their orders
     * @return array Result containing success status, message, and orders data
     */
    public function getUserOrders(User $user, $perPage = 15, $filters = null): array
    {
        try {
            $query = Order::where('user_id', $user->id)
                ->with([
                    'deliveryBoy.user',
                    'items.product',
                    'items.variant',
                    'items.store',
                    'items.store.seller',
                    'sellerFeedbacks',
                    'sellerOrders',
                    'items.returns',
                    'promoLine'
                ]);

            // Apply Date Range Filter
            if ($filters['date_range']) {
                $fromDate = match ($filters['date_range']) {
                    DateRangeFilterEnum::LAST_30_MINUTES() => Carbon::now()->subMinutes(30),
                    DateRangeFilterEnum::LAST_1_HOUR() => Carbon::now()->subHour(),
                    DateRangeFilterEnum::LAST_5_HOURS() => Carbon::now()->subHours(5),
                    DateRangeFilterEnum::LAST_1_DAY() => Carbon::now()->subDay(),
                    DateRangeFilterEnum::LAST_7_DAYS() => Carbon::now()->subDays(7),
                    DateRangeFilterEnum::LAST_30_DAYS() => Carbon::now()->subDays(30),
                    DateRangeFilterEnum::LAST_365_DAYS() => Carbon::now()->subDays(365),
                    default => null,
                };

                if ($fromDate) {
                    $query->where('created_at', '>=', $fromDate);
                }
            }

            if ($filters['status']) {
                $query->where('status', $filters['status']);
            }

            $orders = $query
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return [
                'success' => true,
                'message' => __('messages.orders_retrieved_successfully'),
                'data' => $orders
            ];

        } catch (Exception $e) {
            Log::error('Error retrieving user orders: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Validate status transition for an order item
     *
     * @param string $currentStatus The current status of the order item
     * @param string $newStatus The new status to set
     * @param string $userType The type of user making the update ('seller' or 'delivery_boy')
     * @return array Result containing success status and message
     */
    private
    function validateStatusTransition(string $currentStatus, string $newStatus, string $userType): array
    {
        // Check if the current status is already the same as the update status
        if ($currentStatus === $newStatus) {
            return [
                'success' => false,
                'message' => __('messages.status_already_set')
            ];
        }

        if ($userType === 'seller') {
            // Seller-specific validations
            if ($newStatus === OrderItemStatusEnum::PREPARING() && $currentStatus !== OrderItemStatusEnum::ACCEPTED()) {
                return [
                    'success' => false,
                    'message' => __('labels.order_must_be_accepted_first')
                ];
            }
        } elseif ($userType === 'delivery_boy') {
            // Delivery boy-specific validations
            if ($newStatus === OrderItemStatusEnum::COLLECTED() && $currentStatus !== OrderItemStatusEnum::PREPARING()) {
                return [
                    'success' => false,
                    'message' => __('labels.order_item_must_be_preparing_first')
                ];
            }

            if ($newStatus === OrderItemStatusEnum::DELIVERED() && $currentStatus !== OrderItemStatusEnum::COLLECTED()) {
                return [
                    'success' => false,
                    'message' => __('labels.order_item_must_be_collected_first')
                ];
            }
        }

        return [
            'success' => true,
            'message' => ''
        ];
    }

    /**
     * Map input status to OrderItemStatusEnum
     *
     * @param string $status The input status
     * @param string $userType The type of user making the update ('seller' or 'delivery_boy')
     * @return string|null The mapped OrderItemStatusEnum value or null if invalid
     */
    private
    function mapStatusToEnum(string $status, string $userType): ?string
    {
        return match ($userType) {
            'seller' => match ($status) {
                'accept' => OrderItemStatusEnum::ACCEPTED(),
                'reject' => OrderItemStatusEnum::REJECTED(),
                'preparing' => OrderItemStatusEnum::PREPARING(),
                default => null,
            },
            'delivery_boy' => match ($status) {
                'collected' => OrderItemStatusEnum::COLLECTED(),
                'delivered' => OrderItemStatusEnum::DELIVERED(),
                default => null,
            },
            default => null,
        };
    }


    /**
     * Update order status and related entities
     *
     * @param OrderItem $orderItem The order item to update
     * @param string $newStatus The new status to set
     * @param string $oldStatus The old status
     * @param string $userType The type of user making the update ('seller' or 'delivery_boy')
     * @param int|null $userId The ID of the user making the update (seller ID or delivery boy ID)
     * @return array Result containing success status, message, and data
     */
    private
    function updateOrderStatusAndRelatedEntities(
        OrderItem $orderItem,
        string    $newStatus,
        string    $oldStatus,
        string    $userType,
        ?int      $userId = null
    ): array
    {
        try {
            DB::beginTransaction();

            $returnDeadline = null;
            if ($orderItem->return_eligible && $orderItem->returnable_days > 0) {
                $returnDeadline = $this->getReturnDeadline($orderItem->returnable_days);
            }
            $orderItem->update([
                'status' => $newStatus,
                'return_deadline' => $returnDeadline,
            ]);

            // Create seller settlement credit on item delivered
            if ($userType === 'delivery_boy' && $newStatus === OrderItemStatusEnum::DELIVERED()) {
                try {
                    // avoid duplicate credits for the same item
                    $exists = SellerStatement::where('order_item_id', $orderItem->id)
                        ->where('entry_type', SellerSettlementTypeEnum::CREDIT())
                        ->where(function ($q) {
                            $q->where('reference_type', 'order_item_delivery')
                                ->orWhereNull('reference_type');
                        })
                        ->exists();
                    if (!$exists) {
                        $this->sellerStatementService->addEntry(data: [
                            'seller_id' => $orderItem->store->seller_id,
                            'entry_type' => SellerSettlementTypeEnum::CREDIT(),
                            'amount' => ($orderItem->subtotal - $orderItem->admin_commission_amount),
                            'currency_code' => $orderItem->order->currency_code ?? null,
                            'order_id' => $orderItem->order_id,
                            'order_item_id' => $orderItem->id,
                            'reference_type' => 'order_item_delivery',
                            'reference_id' => $orderItem->id,
                            'description' => 'Seller earning for delivered Order Item #' . $orderItem->id,
                            'meta' => [
                                'product_id' => $orderItem->product_id,
                                'quantity' => $orderItem->quantity,
                            ],
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to create seller settlement credit on delivery', [
                        'order_item_id' => $orderItem->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $orderItemsStatuses = $this->getOrderItemsStatuses($orderItem->order_id);

            $result = ($userType === 'seller')
                ? $this->handleSellerFlow($orderItem, $newStatus, $oldStatus, $userId, $orderItemsStatuses)
                : $this->handleDeliveryBoyFlow(orderItem: $orderItem, orderItemsStatuses: $orderItemsStatuses, userId: $userId);

            event(new OrderStatusUpdated(
                orderItem: $orderItem,
                oldStatus: $oldStatus,
                newStatus: $newStatus
            ));

//            DB::rollBack();
            DB::commit();
            return $result;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating order status', [
                'order_item_id' => $orderItem->id,
                'new_status' => $newStatus,
                'user_type' => $userType,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => __('messages.order_status_update_failed'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    private
    function getOrderItemsStatuses(int $orderId): array
    {
        return OrderItem::where('order_id', $orderId)->pluck('status')->toArray();
    }

    private
    function handleSellerFlow(OrderItem $orderItem, string $newStatus, string $oldStatus, ?int $userId, array $orderItemsStatuses): array
    {
        if (in_array($orderItem->order->status, [
            OrderStatusEnum::AWAITING_STORE_RESPONSE(),
            OrderStatusEnum::PARTIALLY_ACCEPTED(),
            OrderStatusEnum::PENDING()
        ])) {
            $orderStatus = $this->determineSellerOrderStatus($orderItemsStatuses);
            $orderItem->order->update(['status' => $orderStatus]);
        }

        $sellerOrderItem = SellerOrderItem::where('order_item_id', $orderItem->id)
            ->with('sellerOrder')
            ->whereHas('sellerOrder', fn($q) => $q->where('seller_id', $userId))
            ->first();

        if ($sellerOrderItem) {
            if ($newStatus === OrderItemStatusEnum::REJECTED()) {
                $this->recalculateOrderAmounts($orderItem->order->id, $orderItem);
                $this->processOrderItemRefund(orderItem: $orderItem, type: OrderItemStatusEnum::REJECTED());
            }
        }

        return [
            'success' => true,
            'message' => __('messages.order_status_updated_successfully'),
            'data' => $sellerOrderItem
        ];
    }

    private
    function handleDeliveryBoyFlow(OrderItem $orderItem, array $orderItemsStatuses, ?int $userId): array
    {
        $orderStatus = $this->determineDeliveryOrderStatus($orderItemsStatuses);

        if ($orderStatus !== $orderItem->order->status) {
            $this->updateDeliveryOrderAndAssignments($orderItem, $orderStatus, $userId);
        }

        return [
            'success' => true,
            'message' => __('messages.order_status_updated_successfully'),
            'data' => [
                'order_item' => $orderItem->fresh(),
                'order' => $orderItem->order->fresh()
            ]
        ];
    }

    private
    function updateDeliveryOrderAndAssignments(OrderItem $orderItem, string $orderStatus, ?int $userId): void
    {
        $orderData = ['status' => $orderStatus];

        if (!$userId || !in_array($orderStatus, [OrderStatusEnum::OUT_FOR_DELIVERY(), OrderStatusEnum::DELIVERED()])) {
            return;
        }

        $assignmentStatus = $orderStatus === OrderStatusEnum::OUT_FOR_DELIVERY()
            ? DeliveryBoyAssignmentStatusEnum::IN_PROGRESS()
            : DeliveryBoyAssignmentStatusEnum::COMPLETED();

        if ($orderStatus === OrderStatusEnum::DELIVERED()) {
            if ($orderItem->order->payment_method === PaymentTypeEnum::COD()) {
                $orderData['payment_status'] = PaymentStatusEnum::COMPLETED();
            }

            $orderItem->order->update($orderData);

            event(new OrderDelivered($orderItem->order));
            $this->handleCashCollection($orderItem, $userId);
        }

        DeliveryBoyAssignment::where('order_id', $orderItem->order_id)
            ->where('delivery_boy_id', $userId)
            ->update(['status' => $assignmentStatus]);
    }

    private
    function handleCashCollection(OrderItem $orderItem, int $userId): void
    {
        if ($orderItem->order->payment_method !== PaymentTypeEnum::COD()) {
            return;
        }

        $assignmentId = DeliveryBoyAssignment::where('order_id', $orderItem->order_id)
            ->where('delivery_boy_id', $userId)
            ->value('id');

        DeliveryBoyAssignment::where('id', $assignmentId)->update([
            'cod_cash_collected' => $orderItem->order->total_payable,
            'cod_submission_status' => 'pending'
        ]);

        DeliveryBoyCashTransaction::create([
            'delivery_boy_assignment_id' => $assignmentId,
            'order_id' => $orderItem->order_id,
            'delivery_boy_id' => $userId,
            'amount' => $orderItem->order->total_payable,
            'transaction_type' => 'collected',
            'transaction_date' => now()
        ]);
    }


    /**
     * Update the status of an order item
     *
     * @param int $orderItemId The ID of the order item to update
     * @param string $status The new status to set ('accept', 'reject', 'preparing')
     * @param int $sellerId The ID of the seller making the update
     * @return array Result containing success status, message, and order data
     */
    public
    function updateOrderStatusBySeller(int $orderItemId, string $status, int $sellerId): array
    {
        try {
            // Validate status parameter
            if (!in_array($status, ['accept', 'reject', 'preparing'])) {
                return [
                    'success' => false,
                    'message' => __('labels.invalid_status'),
                    'data' => []
                ];
            }

            $sellerOrderItem = SellerOrderItem::where('order_item_id', $orderItemId)
                ->with(['sellerOrder', 'orderItem', 'orderItem.product', 'orderItem.store.seller.user'])
                ->whereHas('sellerOrder', function ($q) use ($sellerId) {
                    $q->where('seller_id', $sellerId);
                })
                ->first();

            if (!$sellerOrderItem) {
                return [
                    'success' => false,
                    'message' => __('labels.order_item_not_found'),
                    'data' => []
                ];
            }

            $orderItem = $sellerOrderItem->orderItem;
            $currentStatus = $orderItem->status;
            if ($currentStatus === OrderItemStatusEnum::PENDING()) {
                return [
                    'success' => false,
                    'message' => __('labels.order_payment_pending_cannot_update_status'),
                    'data' => []
                ];
            }
            if (in_array($currentStatus, [OrderItemStatusEnum::FAILED(), OrderItemStatusEnum::CANCELLED(), OrderItemStatusEnum::COLLECTED(), OrderItemStatusEnum::DELIVERED()])) {
                return [
                    'success' => false,
                    'message' => __('labels.cannot_update_status_because_status_is_already', ['status' => $currentStatus]),
                    'data' => []
                ];
            }

            $updateStatus = $this->mapStatusToEnum($status, 'seller');

            if (!$updateStatus) {
                return [
                    'success' => false,
                    'message' => __('labels.invalid_status'),
                    'data' => []
                ];
            }

            // Validate the status transition
            $validationResult = $this->validateStatusTransition($currentStatus, $updateStatus, 'seller');
            if (!$validationResult['success']) {
                return [
                    'success' => false,
                    'message' => $validationResult['message'],
                    'data' => []
                ];
            }

            // Update the order status and related entities
            return $this->updateOrderStatusAndRelatedEntities(
                $orderItem,
                $updateStatus,
                $currentStatus,
                'seller',
                $sellerId
            );
        } catch (Exception $e) {
            Log::error('Error in updateOrderStatusBySeller', [
                'order_item_id' => $orderItemId,
                'status' => $status,
                'seller_id' => $sellerId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => __('messages.order_status_update_failed'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    function getReturnDeadline(int $days, string $fromDate = null): string
    {
        $baseDate = $fromDate ? Carbon::parse($fromDate) : Carbon::now();
        return $baseDate->addDays($days)->format('Y-m-d');
    }

    /**
     * Determine the overall order status based on the statuses of all order items
     *
     * @param array $orderItemsStatuses Array of order item statuses
     * @return string The determined order status
     */

    /**
     * Recalculate order amounts after an item is rejected
     *
     * @param int $orderId The order ID
     * @param \App\Models\OrderItem|null $rejectedItem The rejected order item (optional)
     * @return array Result containing success status, message, and data
     */
    public
    function recalculateOrderAmounts(int $orderId, $rejectedItem = null): array
    {
        try {
            DB::beginTransaction();
            // Get the order
            $order = Order::findOrFail($orderId);

            // Get all active items (excluding rejected and cancelled) for this order
            $query = $order->items()->whereNotIn('status', [
                OrderItemStatusEnum::REJECTED(),
                OrderItemStatusEnum::CANCELLED()
            ]);
            if ($rejectedItem) {
                $query->where('id', '!=', $rejectedItem->id);
            }
            $newPromoDiscount = $order->promo_discount;
            if (!empty($order->promo_code)) {
                $orderPromoLine = OrderPromoLine::where('order_id', $orderId)->get()->first();
                $newPromoDiscount = $order->promo_discount - $rejectedItem->promo_discount;
                $orderPromoLine->update(['discount_amount' => $newPromoDiscount]);
            }

            $activeItems = $query->get();

            // Calculate new subtotal (sum of all non-rejected items)
            $newSubtotal = $activeItems->sum('subtotal');

            $handlingCharges = (float)($order->handling_charges ?? 0);
            $perStoreDropOffFee = (float)($order->per_store_drop_off_fee ?? 0);
            $deliveryCharge = (float)($order->delivery_charge ?? 0);

            $newFinalTotal = $newSubtotal
                + $deliveryCharge
                + $handlingCharges
                + $perStoreDropOffFee
                - (($newPromoDiscount ?? 0) + ($order->gift_card_discount ?? 0));

            // Calculate new total payable (final total - wallet balance)
            $newTotalPayable = max(0, $newFinalTotal - $order->wallet_balance);

            // Update the order with new amounts
            $order->update([
                'subtotal' => $newSubtotal,
                'final_total' => $newFinalTotal,
                'total_payable' => $newTotalPayable,
                'promo_discount' => $newPromoDiscount,
            ]);

            // Update seller orders if they exist
            if ($rejectedItem) {
                $sellerOrder = SellerOrder::where('order_id', $orderId)
                    ->where('seller_id', $rejectedItem->store->seller_id)
                    ->first();

                if ($sellerOrder) {
                    // Calculate new seller order total (sum of all non-rejected items for this seller)
                    $sellerItems = $activeItems->filter(function ($item) use ($sellerOrder) {
                        return $item->store->seller_id == $sellerOrder->seller_id;
                    });

                    $newSellerTotal = $sellerItems->sum('subtotal');

                    // Update seller order total
                    $sellerOrder->update([
                        'total_price' => $newSellerTotal
                    ]);
                }
            } else {
                // Update all seller orders
                foreach ($order->sellerOrders as $sellerOrder) {
                    $sellerItems = $activeItems->filter(function ($item) use ($sellerOrder) {
                        return $item->store->seller_id == $sellerOrder->seller_id;
                    });

                    $newSellerTotal = $sellerItems->sum('subtotal');

                    // Update seller order total
                    $sellerOrder->update([
                        'total_price' => $newSellerTotal
                    ]);
                }
            }
            DB::commit();
            Log::info('Order amounts recalculated', [
                'order_id' => $orderId,
                'rejected_item_id' => $rejectedItem ? $rejectedItem->id : null,
                'new_subtotal' => $newSubtotal,
                'new_final_total' => $newFinalTotal,
                'new_total_payable' => $newTotalPayable
            ]);

            return [
                'success' => true,
                'message' => 'Order amounts recalculated successfully',
                'data' => [
                    'order_id' => $orderId,
                    'new_subtotal' => $newSubtotal,
                    'new_final_total' => $newFinalTotal,
                    'new_total_payable' => $newTotalPayable
                ]
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error recalculating order amounts', [
                'order_id' => $orderId,
                'rejected_item_id' => $rejectedItem ? $rejectedItem->id : null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error recalculating order amounts: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Cancel an order item
     *
     * @param User $user The user requesting the cancellation
     * @param int $orderItemId The order item ID to cancel
     * @return array Result containing success status, message, and data
     */
    public
    function cancelOrderItem(User $user, int $orderItemId): array
    {
        try {
            DB::beginTransaction();

            // Get the order item with relationships
            $orderItem = OrderItem::with(['order', 'product'])
                ->where('id', $orderItemId)
                ->whereHas('order', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->first();

            if (!$orderItem) {
                return [
                    'success' => false,
                    'message' => __('messages.order_item_not_found'),
                    'data' => []
                ];
            }

            // Check if the product is cancelable
            if (!$orderItem->product->is_cancelable) {
                return [
                    'success' => false,
                    'message' => __('messages.product_not_cancelable'),
                    'data' => []
                ];
            }

            // Check if the current status allows cancellation based on product's cancelable_till
            $statusHierarchy = OrderItem::getStatusHierarchy();
            $currentStatusLevel = $statusHierarchy[$orderItem->status] ?? 0;
            $cancelableTillLevel = $statusHierarchy[$orderItem->product->cancelable_till] ?? 0;

            if ($currentStatusLevel > $cancelableTillLevel || $currentStatusLevel === -1) {
                return [
                    'success' => false,
                    'message' => __('messages.order_item_cannot_be_cancelled_at_current_status'),
                    'data' => []
                ];
            }

            // Check if order item is already cancelled or in a terminal state
            if (in_array($orderItem->status, [
                OrderItemStatusEnum::CANCELLED(),
                OrderItemStatusEnum::REJECTED(),
                OrderItemStatusEnum::DELIVERED(),
                OrderItemStatusEnum::RETURNED(),
                OrderItemStatusEnum::REFUNDED(),
                OrderItemStatusEnum::FAILED()
            ])) {
                return [
                    'success' => false,
                    'message' => __('messages.order_item_already_in_terminal_state'),
                    'data' => []
                ];
            }

            $oldStatus = $orderItem->status;

            // Update order item status to cancelled
            $orderItem->update(['status' => OrderItemStatusEnum::CANCELLED()]);

            // Fire status updated event so listeners (e.g., stock restock) can react
            event(new OrderStatusUpdated(
                orderItem: $orderItem,
                oldStatus: $oldStatus,
                newStatus: OrderItemStatusEnum::CANCELLED()
            ));

            // Process refund if payment was made in advance
            $refundResult = $this->processOrderItemRefund($orderItem);
            if (!$refundResult['success']) {
                DB::rollBack();
                return $refundResult;
            }

            // Recalculate order pricing
            $recalculationResult = $this->recalculateOrderAmounts($orderItem->order_id, $orderItem);
            if (!$recalculationResult['success']) {
                DB::rollBack();
                return $recalculationResult;
            }

            // Update overall order status if needed
            $this->updateOrderStatusAfterCancellation($orderItem->order);

            DB::commit();

            Log::info('Order item cancelled successfully', [
                'order_item_id' => $orderItem->id,
                'order_id' => $orderItem->order_id,
                'user_id' => $user->id,
                'old_status' => $oldStatus,
                'refund_amount' => $refundResult['data']['refund_amount'] ?? 0
            ]);

            return [
                'success' => true,
                'message' => __('messages.order_item_cancelled_successfully'),
                'data' => [
                    'order_item' => $orderItem->fresh(),
                    'refund_details' => $refundResult['data'] ?? null
                ]
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error cancelling order item', [
                'order_item_id' => $orderItemId,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    public
    function returnOrderItem(User $user, array $data): array
    {
        try {
            $orderItem = OrderItem::with(['order', 'product', 'store'])
                ->where('id', $data['order_item_id'])
                ->whereHas('order', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->first();

            if (!$orderItem) {
                return ApiResponseType::toArray(
                    success: false,
                    message: __('messages.order_item_not_found'),
                );
            }

            // Check if the product is cancelable
            if (!$orderItem->return_eligible) {
                return ApiResponseType::toArray(
                    success: false,
                    message: __('messages.product_not_returnable'),
                );
            }

            if ($orderItem->status !== OrderItemStatusEnum::DELIVERED() && $orderItem->order->status !== OrderStatusEnum::DELIVERED()) {
                return ApiResponseType::toArray(
                    success: false,
                    message: __('messages.order_item_cannot_be_returned_at_current_status'),
                );
            }

            if ($orderItem->return_deadline < Carbon::now()->format('Y-m-d')) {
                return ApiResponseType::toArray(
                    success: false,
                    message: __('labels.return_deadline_expired'),
                );
            }

            $existingReturn = OrderItemReturn::where('order_item_id', $orderItem->id)
                ->where('return_status', '!=', OrderItemReturnStatusEnum::CANCELLED())->first();
            if ($existingReturn) {
                return ApiResponseType::toArray(
                    success: false,
                    message: __('messages.return_already_requested'),
                );
            }

            $return = OrderItemReturn::create([
                'order_item_id' => $orderItem->id,
                'order_id' => $orderItem->order_id,
                'user_id' => $user->id,
                'seller_id' => $orderItem->store->seller_id,
                'store_id' => $orderItem->store_id,
                'reason' => $data['reason'] ?? null,
                'refund_amount' => $orderItem->subtotal - $orderItem->promo_discount,
                'pickup_status' => OrderItemReturnPickupStatusEnum::PENDING(),
                'return_status' => OrderItemReturnStatusEnum::REQUESTED(),
            ]);
            if (!empty($data['images'])) {
                foreach ($data['images'] as $image) {
                    SpatieMediaService::uploadFromRequest($return, $image, SpatieMediaCollectionName::ITEM_RETURN_IMAGES());
                }
            }

            return ApiResponseType::toArray(
                success: true,
                message: __('messages.return_request_sent'),
                data: new OrderItemReturnResource($return)
            );
        } catch (Exception $e) {
            return ApiResponseType::toArray(
                success: false,
                message: $e->getMessage(),
                data: null
            );
        }
    }

    public
    function cancelReturnRequest(User $user, $orderItemId): array
    {
        try {

            $return = OrderItemReturn::where('order_item_id', $orderItemId)
                ->where('user_id', $user->id)
                ->where('return_status', '!=', OrderItemReturnStatusEnum::CANCELLED())->first();

            if (!$return) {
                return ApiResponseType::toArray(
                    success: false,
                    message: __('messages.return_request_not_found')
                );
            }

            if (!in_array($return->return_status, [OrderItemReturnStatusEnum::REQUESTED(), OrderItemReturnStatusEnum::SELLER_APPROVED()])) {
                return ApiResponseType::toArray(
                    success: false,
                    message: __('messages.return_cannot_be_cancelled_now')
                );
            }

            $return->update([
                'return_status' => OrderItemReturnStatusEnum::CANCELLED(),
            ]);

            return ApiResponseType::toArray(
                success: true,
                message: __('messages.return_request_cancelled'),
                data: $return
            );

        } catch (Exception $e) {
            return ApiResponseType::toArray(
                success: false,
                message: $e->getMessage()
            );
        }
    }

    /**
     * Process refund for cancelled order item
     *
     * @param OrderItem $orderItem The cancelled order item
     * @return array Result containing success status and refund details
     */
    public
    function processOrderItemRefund(OrderItem $orderItem, $type = 'cancel'): array
    {
        try {
            $order = $orderItem->order;
            $refundAmount = 0;

            // Calculate refund amount (item subtotal minus any discounts)
            $itemRefundAmount = $orderItem->subtotal - $orderItem->promo_discount;

            // Check if payment was made in advance (not COD)
            if ($order->payment_method !== PaymentTypeEnum::COD() && $order->payment_status === PaymentStatusEnum::COMPLETED() || $type === 'return_pickup') {

                $description = "Refund for cancelled order item #{$orderItem->id} from Order #{$order->id}";
                if ($type === OrderItemStatusEnum::REJECTED()) {
                    $description = "Refund issued for rejected order item #{$orderItem->id} from order #{$order->id} by the seller.";
                }
                // Add refund to wallet
                $walletData = [
                    'amount' => $itemRefundAmount,
                    'payment_method' => 'refund',
                    'transaction_reference' => "refund_order_item_{$orderItem->id}",
                    'description' => $description
                ];

                $walletResult = $this->walletService->addBalance($order->user_id, $walletData);

                if (!$walletResult['success']) {
                    return [
                        'success' => false,
                        'message' => __('messages.refund_processing_failed'),
                        'data' => ['error' => $walletResult['message']]
                    ];
                }

                $refundAmount = $itemRefundAmount;
            }

            return [
                'success' => true,
                'message' => __('messages.refund_processed_successfully'),
                'data' => [
                    'refund_amount' => $refundAmount,
                    'refund_method' => $refundAmount > 0 ? 'wallet' : 'none'
                ]
            ];

        } catch (Exception $e) {
            Log::error('Error processing order item refund', [
                'order_item_id' => $orderItem->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => __('messages.refund_processing_failed'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Update order status after item cancellation
     *
     * @param Order $order The order to update
     * @return void
     */
    private
    function updateOrderStatusAfterCancellation(Order $order): void
    {
        $orderItemsStatuses = $this->getOrderItemsStatuses($order->id);

        // Check if all items are cancelled
        $allCancelled = !empty($orderItemsStatuses) &&
            array_filter($orderItemsStatuses, fn($status) => $status !== OrderItemStatusEnum::CANCELLED()) === [];

        if ($allCancelled) {
            $order->update(['status' => OrderStatusEnum::CANCELLED()]);
        } else {
            // Update to partially cancelled or keep existing status based on remaining items
            $hasActiveItems = array_filter($orderItemsStatuses, fn($status) => !in_array($status, [
                OrderItemStatusEnum::CANCELLED(),
                OrderItemStatusEnum::REJECTED(),
                OrderItemStatusEnum::FAILED()
            ])
            );

            if (!empty($hasActiveItems)) {
                // Determine appropriate status based on remaining active items
                $newStatus = $this->determineSellerOrderStatus($orderItemsStatuses);
                if ($newStatus !== $order->status) {
                    $order->update(['status' => $newStatus]);
                }
            }
        }
    }

// This Function will only work when status updated from the seller side
    private
    function determineSellerOrderStatus(array $orderItemsStatuses): string
    {
        if (empty($orderItemsStatuses)) {
            return OrderStatusEnum::PENDING();
        }

        $allAwaitingResponse = array_filter($orderItemsStatuses, fn($status) => $status === OrderItemStatusEnum::AWAITING_STORE_RESPONSE());
        $allRejected = array_filter($orderItemsStatuses, fn($status) => $status === OrderItemStatusEnum::REJECTED());
        $accepted = array_filter($orderItemsStatuses, fn($status) => $status === OrderItemStatusEnum::ACCEPTED());
        $preparing = array_filter($orderItemsStatuses, fn($status) => $status === OrderItemStatusEnum::PREPARING());

        // All items are awaiting response
        if (count($allAwaitingResponse) === count($orderItemsStatuses)) {
            return OrderStatusEnum::AWAITING_STORE_RESPONSE();
        }

        // All items are rejected
        if (count($allRejected) === count($orderItemsStatuses)) {
            return OrderStatusEnum::REJECTED_BY_SELLER();
        }

        // All items are accepted and no items are awaiting response
        if (!empty($allAwaitingResponse) && (!empty($accepted) || !empty($allRejected) || !empty($preparing))) {
            return OrderStatusEnum::PARTIALLY_ACCEPTED();
        }

        // No items are awaiting response (all are either accepted, rejected, or in a later stage)
        if (empty($allAwaitingResponse)) {
            return OrderStatusEnum::READY_FOR_PICKUP();
        }
        return OrderStatusEnum::PENDING();
    }

    /**
     * Update the status of an order item by delivery boy
     *
     * @param int $orderItemId The ID of the order item to update
     * @param string $status The new status to set ('collected', 'delivered')
     * @param int $deliveryBoyId The ID of the delivery boy making the update
     * @param string|null $otp The OTP provided for verification (if required)
     * @return array Result containing success status, message, and order data
     */
    public
    function updateOrderItemStatusByDeliveryBoy(int $orderItemId, string $status, int $deliveryBoyId, ?string $otp = null): array
    {
        try {
            // Validate status parameter
            if (!in_array($status, ['collected', 'delivered'])) {
                return [
                    'success' => false,
                    'message' => __('labels.invalid_status'),
                    'data' => []
                ];
            }

            // Find the order item
            $orderItem = OrderItem::where('id', $orderItemId)
                ->whereHas('order', function ($q) use ($deliveryBoyId) {
                    $q->where('delivery_boy_id', $deliveryBoyId);
                })
                ->with(['order', 'product', 'store.seller.user'])
                ->first();

            if (!$orderItem) {
                return [
                    'success' => false,
                    'message' => __('labels.order_item_not_found'),
                    'data' => []
                ];
            }

            // Check if OTP verification is required for this product when delivering
            if ($status === OrderItemStatusEnum::DELIVERED() && $orderItem->product->requires_otp) {
                // If no OTP provided
                if (!$otp) {
                    return [
                        'success' => false,
                        'message' => __('labels.otp_required'),
                        'data' => []
                    ];
                }

                // If OTP doesn't match or hasn't been set yet
                if ($orderItem->otp && $orderItem->otp !== $otp) {
                    return [
                        'success' => false,
                        'message' => __('labels.invalid_otp'),
                        'data' => []
                    ];
                }

                // If OTP hasn't been set yet, set it now (first delivery attempt)
                if (!$orderItem->otp) {
                    $orderItem->otp = $otp;
                }

                // Mark as OTP verified
                $orderItem->otp_verified = true;
                $orderItem->save();
            }

            $currentStatus = $orderItem->status;
            $updateStatus = $this->mapStatusToEnum($status, 'delivery_boy');

            if (!$updateStatus) {
                return [
                    'success' => false,
                    'message' => __('labels.invalid_status'),
                    'data' => []
                ];
            }

            // Validate the status transition
            $validationResult = $this->validateStatusTransition($currentStatus, $updateStatus, 'delivery_boy');
            if (!$validationResult['success']) {
                return [
                    'success' => false,
                    'message' => $validationResult['message'],
                    'data' => []
                ];
            }

            // Update the order status and related entities
            return $this->updateOrderStatusAndRelatedEntities(
                $orderItem,
                $updateStatus,
                $currentStatus,
                'delivery_boy',
                $deliveryBoyId
            );
        } catch (Exception $e) {
            Log::error('Error in updateOrderItemStatusByDeliveryBoy', [
                'order_item_id' => $orderItemId,
                'status' => $status,
                'delivery_boy_id' => $deliveryBoyId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => __('messages.order_status_update_failed'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Determine the overall order status based on the statuses of all order items for delivery
     *
     * @param array $orderItemsStatuses Array of order item statuses
     * @return string The determined order status
     */
    private
    function determineDeliveryOrderStatus(array $orderItemsStatuses): string
    {
        if (empty($orderItemsStatuses)) {
            return OrderStatusEnum::PENDING();
        }

        // Count items by status
        $collected = array_filter($orderItemsStatuses, fn($status) => $status === OrderItemStatusEnum::COLLECTED());
        $delivered = array_filter($orderItemsStatuses, fn($status) => $status === OrderItemStatusEnum::DELIVERED());
//        $rejected = array_filter($orderItemsStatuses, fn($status) => $status === OrderItemStatusEnum::REJECTED());
        $nonRejected = array_filter($orderItemsStatuses, fn($status) => $status !== OrderItemStatusEnum::REJECTED());

        // If all non-rejected items are delivered, mark order as DELIVERED
        if (count($delivered) === count($nonRejected)) {
            return OrderStatusEnum::DELIVERED();
        }

        // If all non-rejected items are collected (or delivered), mark order as OUT_FOR_DELIVERY
        if (count($collected) + count($delivered) === count($nonRejected)) {
            return OrderStatusEnum::OUT_FOR_DELIVERY();
        }

        // Default to ASSIGNED if the order has a delivery boy but no collected/delivered items yet
        return OrderStatusEnum::ASSIGNED();
    }

    public
    function getOrderDeliveryBoyLocation(User $user, string $orderSlug): array
    {
        try {
            // Find the order with the given ID and user
            $order = Order::where('slug', $orderSlug)
                ->where('user_id', $user->id)
                ->with(['items.product', 'items.variant', 'items.store', 'deliveryBoy'])
                ->first();

            if (!$order) {
                return [
                    'success' => false,
                    'message' => __('labels.order_not_found'),
                    'data' => []
                ];
            }
            // Get store IDs from order items
            $storeIds = $order->items->pluck('store_id')->unique()->toArray();

            // Calculate delivery route
            $deliveryRoute = DeliveryZoneService::calculateDeliveryRoute(
                $order->shipping_latitude,
                $order->shipping_longitude,
                $storeIds,
                $order
            );
            if ($order->status === OrderStatusEnum::DELIVERED()) {
                return [
                    'success' => true,
                    'message' => __('labels.order_delivered_already'),
                    'data' => [
                        "delivery_boy" => [],
                        "route" => $deliveryRoute,
                        "order" => new OrderResource($order)
                    ]
                ];
            }

            return [
                "success" => true,
                "message" => __('labels.order_delivery_boy_location_retrieved_successfully'),
                "data" => [
                    "delivery_boy" => !empty($order->deliveryBoy) ? $this->deliveryBoyService->getLastLocation($order->delivery_boy_id) : [],
                    "route" => $deliveryRoute,
                    "order" => new OrderResource($order)
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    public static function checkUserReviewExistByOrderItemId($id): bool
    {
        return Review::where(['order_item_id' => $id, 'user_id' => auth()->id()])->exists();
    }

    public static function getUserReviewByOrderItemId($id): ReviewResource|null
    {
        $review = Review::where(['order_item_id' => $id, 'user_id' => auth()->id()])->get()->first();
        if ($review) {
            return new ReviewResource($review);
        }
        return null;
    }
}
