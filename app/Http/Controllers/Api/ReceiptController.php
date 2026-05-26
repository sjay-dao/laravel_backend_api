<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function officialReceipt(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $order->load(['items.product', 'user']);

        $pdf = Pdf::loadView('pdf.official-receipt', [
            'order' => $order,
        ]);

        return $pdf->stream('official-receipt-' . $order->order_number . '.pdf');
    }
}