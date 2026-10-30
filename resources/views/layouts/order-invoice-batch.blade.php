@php use Illuminate\Support\Str; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order invoices {{ $startDate->format('Y-m-d') }} to {{ $endDate->format('Y-m-d') }}</title>
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #222; }
        h1, h2, h3, p { margin: 0 0 6px; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 12px; }
        h2 { font-size: 15px; }
        h3 { font-size: 12px; margin-top: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        th, td { border: 1px solid #bbb; padding: 5px; text-align: left; vertical-align: top; }
        th { background: #f1f1f1; }
        .info { width: 100%; }
        .info td { width: 50%; }
        .right { text-align: right; }
        .muted { color: #555; }
        .invoice { page-break-after: always; }
        .invoice:last-child { page-break-after: auto; }
        .tax-lines { white-space: normal; }
    </style>
</head>
<body>
<h1>Order invoices · {{ $startDate->format('d M Y') }} – {{ $endDate->format('d M Y') }}</h1>

@if($sellerOrders->isEmpty())
    <p>No order invoices matched the selected date range{{ $status ? ' and status (' . Str::headline($status) . ')' : '' }}.</p>
@else
@foreach($sellerOrders as $sellerOrder)
    @php
        $order = $sellerOrder->order;
        $firstSellerItem = $sellerOrder->items->first();
        $store = $firstSellerItem?->orderItem?->store;
        $supplierTaxName = $firstSellerItem?->orderItem?->supplier_tax_name;
        $supplierTaxNumber = $firstSellerItem?->orderItem?->supplier_tax_number;
        $taxTotal = $sellerOrder->items->sum(fn ($sellerItem) => (float) ($sellerItem->orderItem?->tax_total ?? 0));
    @endphp
    <section class="invoice">
        <h2>{{ $isAdmin ? 'Store tax invoice' : 'Order invoice' }}</h2>
        <table class="info">
            <tr>
                <td>
                    <strong>Seller / store</strong><br>
                    {{ $store?->name ?? $sellerOrder->seller?->name ?? 'N/A' }}<br>
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
                        {{ $store->country }} {{ $store->zipcode }}
                    @endif
                    @if(filled($supplierTaxNumber))
                        <br>{{ $supplierTaxName ?: 'Tax registration number' }}: {{ $supplierTaxNumber }}
                    @endif
                </td>
                <td>
                    <strong>Order</strong><br>
                    Invoice #: {{ $order->order_number ?? $order->uuid }}<br>
                    Order date: {{ $order->created_at?->format('Y-m-d H:i:s') }}<br>
                    Status: {{ Str::headline($order->status ?? '') }}<br>
                    Payment: {{ Str::headline($order->payment_method ?? '') }}<br>
                    Purchase type: {{ Str::headline($order->order_mode ?? 'regular') }}
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Billing information</strong><br>
                    {{ $order->billing_name }}<br>
                    {{ $order->billing_address_1 }}
                    @if($order->billing_address_2)
                        , {{ $order->billing_address_2 }}
                    @endif
                    @if($order->billing_landmark)
                        , {{ $order->billing_landmark }}
                    @endif
                    <br>
                    {{ $order->billing_city }}, {{ $order->billing_state }} {{ $order->billing_zip }}<br>
                    {{ $order->billing_country }}
                    @if($order->customer_gstin)
                        <br>Buyer GSTIN: {{ $order->customer_gstin }}
                        @if($order->customer_gstin_legal_name)
                            <br>Registered name: {{ $order->customer_gstin_legal_name }}
                        @endif
                    @endif
                </td>
                <td>
                    <strong>Shipping information</strong><br>
                    {{ $order->shipping_name }}<br>
                    {{ $order->shipping_address_1 }}
                    @if($order->shipping_address_2)
                        , {{ $order->shipping_address_2 }}
                    @endif
                    @if($order->shipping_landmark)
                        , {{ $order->shipping_landmark }}
                    @endif
                    <br>
                    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}<br>
                    {{ $order->shipping_country }}<br>
                    Phone: {{ $order->shipping_phone }}
                </td>
            </tr>
        </table>

        <table>
            <thead>
            <tr>
                <th>Item</th>
                <th>HSN/SAC</th>
                <th>Qty</th>
                <th>Tax details</th>
                <th>Taxable value</th>
                <th>Unit price incl. tax</th>
                <th>Line total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($sellerOrder->items as $sellerItem)
                @php
                    $item = $sellerItem->orderItem;
                    $itemTaxTotal = (float) ($item?->tax_total ?? 0);
                    $lineTotal = (float) ($item?->subtotal ?? 0);
                    $quantity = max((float) ($item?->quantity ?? $sellerItem->quantity), 1);
                @endphp
                <tr>
                    <td>
                        {{ $sellerItem->product?->title ?? $item?->title ?? 'Item' }}
                        @if($sellerItem->variant?->title)
                            <br><span class="muted">{{ $sellerItem->variant->title }}</span>
                        @endif
                        @if($item?->is_gift)
                            <br>Gift item
                        @endif
                    </td>
                    <td>{{ $item?->hsn_code ?: '—' }}</td>
                    <td>{{ $item?->quantity ?? $sellerItem->quantity }}</td>
                    <td class="tax-lines">
                        @if($item?->is_tax_inclusive)
                            Included in price<br>
                        @endif
                        @forelse($item?->tax_breakdown ?? [] as $tax)
                            {{ $tax['tax_group'] ? $tax['tax_group'] . ' – ' : '' }}{{ $tax['name'] }}
                            ({{ number_format((float) $tax['rate'], 2) }}%):
                            {{ $systemSettings['currencySymbol'] }}{{ number_format((float) $tax['amount'], 2) }}<br>
                        @empty
                            @if($itemTaxTotal > 0)
                                Tax {{ number_format((float) ($item?->tax_percent ?? 0), 2) }}%:
                                {{ $systemSettings['currencySymbol'] }}{{ number_format($itemTaxTotal, 2) }}
                            @else
                                —
                            @endif
                        @endforelse
                    </td>
                    <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($lineTotal - $itemTaxTotal, 2) }}</td>
                    <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($lineTotal / $quantity, 2) }}</td>
                    <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($lineTotal, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <th colspan="4" class="right">Product tax total (included in line totals)</th>
                <th colspan="2">{{ $systemSettings['currencySymbol'] }}{{ number_format($taxTotal, 2) }}</th>
                <th>{{ $systemSettings['currencySymbol'] }}{{ number_format((float) $sellerOrder->total_price, 2) }}</th>
            </tr>
            </tfoot>
        </table>
        @if($isAdmin)
            <p class="muted">This is the seller/store portion of the order invoice.</p>
        @endif
    </section>
@endforeach
@endif
</body>
</html>
