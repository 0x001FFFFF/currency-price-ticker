<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\CurrencyTickerException;
use App\Exception\RateLimitExceededException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $debugMode = false
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $path = $request->getPathInfo();
        if (preg_match('#^/api/doc(?:\.json|\.yaml|\.html)?$#', $path)) {
            return;
        }

        $response = $this->createErrorResponse($exception);
        $this->logException($exception, $request);

        $event->setResponse($response);
    }

    private function createErrorResponse(\Throwable $exception): JsonResponse
    {
        $exception = $exception->getPrevious() ?? $exception;

        if ($exception instanceof CurrencyTickerException) {
            $response = new JsonResponse(
                $exception->toApiResponse(),
                $exception->getStatusCode()
            );

            // Add Retry-After header for rate limiting
            if ($exception instanceof RateLimitExceededException) {
                $response->headers->set('Retry-After', (string) $exception->getRetryAfter());
            }

            return $response;
        }

        if ($exception instanceof ValidationFailedException) {
            return new JsonResponse([
                'error_code' => 'VALIDATION_ERROR',
                'message' => $exception->getViolations()->get(0)->getMessage(),
                'status_code' => 400,
            ], 400);
        }

        if ($exception instanceof HttpExceptionInterface) {
            return new JsonResponse([
                'error_code' => 'HTTP_EXCEPTION',
                'message' => $exception->getMessage(),
                'status_code' => $exception->getStatusCode(),
                'timestamp' => (new \DateTime())->format('c')
            ], $exception->getStatusCode());
        }

        // Generic error response
        $message = $this->debugMode
            ? $exception->getMessage()
            : 'An error occurred while processing your request';

        return new JsonResponse([
            'error_code' => 'INTERNAL_ERROR',
            'message' => $message,
            'status_code' => 500,
            'timestamp' => (new \DateTime())->format('c')
        ], 500);
    }

    private function logException(\Throwable $exception, $request): void
    {
        $context = [
            'exception' => $exception,
            'request_uri' => $request->getUri(),
            'client_ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent')
        ];

        if ($exception instanceof CurrencyTickerException) {
            $context['error_code'] = $exception->getErrorCode();
            $level = $exception instanceof BusinessException ? 'warning' : 'error';
        } else {
            $level = 'critical';
        }

        $this->logger->log($level, 'API exception occurred', $context);
    }
}
