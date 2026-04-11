<?php

namespace App\Http\Controllers;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Services\AuthService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private OtpService $otpService
    ) {}

    public function register(Request $request)
    {
        \Log::info('=== REGISTRATION START ===');
        \Log::info('Request Data: ' . json_encode($request->all()));
        
        try {
            $validated = $request->validate([
                'username'   => 'required|string|max:255|unique:users',
                'first_name' => 'required|string|max:255',
                'last_name'  => 'required|string|max:255',
                'email'      => 'required|email|unique:users|regex:/@.*\.com$/',
                'password'   => 'required|min:8|confirmed',
            ]);

            \Log::info('Validation passed');

            // ONLY NOW create the account in database
            $dto = new RegisterDTO(
                username: $validated['username'],
                firstName: $validated['first_name'],
                lastName: $validated['last_name'],
                email: $validated['email'],
                password: $validated['password'],
                role: 'user',
            );

            \Log::info('Creating account for: ' . $validated['email']);
            $response = $this->authService->register($dto);

            if (!$response->success) {
                \Log::error('Account creation failed: ' . $response->message);
                return back()->withErrors(['email' => $response->message])->withInput();
            }

            $user = $response->data;
            \Log::info('Account created successfully. User ID: ' . $user->id);
            
            // Try to send OTP to the newly created user
            \Log::info('Attempting to send OTP...');
            $sendOtpResult = $this->otpService->generateAndSendOtp($user);

            if (!$sendOtpResult) {
                // OTP send failed - delete the user account since we can't verify them
                \Log::error('OTP Send Failed - Deleting account');
                $user->delete();
                \Log::error('OTP Send Failed - Account deleted to maintain data integrity');
                return back()->withErrors(['email' => 'Failed to send OTP. Please try again.'])->withInput();
            }

            \Log::info('OTP sent successfully');
            \Log::info('=== REGISTRATION END (SUCCESS) ===');
            return redirect('/verify-email')->with('email', $user->email)->with('success', 'Account created! Check your email for OTP.');
        } catch (\Exception $e) {
            \Log::error('=== REGISTRATION EXCEPTION ===');
            \Log::error('Exception Message: ' . $e->getMessage());
            \Log::error('Exception Class: ' . get_class($e));
            \Log::error('Exception Code: ' . $e->getCode());
            \Log::error('Stack Trace: ' . $e->getTraceAsString());
            \Log::error('=== REGISTRATION EXCEPTION END ===');
            
            return back()->withErrors(['email' => 'Registration error: ' . $e->getMessage()])->withInput();
        }
    }

    public function verifyEmail(Request $request)
    {
        \Log::info('=== VERIFY EMAIL START ===');
        \Log::info('Request Data: ' . json_encode($request->all()));
        
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|string|size:6',
        ]);

        \Log::info('Validation passed');

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            \Log::error('User not found: ' . $validated['email']);
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        \Log::info('User found. ID: ' . $user->id);

        if ($user->email_verified) {
            \Log::warning('Email already verified for: ' . $validated['email']);
            return response()->json([
                'success' => false,
                'message' => 'Email already verified'
            ], 400);
        }

        \Log::info('Verifying OTP: ' . $validated['otp']);
        if (!$this->otpService->verifyOtp($user, $validated['otp'])) {
            \Log::error('OTP verification failed');
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        \Log::info('OTP verified successfully');
        // Only mark as verified AFTER successful OTP verification
        $user->update(['email_verified' => true]);
        \Log::info('Email marked as verified');
        
        Auth::login($user);
        \Log::info('User logged in');
        \Log::info('=== VERIFY EMAIL END (SUCCESS) ===');

        return response()->json([
            'success' => true,
            'message' => 'Email verified! Welcome to YATIS.',
            'redirect' => '/dashboard'
        ], 200);
    }

    public function resendOtp(Request $request)
    {
        \Log::info('=== RESEND OTP START ===');
        \Log::info('Request Data: ' . json_encode($request->all()));
        
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        \Log::info('Validation passed for email: ' . $validated['email']);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            \Log::error('User not found: ' . $validated['email']);
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        \Log::info('User found. ID: ' . $user->id);

        if ($user->email_verified) {
            \Log::warning('Email already verified for: ' . $validated['email']);
            return response()->json([
                'success' => false,
                'message' => 'Email already verified'
            ], 400);
        }

        \Log::info('Attempting to resend OTP...');
        if ($this->otpService->resendOtp($user)) {
            \Log::info('OTP resent successfully');
            \Log::info('=== RESEND OTP END (SUCCESS) ===');
            return response()->json([
                'success' => true,
                'message' => 'OTP resent to your email'
            ], 200);
        }

        \Log::error('Failed to resend OTP');
        \Log::info('=== RESEND OTP END (FAILED) ===');
        return response()->json([
            'success' => false,
            'message' => 'Failed to resend OTP'
        ], 500);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $dto = new LoginDTO(email: $validated['email'], password: $validated['password']);

        $response = $this->authService->login($dto);

        if (!$response->success) {
            return back()->withErrors(['email' => $response->message])->withInput();
        }

        Auth::login($response->data);

        return redirect('/dashboard')->with('success', $response->message);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/login')->with('success', 'Logged out successfully');
    }

    public function me(Request $request)
    {
        return response()->json(['success' => true, 'data' => $request->user()]);
    }

    public function sendResetLink(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $token = Password::createToken(User::where('email', $validated['email'])->first());

        return redirect()->route('password.reset', ['token' => $token, 'email' => $validated['email']]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email|exists:users,email',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect('/login')->with('success', 'Password reset successfully! Please login with your new password.')
            : back()->withErrors(['email' => __($status)]);
    }
}
