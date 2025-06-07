<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Order;
use App\Models\Branch;
use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\BranchResource;
use App\Http\Resources\BranchCollection;
use App\Http\Requests\CreateBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BranchController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        $query = Branch::query();

        if ($search = request('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%");
        }

        return BranchResource::collection($query->paginate());
    }

    /**
     * Store a newly created branch in storage.
     */
    public function store(CreateBranchRequest $request)
    {
        $validatedData = $request->validated();
        
        // Create the branch first
        $branch = Branch::create([
            'name' => $validatedData['name'],
            'location' => $validatedData['location'],
            'branch_phone' => $validatedData['branch_phone'],
        ]);

        // Create the admin user for this branch
        $admin = User::create([
            'fullname' => $validatedData['fullname'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => RoleEnum::Admin->value,
            'branch_id' => $branch->id,
            'phone' => $validatedData['phone'],
        ]);

        return new BranchResource($branch);
    }

    /**
     * Handle the case when a branch is not found.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFound()
    {
        return response()->json([
            'message' => 'Branch not found.',
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * Display the specified branch.
     */
    public function show(Branch $branch)
    {
        return new BranchResource($branch);
    }

    /**
     * Update the specified branch in storage.
     */
    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $branch->update($request->validated());
        return new BranchResource($branch);
    }

    /**
     * Soft delete the specified branch.
     */
    public function destroy(Branch $branch)
    {
        $branch->delete();
        return response()->noContent();
    }

    /**
     * Permanently delete the specified branch.
     */
    public function forceDelete(Branch $branch)
    {
        $branch->forceDelete();
        return response()->json(['message' => 'Branch permanently deleted.'], 200);
    }
}
