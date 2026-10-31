@php use Illuminate\Support\Str; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order invoices {{ $startDate->format('Y-m-d') }} to {{ $endDate->format('Y-m-d') }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #414b5d; }
        h1, h2, h3, h4, h5, p { margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f5f5f5; }
        .invoice { page-break-after: always; }
        .invoice:last-child { page-break-after: auto; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-capitalize { text-transform: capitalize; }
        .main-header { margin-top: 10px; }
        .main-header td { width: 33.333%; }
        .main-summary { margin-top: 8px; }
        .payment-summary { margin-top: 20px; }
        .signatory { margin-top: 35px; width: 100%; }
        .signatory-block { float: right; text-align: center; width: 260px; }
        .signatory-block img { max-height: 80px; max-width: 100%; object-fit: contain; }
        .signatory-label { border-top: 1px solid #000; margin-top: 10px; padding-top: 5px; font-weight: bold; }
        .clear { clear: both; }
        .store-invoice { margin: 16px 0; padding: 12px; border: 1px solid #ddd; border-radius: 8px; page-break-inside: avoid; }
        .store-header td:first-child { width: 30%; }
        .store-header td:nth-child(2) { width: 28%; }
        .store-header td:last-child { width: 42%; }
        .store-items { margin-top: 10px; }
        .tax-lines { white-space: normal; }
        .muted { color: #555; }
        .no-invoices { text-align: center; padding-top: 100px; }
    </style>
</head>
<body>
@if($sellerOrders->isEmpty())
    <p class="no-invoices">
        No order invoices matched the selected date range{{ $status ? ' and status (' . Str::headline($status) . ')' : '' }}.
    </p>
@else
    @foreach($sellerOrders->groupBy('order_id') as $orderSellerOrders)
        @php
            $order = $orderSellerOrders->first()->order;
            $productTaxTotal = $orderSellerOrders->sum(
                fn ($vendor) => $vendor->items->sum(fn ($item) => (float) ($item->orderItem?->tax_total ?? 0))
            );
            $itemsSubtotal = $isAdmin
                ? (float) $order->subtotal
                : (float) $orderSellerOrders->sum('total_price');
        @endphp
        <section class="invoice">
            <h4 class="text-center"><strong>Order Invoice</strong></h4>

            <table class="main-header">
                <tr>
                    <td>
                        @if(!empty($systemSettings['logo']))
                            <img src="{{ $systemSettings['logo'] }}" alt="{{ $systemSettings['appName'] }} Logo" style="height: 50px; max-width: 100%;">
                        @endif
                        <p><strong>{{ $systemSettings['appName'] }}</strong></p>
                        @if(!empty($systemSettings['companyAddress']))
                            <p>{!! nl2br(e($systemSettings['companyAddress'])) !!}</p>
                        @endif
                        <p>Email: {{ $systemSettings['sellerSupportEmail'] ?? 'support@example.com' }}</p>
                        <p>Phone: {{ $systemSettings['sellerSupportNumber'] ?? '0000000000' }}</p>
                    </td>
                    <td>
                        <strong>Invoice #:</strong> {{ $order->uuid }}<br>
                        <strong>Order Date:</strong> {{ $order->created_at?->format('Y-m-d H:i:s') }}<br>
                        <strong>Payment Method:</strong> {{ $order->payment_method ?? 'Cash on Delivery' }}<br>
                        <strong>Purchase Type:</strong> {{ Str::headline($order->order_mode ?? 'regular') }}<br>
                        <strong>Payment Date:</strong> {{ $order->created_at?->format('Y-m-d H:i:s') }}
                    </td>
                    <td>
                        <strong>Billing Information</strong><br>
                        {{ $order->billing_name }}<br>
                        {{ $order->billing_address_1 }}
                        @if($order->billing_landmark)
                            , {{ $order->billing_landmark }}
                        @endif,
                        {{ $order->billing_city }}, {{ $order->billing_state }},
                        {{ $order->billing_country }} - {{ $order->billing_zip }}<br>
                        @if($order->customer_gstin)
                            <strong>GSTIN:</strong> {{ $order->customer_gstin }}<br>
                            @if($order->customer_gstin_legal_name)
                                <strong>Registered Name:</strong> {{ $order->customer_gstin_legal_name }}<br>
                            @endif
                        @endif
                        Email: {{ $order->email }}<br>
                        Phone: {{ $order->shipping_phone }}
                    </td>
                </tr>
            </table>

            <h4 style="margin-top: 20px;">Order Summary</h4>
            @foreach($orderSellerOrders as $vendor)
                @php
                    $firstItem = $vendor->items->first();
                    $store = $firstItem?->orderItem?->store;
                    $supplierTaxName = $firstItem?->orderItem?->supplier_tax_name ?? $store?->tax_name;
                    $supplierTaxNumber = $firstItem?->orderItem?->supplier_tax_number ?? $store?->tax_number;
                @endphp
                <h5 style="margin-top: 10px;">
                    Sold by: {{ $store?->name ?? $vendor->seller?->name ?? 'N/A' }}
                    (ID: {{ $vendor->seller?->id ?? 'N/A' }})
                </h5>
                @if($store || filled($supplierTaxNumber))
                    <p>
                        @if($store)
                            <strong>Store:</strong> {{ $store->name }}
                        @endif
                        @if(filled($supplierTaxNumber))
                            @if($store)
                                |
                            @endif
                            <strong>{{ $supplierTaxName ?: 'GSTIN' }}:</strong> {{ $supplierTaxNumber }}
                        @endif
                    </p>
                @endif
                <table class="main-summary">
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th>HSN/SAC</th>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th>Tax details</th>
                        <th>Taxable value</th>
                        <th>Unit price (excl. tax)</th>
                        <th>Total (incl. tax)</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($vendor->items as $sellerItem)
                        @php
                            $item = $sellerItem->orderItem;
                            $taxTotal = (float) ($item?->tax_total ?? 0);
                            $lineTotal = (float) ($item?->subtotal ?? 0);
                        @endphp
                        <tr>
                            <td>
                                {{ $sellerItem->product?->title ?? $item?->title ?? 'Item' }}
                                @if($item?->is_gift)
                                    (1 Rupee Gift)
                                @endif
                            </td>
                            <td>{{ $item?->hsn_code ?: '-' }}</td>
                            <td>{{ $sellerItem->product?->short_description ?? '-' }}</td>
                            <td>{{ $item?->quantity ?? $sellerItem->quantity }}</td>
                            <td class="text-capitalize">{{ Str::replace('_', ' ', $item?->status ?? '') }}</td>
                            <td class="tax-lines">
                                @if($item?->is_tax_inclusive)
                                    Included in price:<br>
                                @endif
                                @forelse($item?->tax_breakdown ?? [] as $tax)
                                    {{ !empty($tax['tax_group']) ? $tax['tax_group'] . ' - ' : '' }}{{ $tax['name'] }}
                                    ({{ number_format((float) $tax['rate'], 2) }}%):
                                    {{ $systemSettings['currencySymbol'] }}{{ number_format((float) $tax['amount'], 2) }}<br>
                                @empty
                                    {{ $systemSettings['currencySymbol'] }}{{ number_format($taxTotal, 2) }}
                                    ({{ $item?->tax_percent ?? 0 }}%)
                                @endforelse
                            </td>
                            <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($lineTotal - $taxTotal, 2) }}</td>
                            <td>{{ $systemSettings['currencySymbol'] }}{{ number_format((float) $sellerItem->price, 2) }}</td>
                            <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($lineTotal, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="8" class="text-right"><strong>Subtotal:</strong></td>
                        <td>{{ $systemSettings['currencySymbol'] }}{{ number_format((float) $vendor->total_price, 2) }}</td>
                    </tr>
                    </tfoot>
                </table>
            @endforeach

            <table class="payment-summary">
                <tr>
                    <td class="text-right"><strong>Items Subtotal:</strong></td>
                    <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($itemsSubtotal, 2) }}</td>
                </tr>
                <tr>
                    <td class="text-right"><strong>Product taxes (included in items subtotal):</strong></td>
                    <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($productTaxTotal, 2) }}</td>
                </tr>
                @if($isAdmin)
                <tr>
                    <td class="text-right"><strong>Shipping &amp; Handling:</strong></td>
                    <td>{{ $systemSettings['currencySymbol'] }}{{ number_format((float) $order->delivery_charge, 2) }}</td>
                </tr>
                <tr>
                    <td class="text-right"><strong>Handling Charges:</strong></td>
                    <td>{{ $systemSettings['currencySymbol'] }}{{ number_format((float) ($order->handling_charges ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td class="text-right"><strong>Per Store Drop Off Fee:</strong></td>
                    <td>{{ $systemSettings['currencySymbol'] }}{{ number_format((float) ($order->per_store_drop_off_fee ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td class="text-right"><strong>Grand Total:</strong></td>
                    <td><strong>{{ $systemSettings['currencySymbol'] }}{{ number_format((float) $order->subtotal + (float) $order->delivery_charge + (float) ($order->handling_charges ?? 0) + (float) ($order->per_store_drop_off_fee ?? 0), 2) }}</strong></td>
                </tr>
                @if((float) $order->wallet_balance > 0)
                    <tr>
                        <td class="text-right"><strong>Wallet Used:</strong></td>
                        <td>- {{ $systemSettings['currencySymbol'] }}{{ number_format((float) $order->wallet_balance, 2) }}</td>
                    </tr>
                @endif
                @if((float) $order->promo_discount > 0)
                    <tr>
                        <td class="text-right">
                            <strong>
                                Promo Discount
                                @if($order->promoLine?->cashback_flag)
                                    (cashback)
                                @endif
                                ({{ $order->promo_code }}):
                            </strong>
                        </td>
                        <td>- {{ $systemSettings['currencySymbol'] }}{{ number_format((float) $order->promo_discount, 2) }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="text-right"><strong>Total Payable:</strong></td>
                    <td><strong>{{ $systemSettings['currencySymbol'] }}{{ number_format((float) $order->total_payable, 2) }}</strong></td>
                </tr>
                @endif
            </table>

            <div class="signatory">
                <div class="signatory-block">
                    @if(!empty($systemSettings['adminSignature']))
                        <img src="{{ $systemSettings['adminSignature'] }}" alt="Authorized Signatory">
                    @else
                        <div style="height: 60px;"></div>
                    @endif
                    <div class="signatory-label">Authorized Signatory</div>
                </div>
                <div class="clear"></div>
            </div>
            <p class="text-center" style="margin-top: 20px;">
                Thank you for shopping with {{ $systemSettings['appName'] }}!<br>
                @if(!empty($systemSettings['sellerSupportEmail']))
                    Need help? Contact us at {{ $systemSettings['sellerSupportEmail'] }}.
                @endif
            </p>
            <p class="text-center">{{ $systemSettings['copyrightDetails'] }}</p>

            <div style="page-break-before: always;"></div>
            <h4 class="text-center"><strong>Store-wise Invoices</strong></h4>
            @foreach($orderSellerOrders as $vendor)
                @php
                    $firstItem = $vendor->items->first();
                    $store = $firstItem?->orderItem?->store;
                    $supplierTaxName = $firstItem?->orderItem?->supplier_tax_name ?? $store?->tax_name;
                    $supplierTaxNumber = $firstItem?->orderItem?->supplier_tax_number ?? $store?->tax_number;
                    $hasSupplierTaxNumber = filled($supplierTaxNumber)
                        && !in_array(strtoupper(trim((string) $supplierTaxNumber)), ['0', '00000', 'N/A'], true);
                @endphp
                <section class="store-invoice">
                    <h4 class="text-center"><strong>{{ $hasSupplierTaxNumber ? 'Tax Invoice' : 'Invoice' }}</strong></h4>
                    <table class="store-header" style="margin-top: 10px;">
                        <tr>
                            <td>
                                <strong>Sold by:</strong> {{ $store?->name ?? $vendor->seller?->name ?? 'N/A' }}<br>
                                @if($store)
                                    {{ $store->address }}
                                    @if($store->landmark)
                                        , {{ $store->landmark }}
                                    @endif
                                    <br>
                                    {{ $store->city }}
                                    @if($store->state)
                                        , {{ $store->state }}
                                    @endif
                                    <br>
                                    {{ $store->country }}
                                    @if($store->zipcode)
                                        - {{ $store->zipcode }}
                                    @endif
                                @endif
                            </td>
                            <td>
                                <strong>Invoice #:</strong> {{ $order->uuid }}<br>
                                <strong>Order Date:</strong> {{ $order->created_at?->format('Y-m-d H:i:s') }}<br>
                                <strong>Payment Method:</strong> {{ $order->payment_method ?? 'Cash on Delivery' }}<br>
                                <strong>Payment Date:</strong> {{ $order->created_at?->format('Y-m-d H:i:s') }}
                            </td>
                            <td>
                                <strong>Billing Information</strong><br>
                                {{ $order->billing_name }}<br>
                                {{ $order->billing_address_1 }}, {{ $order->billing_landmark }},
                                {{ $order->billing_city }}, {{ $order->billing_state }},
                                {{ $order->billing_country }} - {{ $order->billing_zip }}<br>
                                @if($order->customer_gstin)
                                    <strong>GSTIN:</strong> {{ $order->customer_gstin }}<br>
                                    @if($order->customer_gstin_legal_name)
                                        <strong>Registered Name:</strong> {{ $order->customer_gstin_legal_name }}<br>
                                    @endif
                                @endif
                                Email: {{ $order->email }}<br>
                                Phone: {{ $order->shipping_phone }}
                            </td>
                        </tr>
                    </table>
                    <h5 style="margin-top: 10px;">
                        Sold by: {{ $store?->name ?? $vendor->seller?->name ?? 'N/A' }}
                        @if($vendor->seller?->id)
                            (Seller ID: {{ $vendor->seller->id }})
                        @endif
                    </h5>
                    @if($store || $hasSupplierTaxNumber)
                        <p>
                            @if($store)
                                <strong>Store:</strong> {{ $store->name }}
                            @endif
                            @if($hasSupplierTaxNumber)
                                @if($store)
                                    |
                                @endif
                                <strong>{{ $supplierTaxName ?: 'GSTIN' }}:</strong> {{ $supplierTaxNumber }}
                            @endif
                        </p>
                    @endif
                    <table class="store-items">
                        <thead>
                        <tr>
                            <th>Item</th>
                            <th>HSN/SAC</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Tax details</th>
                            <th>Taxable value</th>
                            <th>Unit price (excl. tax)</th>
                            <th>Total (incl. tax)</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($vendor->items as $sellerItem)
                            @php
                                $item = $sellerItem->orderItem;
                                $taxTotal = (float) ($item?->tax_total ?? 0);
                                $lineTotal = (float) ($item?->subtotal ?? 0);
                            @endphp
                            <tr>
                                <td>{{ $sellerItem->product?->title ?? $item?->title ?? 'Item' }}</td>
                                <td>{{ $item?->hsn_code ?: '-' }}</td>
                                <td>{{ $sellerItem->product?->short_description ?? '-' }}</td>
                                <td>{{ $item?->quantity ?? $sellerItem->quantity }}</td>
                                <td class="text-capitalize">{{ Str::replace('_', ' ', $item?->status ?? '') }}</td>
                                <td class="tax-lines">
                                    @if($item?->is_tax_inclusive)
                                        Included in price:<br>
                                    @endif
                                    @forelse($item?->tax_breakdown ?? [] as $tax)
                                        {{ !empty($tax['tax_group']) ? $tax['tax_group'] . ' - ' : '' }}{{ $tax['name'] }}
                                        ({{ number_format((float) $tax['rate'], 2) }}%):
                                        {{ $systemSettings['currencySymbol'] }}{{ number_format((float) $tax['amount'], 2) }}<br>
                                    @empty
                                        {{ $systemSettings['currencySymbol'] }}{{ number_format($taxTotal, 2) }}
                                        ({{ $item?->tax_percent ?? 0 }}%)
                                    @endforelse
                                </td>
                                <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($lineTotal - $taxTotal, 2) }}</td>
                                <td>{{ $systemSettings['currencySymbol'] }}{{ number_format((float) $sellerItem->price, 2) }}</td>
                                <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($lineTotal, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="8" class="text-right"><strong>Store Subtotal:</strong></td>
                            <td>{{ $systemSettings['currencySymbol'] }}{{ number_format((float) $vendor->total_price, 2) }}</td>
                        </tr>
                        </tfoot>
                    </table>
                    <div class="signatory" style="margin-top: 30px;">
                        <div class="signatory-block">
                            @if(!empty($vendor->seller?->authorized_signature))
                                <img src="{{ $vendor->seller->authorized_signature }}" alt="Authorized Signatory">
                            @else
                                <div style="height: 60px;"></div>
                            @endif
                            <div class="signatory-label">Authorized Signatory</div>
                        </div>
                        <div class="clear"></div>
                    </div>
                </section>
                @if(!$loop->last)
                    <div style="page-break-before: always;"></div>
                @endif
            @endforeach
        </section>
    @endforeach
@endif
</body>
</html>
