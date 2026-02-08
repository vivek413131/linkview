<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Otp;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Helpers\ApiResponse;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Authentication APIs"
 * )
 */

class AuthController extends Controller
{

 /**
     * @OA\Post(
     *     path="/api/send-otp",
     *     tags={"Auth"},
     *     summary="Send OTP to mobile",
     *     description="Send OTP to the user's mobile number",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mobile"},
     *             @OA\Property(property="mobile", type="string", example="9876543210")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="OTP sent successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     */

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


     /**
     * @OA\Post(
     *     path="/api/verify-otp",
     *     tags={"Auth"},
     *     summary="Verify OTP and login",
     *     description="Verify OTP sent to the user's mobile and return JWT token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mobile","otp"},
     *             @OA\Property(property="mobile", type="string", example="9876543210"),
     *             @OA\Property(property="otp", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="OTP verified successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string", example="jwt_token_here"),
     *                 @OA\Property(property="user", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid or expired OTP"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
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

     /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *     summary="Logout user",
     *     description="Invalidate the current JWT token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Logged out successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Token invalid or expired"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    
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
