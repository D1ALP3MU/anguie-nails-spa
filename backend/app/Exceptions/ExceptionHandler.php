<?php

namespace App\Exceptions;

use Throwable;
use App\Responses\Response;

class ExceptionHandler
{
    public static function handle(Throwable $exception): void
    {
        switch (true) {

            case $exception instanceof ValidationException:
                Response::json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'errors' => $exception->getErrors()
                ], 422);
                break;

            case $exception instanceof NotFoundException:
                Response::json([
                    'success' => false,
                    'message' => $exception->getMessage()
                ], 404);
                break;

            case $exception instanceof ConflictException:
                Response::json([
                    'success' => false,
                    'message' => $exception->getMessage()
                ], 409);
                break;

            case $exception instanceof UnauthorizedException:
                Response::json([
                    'success' => false,
                    'message' => $exception->getMessage()
                ], 401);
                break;

            default:
                Response::json([
                    'success' => false,
                    'message' => 'Ha ocurrido un error interno del servidor.'
                ], 500);
        }
    }
}
