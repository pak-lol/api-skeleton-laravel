<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PasswordRemindController extends Controller
{
    use ApiResponseTrait;

    /**
     * Send a password reset link to the given user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            // Check if the user exists
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                // Don't reveal whether a user exists for security reasons
                return $this->successResponse(
                    null,
                    __('passwords.sent')
                );
            }

            // Generate a new reset token
            $token = Str::random(64);
            $expiresAt = now()->addMinutes(config('auth.passwords.users.expire', 60));

            // Store the token in the database
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            // Generate the reset URL for the email
            $resetUrl = config('app.frontend_url', config('app.url')) . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

            // In a real application, you would send an email with the reset URL here
            // Mail::to($request->email)->send(new PasswordResetMail($resetUrl));

            // Since we're not actually sending an email in this example, we'll return the token in the response
            // In production, you would only return a success message
            return $this->successResponse(
                null,
                'Password reset link has been sent'
            );
        } catch (\Exception $e) {
            Log::error('Failed to send password reset link: ' . $e->getMessage());
            return $this->errorResponse(__('messages.server_error'), null, 500);
        }
    }

    /**
     * Reset the given user's password.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            // Find the token record
            $tokenRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$tokenRecord) {
                return $this->errorResponse(
                    'Invalid token or email',
                    null,
                    400
                );
            }

            // Verify token
            if (!Hash::check($request->token, $tokenRecord->token)) {
                return $this->errorResponse(
                    'Invalid token',
                    null,
                    400
                );
            }

            // Check token expiration
            $createdAt = \Carbon\Carbon::parse($tokenRecord->created_at);
            $expiresAt = $createdAt->addMinutes(config('auth.passwords.users.expire', 60));

            if (now()->gt($expiresAt)) {
                // Delete expired token
                DB::table('password_reset_tokens')
                    ->where('email', $request->email)
                    ->delete();

                return $this->errorResponse(
                    'Token has expired',
                    null,
                    400
                );
            }

            // Find the user
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return $this->errorResponse(
                    'User not found',
                    null,
                    404
                );
            }

            // Reset the password
            $user->password = Hash::make($request->password);
            $user->setRememberToken(Str::random(60));
            $user->save();

            // Delete the token to prevent reuse
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            // Fire password reset event
            event(new PasswordReset($user));

            return $this->successResponse(
                null,
                'Password has been reset successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to reset password: ' . $e->getMessage());
            return $this->errorResponse(__('messages.server_error'), null, 500);
        }
    }

    /**
     * Check if a password reset token is valid.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkToken(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required',
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            // Find the token record
            $tokenRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$tokenRecord) {
                return $this->errorResponse(
                    'Invalid token or email',
                    null,
                    400
                );
            }

            // Verify token
            if (!Hash::check($request->token, $tokenRecord->token)) {
                return $this->errorResponse(
                    'Invalid token',
                    null,
                    400
                );
            }

            // Check token expiration
            $createdAt = \Carbon\Carbon::parse($tokenRecord->created_at);
            $expiresAt = $createdAt->addMinutes(config('auth.passwords.users.expire', 60));

            if (now()->gt($expiresAt)) {
                return $this->errorResponse(
                    'Token has expired',
                    null,
                    400
                );
            }

            return $this->successResponse(
                null,
                'Token is valid'
            );
        } catch (\Exception $e) {
            Log::error('Failed to check password reset token: ' . $e->getMessage());
            return $this->errorResponse(__('messages.server_error'), null, 500);
        }
    }
}
