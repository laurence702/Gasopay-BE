<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchCollection;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\Access\AuthorizationException;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('branch_phone', 'like', "%{$search}%");
            })
            ->paginate();

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

            $branch = Branch::create($validated);
            return new BranchResource($branch);
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

    public function show(Branch $branch)
    {
        try {
            return new BranchResource($branch);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'message' => 'Branch not found.',
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'You are not authorized to perform this action.',
            ], Response::HTTP_FORBIDDEN);
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
            return new BranchResource($branch);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'message' => 'Branch not found.',
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'You are not authorized to perform this action.',
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function destroy(Branch $branch)
    {
        try {
            $branch->delete();
            return response()->noContent();
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'message' => 'Branch not found.',
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'You are not authorized to perform this action.',
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
