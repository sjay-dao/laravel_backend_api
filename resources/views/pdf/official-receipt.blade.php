<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Official Receipt</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
        }

        .section {
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #333;
            padding: 6px;
        }

        th {
            background: #eee;
        }

        .right {
            text-align: right;
        }

        .total {
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">OFFICIAL RECEIPT</div>
        <div>Motorcycle Inventory System</div>
    </div>

    <div class="section">
        <strong>Order No:</strong> {{ $order->order_number }}<br>
        <strong>Supplier:</strong> {{ $order->supplier_name }}<br>
        <strong>Order Date:</strong> {{ $order->order_date }}<br>
        <strong>Expected Delivery:</strong> {{ $order->expected_delivery_date }}<br>
        <strong>Status:</strong> {{ strtoupper($order->status) }}<br>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th class="right">Qty</th>
                <th class="right">Unit Cost</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">₱{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="right">₱{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br>

    <div class="right total">
        Total Amount: ₱{{ number_format($order->total_amount, 2) }}
    </div>

    <br><br>

    <div class="section">
        <strong>Remarks:</strong><br>
        {{ $order->remarks }}
    </div>

</body>
</html>