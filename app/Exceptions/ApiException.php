<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiException extends Exception
{
    protected array $errors = [];
    protected array $context = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        array $errors = [],
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
        $this->context = $context;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errorCode' => $this->getCode(),
            'errors' => $this->errors,
            'timestamp' => now()->toISOString(),
            'path' => request()->path(),
            'traceId' => uniqid(),
        ], $this->getHttpStatusCode());
    }

    protected function getHttpStatusCode(): int
    {
        return match ($this->getCode()) {
            1001 => Response::HTTP_BAD_REQUEST,
            1002 => Response::HTTP_UNAUTHORIZED,
            1003 => Response::HTTP_FORBIDDEN,
            1004 => Response::HTTP_NOT_FOUND,
            1005 => Response::HTTP_CONFLICT,
            1006 => Response::HTTP_UNPROCESSABLE_ENTITY,
            1007 => Response::HTTP_TOO_MANY_REQUESTS,
            default => $this->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }
}
