<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Console\Command;

class TestOtpEmail extends Command
{
    protected $signature = 'test:otp-email {email}';
    protected $description = 'Test OTP email sending';

    public function handle(OtpService $otpService)
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found");
            return 1;
        }

        if ($otpService->generateAndSendOtp($user)) {
            $this->info("✓ OTP sent successfully to {$email}");
            $this->info("OTP Code: {$user->otp_code}");
            $this->info("Expires at: {$user->otp_expires_at}");
            return 0;
        } else {
            $this->error("✗ Failed to send OTP");
            return 1;
        }
    }
}
