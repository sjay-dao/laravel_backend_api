<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\TestEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpEmail;
use App\Models\EmailOtp;

class EmailController extends Controller
{
    public function sendTestEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'message' => ['nullable', 'string'],
        ]);

        $messageText = $validated['message'] ?? 'This is a test email from Laravel API.';

        Mail::to($validated['email'])->send(new TestEmail($messageText));

        return response()->json([
            'message' => 'Test email sent successfully.',
            'sent_to' => $validated['email'],
        ]);
    }

    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $otp = rand(100000, 999999);

        EmailOtp::where('email', $validated['email'])
            ->where('is_used', false)
            ->update(['is_used' => true]);

        EmailOtp::create([
            'email' => $validated['email'],
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5),
            'is_used' => false,
        ]);

        Mail::to($validated['email'])->send(new OtpEmail($otp));

        return response()->json([
            'message' => 'OTP sent successfully.',
            'email' => $validated['email'],
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $otpRecord = EmailOtp::where('email', $validated['email'])
            ->where('otp', $validated['otp'])
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otpRecord) {
            return response()->json([
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        $otpRecord->update([
            'is_used' => true,
        ]);

        return response()->json([
            'message' => 'OTP verified successfully.',
        ]);
    }
}