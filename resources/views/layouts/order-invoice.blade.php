@php use Illuminate\Support\Str; @endphp
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Invoice - {{ $systemSettings['appName'] }}</title>
    <link rel="shortcut icon" type="image/png" href="{{ $systemSettings['favicon'] ?? asset('favicon.png') }}"/>
    <link href="{{ asset('assets/theme/css/tabler.css') }}" rel="stylesheet"/>
    <link href="{{ hyperAsset('assets/theme/css/style.css') }}" rel="stylesheet"/>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f5f5f5;
        }

        .main-pd-wrapper {
            box-shadow: 0 0 10px #ddd;
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            margin: auto;
            width: 1000px;
        }

        h4 {
            margin: 0;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
<section class="main-pd-wrapper">
    <h4 class="text-center"><b>Order Invoice</b></h4>

    {{-- Header Section --}}
    <table style="margin-top: 10px;">
        <tr>
            <td>
                <img src="{{ $systemSettings['logo'] }}" alt="{{ $systemSettings['appName'] }} Logo"
                     style="height:50px;">
                <p class="m-0"><b>{{ $systemSettings['appName'] }}</b></p>
                @if(!empty($systemSettings['companyAddress']))
                    <p class="m-0">{!! nl2br(e($systemSettings['companyAddress'])) !!}</p>
                @endif
                <p class="m-0">Email: {{ $systemSettings['sellerSupportEmail'] ?? 'support@example.com' }}</p>
                <p class="m-0">Phone: {{ $systemSettings['sellerSupportNumber'] ?? '0000000000' }}</p>
            </td>
            <td>
                <b>Invoice #:</b> {{ $order['uuid'] }}<br>
                <b>Order Date:</b> {{ $order['created_at']->format('Y-m-d H:i:s') }}<br>
                <b>Payment Method:</b> {{ $order['payment_method'] ?? 'Cash on Delivery' }}<br>
                <b>Purchase Type:</b> {{ ucfirst($order['order_mode'] ?? 'regular') }}<br>
                <b>Payment Date:</b> {{ $order['created_at'] }}
            </td>
            <td>
                <b>Billing Information</b><br>
                {{ $order['billing_name'] }}<br>
                {{ $order['billing_address_1'] }},
                {{ $order['billing_landmark'] }},
                {{ $order['billing_city'] }},
                {{ $order['billing_state'] }},
                {{ $order['billing_country'] }} - {{ $order['billing_zip'] }}<br>
                @if(!empty($order['customer_gstin']))
                    <b>GSTIN:</b> {{ $order['customer_gstin'] }}<br>
                    @if(!empty($order['customer_gstin_legal_name']))
                        <b>Registered Name:</b> {{ $order['customer_gstin_legal_name'] }}<br>
                    @endif
                @endif
                Email: {{ $order['email'] }}<br>
                Phone: {{ $order['shipping_phone'] }}
            </td>
        </tr>
    </table>

    {{-- Order Items --}}
    <h4 style="margin-top: 20px;">Order Summary</h4>
    @foreach($sellerOrder as $vendor)
        <h5 class="mt-2">Sold by: {{ $vendor['seller']['stores'][0]['name'] ?? ($vendor['items'][0]['orderItem']['store']['name'] ?? 'N/A') }}
            (ID: {{ $vendor['seller']['id'] ?? 'N/A' }}
            )</h5>
        @php
            $store = $vendor['items'][0]['orderItem']['store'] ?? null;
            $itemOrder = $vendor['items'][0]['orderItem'] ?? null;
            $supplierTaxName = $itemOrder['supplier_tax_name'] ?? $store['tax_name'] ?? null;
            $supplierTaxNumber = $itemOrder['supplier_tax_number'] ?? $store['tax_number'] ?? null;
            $hasSupplierTaxNumber = filled($supplierTaxNumber)
                && !in_array(strtoupper(trim((string) $supplierTaxNumber)), ['0', '00000', 'N/A'], true);
        @endphp
        @if($store || $hasSupplierTaxNumber)
            <p class="m-0">
                @if($store)<b>Store:</b> {{ $store['name'] ?? 'N/A' }}@endif
                @if($hasSupplierTaxNumber)
                    @if($store) | @endif
                    <b>{{ $supplierTaxName ?: 'GSTIN' }}:</b> {{ $supplierTaxNumber }}
                @endif
            </p>
        @endif
        <table class="table-bordered">
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
            @foreach($vendor['items'] as $item)
{{--                @dd($item)--}}
                <tr>
                    <td>{{ $item['product']['title'] }}{{ !empty($item['orderItem']['is_gift']) ? ' (1 Rupee Gift)' : '' }}</td>
                    <td>{{ $item['orderItem']['hsn_code'] ?? '-' }}</td>
                    <td>{{ $item['product']['short_description'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td class="text-capitalize">{{ Str::replace('_',' ',$item['orderItem']['status']) }}</td>
                    <td>
                        @if(!empty($item['orderItem']['is_tax_inclusive']))
                            Included in price:<br>
                        @endif
                        @forelse($item['orderItem']['tax_breakdown'] ?? [] as $tax)
                            {{ !empty($tax['tax_group']) ? $tax['tax_group'] . ' - ' : '' }}{{ $tax['name'] }} ({{ number_format($tax['rate'], 2) }}%):
                            {{ $systemSettings['currencySymbol'] }}{{ number_format($tax['amount'], 2) }}<br>
                        @empty
                            {{ $systemSettings['currencySymbol'] }}{{ number_format($item['orderItem']['tax_total'] ?? (($item['orderItem']['tax_amount'] ?? 0) * $item['quantity']), 2) }}
                            ({{ $item['orderItem']['tax_percent'] ?? 0 }}%)
                        @endforelse
                    </td>
                    <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($item['orderItem']['subtotal'] - ($item['orderItem']['tax_total'] ?? (($item['orderItem']['tax_amount'] ?? 0) * $item['quantity'])), 2) }}</td>
                    <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($item['price'], 2) }}</td>
                    <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($item?->orderItem['subtotal'], 2) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <td colspan="8" class="text-right"><b>Subtotal:</b></td>
                <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($vendor['total_price'], 2) }}</td>
            </tr>
            </tfoot>
        </table>
    @endforeach

    {{-- Payment Summary --}}
    @php
        $productTaxTotal = $sellerOrder->sum(fn ($vendor) => $vendor->items->sum(fn ($item) => (float) ($item->orderItem?->tax_total ?? 0)));
    @endphp
    <table style="margin-top: 20px;">
        <tr>
            <td class="text-right"><b>Items Subtotal:</b></td>
            <td>{{ $systemSettings['currencySymbol'] }}{{ $order['subtotal'] }}</td>
        </tr>
        <tr>
            <td class="text-right"><b>Product taxes (included in items subtotal):</b></td>
            <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($productTaxTotal, 2) }}</td>
        </tr>
        <tr>
            <td class="text-right"><b>Shipping & Handling:</b></td>
            <td>{{ $systemSettings['currencySymbol'] }}{{ $order['delivery_charge'] }}</td>
        </tr>
        <tr>
            <td class="text-right"><b>Handling Charges:</b></td>
            <td>{{ $systemSettings['currencySymbol'] }}{{ $order['handling_charges'] }}</td>
        </tr>
        <tr>
            <td class="text-right"><b>Per Store Drop Off Fee:</b></td>
            <td>{{ $systemSettings['currencySymbol'] }}{{ $order['per_store_drop_off_fee'] }}</td>
        </tr>
        <tr>
            <td class="text-right"><b>Grand Total:</b></td>
            <td>
                <b>{{ $systemSettings['currencySymbol'] }}{{ number_format($order['subtotal'] + $order['delivery_charge'] + ($order['handling_charges'] ?? 0) + ($order['per_store_drop_off_fee'] ?? 0), 2) }}</b>
            </td>
        </tr>
        @if($order['wallet_balance'] > 0)
            <tr>
                <td class="text-right"><b>Wallet Used:</b></td>
                <td>- {{ $systemSettings['currencySymbol'] }}{{ $order['wallet_balance'] }}</td>
            </tr>
        @endif
        @if($order['promo_discount'] > 0)
            <tr>
                <td class="text-right"><b>Promo
                        Discount {{ (!empty($order['promoLine']) && $order['promoLine']['cashback_flag'] ? ' (' . "cashback" . ')' : '')}}
                        <span
                            class="text-uppercase">({{$order['promo_code']}}):</span></b></td>
                <td>- {{ $systemSettings['currencySymbol'] }}{{ $order['promo_discount'] }}</td>
            </tr>
        @endif
        <tr>
            <td class="text-right"><b>Total Payable:</b></td>
            <td><b>{{ $systemSettings['currencySymbol'] }}{{ $order['total_payable'] }}</b></td>
        </tr>
    </table>

    {{-- Authorized Signatory --}}
    <div style="margin-top: 40px; width: 100%;">
        <div style="float: right; text-align: center; width: 260px;">
            @if(!empty($systemSettings['adminSignature']))
                <img src="{{ $systemSettings['adminSignature'] }}" alt="Authorized Signatory"
                     style="max-height: 80px; max-width: 100%; object-fit: contain;"/>
            @else
                <div style="height: 60px;"></div>
            @endif
            <div style="border-top: 1px solid #000; margin-top: 10px; padding-top: 5px; font-weight: bold;">
                Authorized Signatory
            </div>
        </div>
        <div style="clear: both;"></div>
    </div>

    {{-- Footer --}}
    <p style="margin-top: 20px; text-align:center;">
        Thank you for shopping with {{ $systemSettings['appName'] }}!<br>
        @if($systemSettings['sellerSupportEmail'])
            Need help? Contact us at {{ $systemSettings['sellerSupportEmail'] }}.
        @endif
    </p>
    <p class="text-center">{{ $systemSettings['copyrightDetails'] }}</p>

    {{-- Store-wise Invoices --}}
    <style>
        .page-break { page-break-before: always; }
    </style>

    <div class="page-break"></div>
    <h4 class="text-center"><b>Store-wise Invoices</b></h4>

    @foreach($sellerOrder as $vendor)
        @php
            $store = $vendor['items'][0]['orderItem']['store'] ?? null;
            $itemOrder = $vendor['items'][0]['orderItem'] ?? null;
            $supplierTaxName = $itemOrder['supplier_tax_name'] ?? $store['tax_name'] ?? null;
            $supplierTaxNumber = $itemOrder['supplier_tax_number'] ?? $store['tax_number'] ?? null;
            $hasSupplierTaxNumber = filled($supplierTaxNumber)
                && !in_array(strtoupper(trim((string) $supplierTaxNumber)), ['0', '00000', 'N/A'], true);
        @endphp
        <div style="margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px;">
            <h4 class="text-center"><b>{{ $hasSupplierTaxNumber ? 'Tax Invoice' : 'Invoice' }}</b></h4>
            <table style="margin-top: 10px;">
                <tr>
                    <td>
                        <p class="m-0"><b>Sold by:</b>
                            {{ $store['name'] ?? ($vendor['seller']['stores'][0]['name'] ?? 'N/A') }}
                        </p>
                        @if($store)
                            <p class="m-0">
                                {{ $store['address'] ?? '' }}@if(!empty($store['address']) && !empty($store['landmark'])) , @endif{{ $store['landmark'] ?? '' }}
                            </p>
                            <p class="m-0">
                                {{ $store['city'] ?? '' }}@if(!empty($store['city']) && !empty($store['state'])) , @endif{{ $store['state'] ?? '' }}
                            </p>
                            <p class="m-0">
                                {{ $store['country'] ?? '' }}@if(!empty($store['zipcode'])) - {{ $store['zipcode'] }} @endif
                            </p>
                        @endif
                    </td>
                    <td>
                        <b>Invoice #:</b> {{ $order['uuid'] }}<br>
                        <b>Order Date:</b> {{ $order['created_at']->format('Y-m-d H:i:s') }}<br>
                        <b>Payment Method:</b> {{ $order['payment_method'] ?? 'Cash on Delivery' }}<br>
                        <b>Payment Date:</b> {{ $order['created_at'] }}
                    </td>
                    <td>
                        <b>Billing Information</b><br>
                        {{ $order['billing_name'] }}<br>
                        {{ $order['billing_address_1'] }},
                        {{ $order['billing_landmark'] }},
                        {{ $order['billing_city'] }},
                        {{ $order['billing_state'] }},
                        {{ $order['billing_country'] }} - {{ $order['billing_zip'] }}<br>
                        @if(!empty($order['customer_gstin']))
                            <b>GSTIN:</b> {{ $order['customer_gstin'] }}<br>
                            @if(!empty($order['customer_gstin_legal_name']))
                                <b>Registered Name:</b> {{ $order['customer_gstin_legal_name'] }}<br>
                            @endif
                        @endif
                        Email: {{ $order['email'] }}<br>
                        Phone: {{ $order['shipping_phone'] }}
                    </td>
                </tr>
            </table>

            <h5 class="mt-2">Sold by: {{ $store['name'] ?? ($vendor['seller']['stores'][0]['name'] ?? 'N/A') }}
                @if(isset($vendor['seller']['id'])) (Seller ID: {{ $vendor['seller']['id'] }}) @endif
            </h5>
            @if($store || $hasSupplierTaxNumber)
                <p class="m-0">
                    @if($store)<b>Store:</b> {{ $store['name'] ?? 'N/A' }}@endif
                    @if($hasSupplierTaxNumber)
                        @if($store) | @endif
                        <b>{{ $supplierTaxName ?: 'GSTIN' }}:</b> {{ $supplierTaxNumber }}
                    @endif
                </p>
            @endif

            <table class="table-bordered" style="margin-top: 10px;">
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
                @foreach($vendor['items'] as $item)
                    <tr>
                        <td>{{ $item['product']['title'] }}</td>
                        <td>{{ $item['orderItem']['hsn_code'] ?? '-' }}</td>
                        <td>{{ $item['product']['short_description'] }}</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td class="text-capitalize">{{ Str::replace('_',' ', $item['orderItem']['status']) }}</td>
                        <td>
                            @if(!empty($item['orderItem']['is_tax_inclusive']))
                                Included in price:<br>
                            @endif
                            @forelse($item['orderItem']['tax_breakdown'] ?? [] as $tax)
                                {{ !empty($tax['tax_group']) ? $tax['tax_group'] . ' - ' : '' }}{{ $tax['name'] }} ({{ number_format($tax['rate'], 2) }}%):
                                {{ $systemSettings['currencySymbol'] }}{{ number_format($tax['amount'], 2) }}<br>
                            @empty
                                {{ $systemSettings['currencySymbol'] }}{{ number_format($item['orderItem']['tax_total'] ?? (($item['orderItem']['tax_amount'] ?? 0) * $item['quantity']), 2) }}
                                ({{ $item['orderItem']['tax_percent'] ?? 0 }}%)
                            @endforelse
                        </td>
                        <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($item['orderItem']['subtotal'] - ($item['orderItem']['tax_total'] ?? (($item['orderItem']['tax_amount'] ?? 0) * $item['quantity'])), 2) }}</td>
                        <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($item['price'], 2) }}</td>
                        <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($item?->orderItem['subtotal'], 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="8" class="text-right"><b>Store Subtotal:</b></td>
                    <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($vendor['total_price'], 2) }}</td>
                </tr>
                </tfoot>
            </table>

            {{-- Authorized Signatory (Seller) --}}
            <div style="margin-top: 30px; width: 100%;">
                <div style="float: right; text-align: center; width: 260px;">
                    @if(!empty($vendor['seller']['authorized_signature']))
                        <img src="{{ $vendor['seller']['authorized_signature'] }}" alt="Authorized Signatory"
                             style="max-height: 80px; max-width: 100%; object-fit: contain;"/>
                    @else
                        <div style="height: 60px;"></div>
                    @endif
                    <div style="border-top: 1px solid #000; margin-top: 10px; padding-top: 5px; font-weight: bold;">
                        Authorized Signatory
                    </div>
                </div>
                <div style="clear: both;"></div>
            </div>
        </div>
        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</section>
</body>
<script>
    window.onload = function () {
        window.print();
    };
</script>
</html>
