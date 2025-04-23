<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponseTrait
{
    /**
     * Return a success JSON response.
     *
     * @param  mixed  $data
     * @param  string|null $message
     * @param  int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data, string $message = null, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response.
     *
     * @param  string $message
     * @param  int $statusCode
     * @param  mixed|null $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $message, int $statusCode = Response::HTTP_BAD_REQUEST, $errors = null): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
} 