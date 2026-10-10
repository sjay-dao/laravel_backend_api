<?php

namespace App\Domains\Shared\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200
    ): JsonResponse {

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function created(
        mixed $data = null,
        string $message = 'Created successfully.'
    ): JsonResponse {

        return self::success(
            $data,
            $message,
            201
        );
    }

    public static function error(
        string $message = 'An error occurred.',
        mixed $errors = null,
        int $status = 400
    ): JsonResponse {

        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    public static function deleted(
        string $message = 'Deleted successfully.'
    ): JsonResponse {

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
}