<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserProfileCollection;
use App\Http\Resources\UserProfileResource;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): UserProfileCollection
    {
        $profiles = UserProfile::with(['user', 'vehicleType'])->paginate();
        return new UserProfileCollection($profiles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): UserProfileResource
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'address' => 'required|string',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'nin' => 'nullable|string',
            'guarantors_name' => 'nullable|string',
            'photo' => 'nullable|string',
            'barcode' => 'nullable|string',
        ]);

        $profile = UserProfile::create($validated);

        return new UserProfileResource($profile->load(['user', 'vehicleType']));
    }

    /**
     * Display the specified resource.
     */
    public function show(UserProfile $userProfile): UserProfileResource
    {
        return new UserProfileResource($userProfile->load(['user', 'vehicleType']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserProfile $userProfile): UserProfileResource
    {
        $validated = $request->validate([
            'address' => 'sometimes|required|string',
            'vehicle_type_id' => 'sometimes|required|exists:vehicle_types,id',
            'nin' => 'nullable|string',
            'guarantors_name' => 'nullable|string',
            'photo' => 'nullable|string',
            'barcode' => 'nullable|string',
        ]);

        $userProfile->update($validated);

        return new UserProfileResource($userProfile->load(['user', 'vehicleType']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserProfile $userProfile): JsonResponse
    {
        $userProfile->delete();
        return response()->json(null, 204);
    }
}
