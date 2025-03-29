<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleTypeResource;
use App\Models\VehicleType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehicleTypeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $vehicleTypes = VehicleType::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate();

        return VehicleTypeResource::collection($vehicleTypes);
    }

    public function store(Request $request): VehicleTypeResource
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:vehicle_types,name',
        ]);

        $vehicleType = VehicleType::create($validated);

        return new VehicleTypeResource($vehicleType);
    }

    public function show(VehicleType $vehicleType): VehicleTypeResource
    {
        return new VehicleTypeResource($vehicleType);
    }

    public function update(Request $request, VehicleType $vehicleType): VehicleTypeResource
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:vehicle_types,name,' . $vehicleType->id,
        ]);

        $vehicleType->update($validated);

        return new VehicleTypeResource($vehicleType);
    }

    public function destroy(VehicleType $vehicleType): JsonResponse
    {
        $vehicleType->delete();
        return response()->json(null, 204);
    }
}
