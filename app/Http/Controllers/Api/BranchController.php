<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Http\Resources\BranchCollection;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request): BranchCollection
    {
        $branches = Branch::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('branch_phone', 'like', "%{$search}%");
            })
            ->with(['admin', 'users'])
            ->paginate();

        return new BranchCollection($branches);
    }

    public function store(StoreBranchRequest $request): BranchResource
    {
        $branch = Branch::create($request->validated());

        return new BranchResource($branch->load(['admin', 'users']));
    }

    public function show(Branch $branch): BranchResource
    {
        return new BranchResource($branch->load(['admin', 'users']));
    }

    public function update(UpdateBranchRequest $request, Branch $branch): BranchResource
    {
        $branch->update($request->validated());

        return new BranchResource($branch->load(['admin', 'users']));
    }

    public function destroy(Branch $branch): JsonResponse
    {
        $branch->delete();

        return response()->json([
            'message' => 'Branch deleted successfully'
        ]);
    }
}
