<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Helpers\ApiResponse;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        if ($request->is('api/*')) {

            // Validation errors
            if ($exception instanceof ValidationException) {
                return ApiResponse::error('Validation Error', 422, $exception->errors());
            }

            // Model not found
            if ($exception instanceof ModelNotFoundException) {
                return ApiResponse::error('Resource not found', 404);
            }

            // JWT exceptions
            if ($exception instanceof TokenBlacklistedException) {
                return ApiResponse::error('Token has been blacklisted', 401);
            }

            if ($exception instanceof TokenExpiredException) {
                return ApiResponse::error('Token expired', 401);
            }

            if ($exception instanceof TokenInvalidException) {
                return ApiResponse::error('Token invalid', 401);
            }

            if ($exception instanceof JWTException) {
                return ApiResponse::error('Token not provided', 401);
            }

            // HTTP exceptions like Unauthorized from middleware
            if ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                $message = $exception->getMessage() ?: ($status == 401 ? 'Unauthorized' : 'HTTP Error');
                return ApiResponse::error($message, $status);
            }

            // Default for other exceptions
            return ApiResponse::error('Server Error', 500);
        }

        return parent::render($request, $exception);
    }
}
