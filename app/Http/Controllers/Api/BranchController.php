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

class BranchController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index(Request $request)
    {
        $branches = Branch::query()
            ->with('branchAdmin')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('branch_phone', 'like', "%{$search}%");
            })
            ->paginate(10);

        return new BranchCollection($branches);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'branch_phone' => 'required|string|max:20',
            ]);

            // Create a new admin user for the branch
            $admin = User::factory()->create([
                'role' => RoleEnum::Admin,
                'fullname' => $validated['name'] . ' Admin',
                'email' => strtolower(str_replace(' ', '.', $validated['name'])) . '.admin@example.com',
                'phone' => $validated['branch_phone'],
            ]);

            $validated['branch_admin'] = $admin->id;
            $branch = Branch::create($validated);

            return new BranchResource($branch->load('branchAdmin'));
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'You are not authorized to perform this action.',
            ], Response::HTTP_FORBIDDEN);
        }
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

    public function show(Branch $branch)
    {
        try {
            return new BranchResource($branch->load('branchAdmin'));
        } catch (ModelNotFoundException $e) {
            return $this->notFound();
        }
    }

    public function update(Request $request, Branch $branch)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'branch_phone' => 'required|string|max:20',
            ]);

            $branch->update($validated);
            return new BranchResource($branch->load('branchAdmin'));
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ModelNotFoundException $e) {
            return $this->notFound();
        }
    }

    public function destroy(Branch $branch)
    {
        try {
            User::where('id', $branch->branch_admin)->delete();
            $branch->delete();
            return response()->noContent();
        } catch (ModelNotFoundException $e) {
            return $this->notFound();
        }
    }
}
