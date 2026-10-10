<?php

namespace App\Domains\Shared\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class ApiPaginatedResponse
{
    public static function make(
        LengthAwarePaginator $paginator,
        string $message = 'Success'
    ): JsonResponse {

        return response()->json([
            'success' => true,
            'message' => $message,

            'data' => $paginator->items(),

            'meta' => [

                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),

                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),

            ]
        ]);
    }
}