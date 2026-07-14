<?php

namespace App\Domains\Shared\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Shared\Responses\ApiPaginatedResponse;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseApiController extends Controller
{
    protected function success(
        mixed $data = null,
        string $message = 'Success'
    ) {
        return ApiResponse::success($data, $message);
    }

    protected function created(
        mixed $data = null,
        string $message = 'Created successfully.'
    ) {
        return ApiResponse::created($data, $message);
    }

    protected function deleted(
        string $message = 'Deleted successfully.'
    ) {
        return ApiResponse::deleted($message);
    }

    protected function paginated(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        string $message = 'Success'
    ) {
        $paginator->setCollection(
            $resourceClass::collection(
                collect($paginator->items())
            )->collection
        );

        return ApiPaginatedResponse::make(
            $paginator,
            $message
        );
    }

    protected function resource(
        JsonResource $resource,
        string $message = 'Success'
    ) {
        return ApiResponse::success(
            $resource,
            $message
        );
    }
}