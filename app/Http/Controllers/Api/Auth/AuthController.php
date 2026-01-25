<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Otp;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Helpers\ApiResponse;

class AuthController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate(['mobile' => 'required|min:10']);

        try {
            $otp = app()->environment('local') ? '123456' : rand(100000, 999999);

            Otp::updateOrCreate(
                ['mobile' => $request->mobile],
                ['otp' => $otp, 'expires_at' => now()->addMinutes(5)]
            );

            return ApiResponse::success([], 'OTP sent successfully');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['mobile' => 'required', 'otp' => 'required']);

        try {
            $otpData = Otp::where('mobile', $request->mobile)
                ->where('otp', $request->otp)
                ->where('expires_at', '>=', now())
                ->first();

            if (!$otpData) {
                return ApiResponse::error('Invalid or expired OTP', 401);
            }

            $otpData->delete();

            $user = User::firstOrCreate(['mobile' => $request->mobile]);

            $token = JWTAuth::fromUser($user);

            return ApiResponse::success([
                'token' => $token,
                'user' => $user
            ], 'OTP verified successfully');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::parseToken()->invalidate(); // invalidate current token
            return ApiResponse::success([], 'Logged out successfully');
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return ApiResponse::error('Token already expired', 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return ApiResponse::error('Token invalid', 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return ApiResponse::error('Token not provided', 401);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
