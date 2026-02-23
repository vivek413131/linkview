<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\AboutProfile;
use App\Models\Designation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{



    // public function updateProfile(Request $request)
    // {
    //     try {
    //         $userId = Auth::id();

    //         if (!$userId) {
    //             return ApiResponse::error('Unauthorized', 401);
    //         }
    //         $validated = $request->validate([
    //             'name'  => 'sometimes|string|max:255',
    //             'about'  => 'sometimes|string',
    //             'designation'  => 'sometimes|numeric',
    //             'email' => 'sometimes|email|unique:users,email,' . $userId,
    //             'lat'   => 'sometimes|numeric',
    //             'long'  => 'sometimes|numeric',
    //             'profile_pic' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
    //         ]);
    //         $user = User::findOrFail($userId);

    //         DB::beginTransaction();
    //         $updateData = [];

    //         if ($request->has('name')) {
    //             $updateData['name'] = $validated['name'];
    //         }

    //         if ($request->has('email')) {
    //             $updateData['email'] = $validated['email'];
    //         }


    //         if ($request->has('lat') && $request->has('long')) {
    //             $updateData['location_lat'] = $validated['lat'];
    //             $updateData['location_lng'] = $validated['long'];
    //         }

    //         if ($request->hasFile('profile_pic')) {
    //             if ($user->profile_pic && Storage::disk('public')->exists('profile/' . $user->profile_pic)) {
    //                 Storage::disk('public')->delete('profile/' . $user->profile_pic);
    //             }

    //             $file = $request->file('profile_pic');
    //             $uniqueName = Str::uuid() . '.' . $file->getClientOriginalExtension();

    //             $file->storeAs('profile', $uniqueName, 'public');
    //             $updateData['profile_pic'] = $uniqueName;
    //         }

    //         if ($request->has('designation')) {
    //             $updateData['designation_id'] = $validated['designation'];
    //         }
    //         if (!empty($updateData)) {
    //             $user->update($updateData);
    //         }

    //         if ($request->filled('about')) {

    //             AboutProfile::updateOrCreate(
    //                 ['user_id' => $user->id],
    //                 ['description' => $request->about]
    //             );
    //         }



    //         DB::commit();

    //         return ApiResponse::success($user, 'Profile Updated Successfully');
    //     } catch (ValidationException $e) {
    //         return ApiResponse::error($e->errors(), 422);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return ApiResponse::error('Something went wrong', 500);
    //     }
    // }


    public function updateProfile(Request $request)
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return ApiResponse::error('Unauthorized', 401);
            }

            $validated = $request->validate([
                'name'        => 'sometimes|string|max:255',
                'about'       => 'sometimes|string',
                'designation' => 'sometimes|exists:designations,id',
                'email'       => 'sometimes|email|unique:users,email,' . $userId,
                'lat'         => 'sometimes|numeric',
                'long'        => 'sometimes|numeric',
                'profile_pic' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            $user = User::findOrFail($userId);

            DB::transaction(function () use ($request, $validated, $user) {

                $updateData = [];

                if (!empty($validated['name'])) {
                    $updateData['name'] = $validated['name'];
                }

                if (!empty($validated['email'])) {
                    $updateData['email'] = $validated['email'];
                }

                if (isset($validated['lat']) && isset($validated['long'])) {
                    $updateData['location_lat'] = $validated['lat'];
                    $updateData['location_lng'] = $validated['long'];
                }

                if (!empty($validated['designation'])) {
                    $updateData['designation_id'] = $validated['designation'];
                }

                if ($request->hasFile('profile_pic')) {

                    if (
                        $user->profile_pic &&
                        Storage::disk('public')->exists('profile/' . $user->profile_pic)
                    ) {
                        Storage::disk('public')->delete('profile/' . $user->profile_pic);
                    }

                    $file = $request->file('profile_pic');
                    $uniqueName = Str::uuid() . '.' . $file->getClientOriginalExtension();

                    $file->storeAs('profile', $uniqueName, 'public');

                    $updateData['profile_pic'] = $uniqueName;
                }

                if (!empty($updateData)) {
                    $user->update($updateData);
                }

                if (!empty($validated['about'])) {
                    $user->about()->updateOrCreate(
                        [],
                        ['description' => $validated['about']]
                    );
                }
            });

            $user->refresh();

            return ApiResponse::success($user, 'Profile Updated Successfully');
        } catch (ValidationException $e) {
            return ApiResponse::error($e->errors(), 422);
        } catch (Exception $e) {
            return ApiResponse::error('Something went wrong', 500);
        }
    }


    public function getDesignation()
    {
        try {
            $model = Designation::select('id', 'name')->get();

            return ApiResponse::success($model, 'Designation List');
        } catch (\Exception $e) {
            return ApiResponse::error('Something went wrong', 500);
        }
    }

    public function getProfile()
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return ApiResponse::error('Unauthorized', 401);
            }
            $user = User::findOrFail($userId)->with(['designation',]);
            return ApiResponse::success($user, 'Designation List');
        } catch (\Exception $e) {
            return ApiResponse::error('Something went wrong', 500);
        }
    }
}
