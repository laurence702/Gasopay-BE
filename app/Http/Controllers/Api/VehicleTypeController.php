<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleTypeResource;
use App\Models\VehicleType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class VehicleTypeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $vehicleTypes = VehicleType::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate(10);

        return VehicleTypeResource::collection($vehicleTypes);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:vehicle_types,name',
            ]);

            $vehicleType = VehicleType::create($validated);

            return response()->json(new VehicleTypeResource($vehicleType), 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function show(VehicleType $vehicleType): VehicleTypeResource
    {
        return new VehicleTypeResource($vehicleType);
    }

    public function update(Request $request, VehicleType $vehicleType): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:vehicle_types,name,' . $vehicleType->id,
            ]);

            $vehicleType->update($validated);

            return response()->json(new VehicleTypeResource($vehicleType));
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(VehicleType $vehicleType): JsonResponse
    {
        $vehicleType->delete();
        return response()->json(null, 204);
    }
}
