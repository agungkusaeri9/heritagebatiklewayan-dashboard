<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Build a successful JSON response.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $code = Response::HTTP_OK,
        array $meta = [],
    ): JsonResponse {
        return static::format(
            status: 'success',
            message: $message,
            code: $code,
            data: $data,
            meta: $meta,
        );
    }

    /**
     * Build a paginated successful JSON response.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function paginate(
        LengthAwarePaginator $paginator,
        mixed $data = null,
        string $message = 'Success',
        int $code = Response::HTTP_OK,
        array $meta = [],
    ): JsonResponse {
        $paginationMeta = [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
            ...$meta,
        ];

        return static::format(
            status: 'success',
            message: $message,
            code: $code,
            data: $data ?? $paginator->items(),
            meta: $paginationMeta,
        );
    }

    /**
     * Build an error JSON response.
     *
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, mixed>  $meta
     */
    public static function error(
        string $message = 'Error',
        int $code = Response::HTTP_BAD_REQUEST,
        ?array $errors = null,
        array $meta = [],
    ): JsonResponse {
        return static::format(
            status: 'error',
            message: $message,
            code: $code,
            data: null,
            errors: $errors,
            meta: $meta,
        );
    }

    /**
     * Build the standardized response payload.
     *
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, mixed>  $meta
     */
    protected static function format(
        string $status,
        string $message,
        int $code,
        mixed $data = null,
        ?array $errors = null,
        array $meta = [],
    ): JsonResponse {
        $payload = [
            'meta' => [
                'code' => $code,
                'status' => $status,
                'message' => $message,
                ...$meta,
            ],
            'data' => $data,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }
}
