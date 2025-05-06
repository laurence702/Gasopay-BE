<?php

namespace App\Http\Controllers\Api;

use App\Models\UserProfile;
use Illuminate\Http\Request;
use App\Enums\VehicleTypeEnum;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rules\Enum;
use App\Http\Resources\UserProfileResource;
use App\Http\Resources\UserProfileCollection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\StoreUserProfileRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use Illuminate\Support\Facades\Storage;

class UserProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $userProfiles = UserProfile::with(['user'])->paginate();
        return UserProfileResource::collection($userProfiles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserProfileRequest $request): UserProfileResource
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = \Illuminate\Support\Facades\Auth::id();
        
        if ($request->hasFile('profile_pic_url') && $request->file('profile_pic_url')->isValid()) {
            // Store the relative path returned by store()
            $validatedData['profile_pic_url'] = $request->file('profile_pic_url')->store('profile-pics', 'public');
        } else {
             unset($validatedData['profile_pic_url']); 
        }
        
        $userProfile = UserProfile::create($validatedData);

        return new UserProfileResource($userProfile->load(['user']));
    }

    /**
     * Display the specified resource.
     */
    public function show(UserProfile $userProfile): UserProfileResource
    {
        return new UserProfileResource($userProfile->load(['user']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserProfileRequest $request, UserProfile $userProfile): UserProfileResource
    {
        $validatedData = $request->validated();

        if ($request->hasFile('profile_pic_url') && $request->file('profile_pic_url')->isValid()) {
            // Delete the old file (using the stored path)
            if ($userProfile->profile_pic_url && Storage::disk('public')->exists($userProfile->profile_pic_url)) {
                 Storage::disk('public')->delete($userProfile->profile_pic_url);
            }
            // Store the new relative path
            $validatedData['profile_pic_url'] = $request->file('profile_pic_url')->store('profile-pics', 'public');
        } else {
             unset($validatedData['profile_pic_url']); 
        }

        $userProfile->update($validatedData);

        return new UserProfileResource($userProfile->load(['user']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserProfile $userProfile): \Illuminate\Http\JsonResponse
    {
        $userProfile->delete();
        return response()->json(null, 204);
    }
}
