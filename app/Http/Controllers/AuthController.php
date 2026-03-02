<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\User;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Login using email/password
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }
            return back()->with('error', 'Invalid credentials')->withInput();
        }

        if (!$user->is_active) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Account is inactive'], 403);
            }
            return back()->with('error', 'Account is inactive')->withInput();
        }

        $request->session()->put('user_id', $user->id);
        $request->session()->put('role', $user->role);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged in',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ]);
        }

        return redirect()->intended('/admin/ui/dashboard');
    }

    /**
     * Logout current session
     */
    public function logout(Request $request)
    {
        $request->session()->flush();
        
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Logged out']);
        }
        
        return redirect('/login')->with('message', 'Logged out successfully');
    }

    /**
     * Current session status
     */
    public function status(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return response()->json(['authenticated' => false]);
        }

        $user = User::find($userId);
        return response()->json([
            'authenticated' => !!$user,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ] : null,
        ]);
    }

    /**
     * Show forgot password form
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send password reset link
     */
    public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::where('email', $request->email)->first();
        $token = Str::random(64);

        // Store token in password_resets table (create if doesn't exist)
        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        // Send email
        try {
            Mail::send('emails.reset-password', [
                'token' => $token,
                'user' => $user,
                'resetUrl' => url('/reset-password/' . $token),
            ], function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Reset Your Password - ZenithSoles');
            });

            return back()->with('success', 'Password reset link sent to your email!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    /**
     * Show reset password form
     */
    public function showResetPasswordForm($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$reset || !Hash::check($request->token, $reset->token)) {
            return back()->with('error', 'Invalid or expired reset token');
        }

        // Check if token is older than 1 hour
        if (now()->diffInHours($reset->created_at) > 1) {
            return back()->with('error', 'Reset token has expired');
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete reset token
        DB::table('password_resets')->where('email', $request->email)->delete();

        return redirect('/login')->with('success', 'Password reset successfully! Please login.');
    }

    /**
     * Test SMTP configuration
     */
    public function testSmtp(Request $request)
    {
        try {
            $to = $request->get('to', 'admin@zenithsoles.in');
            
            Mail::raw('This is a test email from ZenithSoles Affiliate System. SMTP is working correctly!', function ($message) use ($to) {
                $message->to($to)
                        ->subject('SMTP Test - ZenithSoles');
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Test email sent successfully to ' . $to,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send test email: ' . $e->getMessage(),
            ], 500);
        }
    }
}


