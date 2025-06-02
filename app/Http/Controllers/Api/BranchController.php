<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchCollection;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Http\Requests\CreateBranchRequest;
use App\Http\Requests\UpdateBranchRequest;

class BranchController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
       // $this->authorize('viewAny', Branch::class);
        $branches = Branch::query()
            ->when(request()->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            })
            ->paginate(10);

        return BranchResource::collection($branches);
    }

    /**
     * Store a newly created branch in storage.
     */
    public function store(CreateBranchRequest $request)
    {
        $this->authorize('create', Branch::class);
        
        $validated = $request->validated();

        // Create a new admin user for the branch
        $admin = User::create([
            'name' => $validated['name'] . ' Admin',
            'email' => $validated['email'] ?? strtolower(str_replace(' ', '', $validated['name'])) . '@gasopay.com',
            'password' => bcrypt($validated['password'] ?? 'password'),
            'role' => 'Admin',
        ]);

        // Create the branch
        $branch = Branch::create([
            'name' => $validated['name'],
            'location' => $validated['location'],
            'branch_phone' => $validated['branch_phone'],
            'branch_admin' => $admin->id,
        ]);

        return new BranchResource($branch->load('branchAdmin'));
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
        $this->authorize('view', $branch);
        try {
            return new BranchResource($branch->load('branchAdmin'));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve branch',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified branch in storage.
     */
    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $this->authorize('update', $branch);
        
        $validated = $request->validated();
        $branch->update($validated);
        
        return new BranchResource($branch->load('branchAdmin'));
    }

    /**
     * Remove the specified branch from storage.
     */
    public function destroy(Branch $branch)
    {
        $this->authorize('delete', $branch);
        
        User::where('id', $branch->branch_admin)->delete();
        $branch->delete();
        
        return response()->json(null, 204);
    }
}
