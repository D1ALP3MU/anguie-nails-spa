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

            case $exception instanceof ForbiddenException:
                Response::json([
                    'success' => false,
                    'message' => $exception->getMessage()
                ], 403);
                break;

            case $exception instanceof MethodNotAllowedException:
                Response::json([
                    'success' => false,
                    'message' => $exception->getMessage()
                ], 405);
                break;

            default:
                error_log(
                    'Error no controlado: '
                    . $exception->getMessage()
                    . ' en ' . $exception->getFile()
                    . ':' . $exception->getLine()
                );

                Response::json([
                    'success' => false,
                    'message' => 'Ha ocurrido un error interno del servidor.'
                ], 500);

                break;
        }
    }
}
