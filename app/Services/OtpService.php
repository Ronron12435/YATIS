<?php

namespace App\Services;

use App\Mail\OtpVerificationMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function generateAndSendOtp(User $user): bool
    {
        try {
            \Log::info('=== OTP SEND DEBUG START ===');
            \Log::info('User Email: ' . $user->email);
            
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            \Log::info('Generated OTP: ' . $otp);
            
            $user->update([
                'otp_code' => $otp,
                'otp_expires_at' => Carbon::now()->addSeconds(60),
            ]);
            \Log::info('OTP saved to database');
            \Log::info("OTP for {$user->email}: {$otp}");

            \Log::info('Sending OTP via Laravel Mail facade...');
            \Log::info('Email To: ' . $user->email);
            \Log::info('Email From: ' . config('mail.from.address'));
            
            Mail::send(new OtpVerificationMail($user, $otp));
            
            \Log::info('Email sent successfully via Laravel Mail');
            \Log::info('=== OTP SEND DEBUG END (SUCCESS) ===');
            return true;
        } catch (\Exception $e) {
            \Log::error('=== OTP SEND DEBUG END (EXCEPTION) ===');
            \Log::error('OTP Send Error: ' . $e->getMessage());
            \Log::error('Exception Class: ' . get_class($e));
            \Log::error('Exception Code: ' . $e->getCode());
            \Log::error('Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    public function verifyOtp(User $user, string $otp): bool
    {
        if (!$user->otp_code || $user->otp_code !== $otp) {
            return false;
        }

        if (Carbon::now()->isAfter($user->otp_expires_at)) {
            return false;
        }

        // Clear OTP fields
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        return true;
    }

    public function resendOtp(User $user): bool
    {
        if ($user->email_verified) {
            return false;
        }

        return $this->generateAndSendOtp($user);
    }
}
