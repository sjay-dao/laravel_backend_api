<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\RmsTagNotificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class RmsNotificationController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'records' => 'required|array',
        ]);

        Mail::to($validated['email'])
            ->send(new RmsTagNotificationMail($validated['records']));

        return response()->json([
            'message' => 'RMS notification email sent successfully.',
        ]);
    }
}