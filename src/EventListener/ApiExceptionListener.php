<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\BusinessException;
use App\Exception\CurrencyTickerException;
use App\Exception\RateLimitExceededException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $debugMode = false
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (! str_starts_with($request->getPathInfo(), '/api/')) {
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

        $response = new JsonResponse();
        $responseData = [
            'timestamp' => (new \DateTime())->format('c'),
        ];

        switch (true) {
            case $exception instanceof ValidationFailedException:
                $responseData['message'] = $exception->getViolations()->get(0)->getMessage();
                $responseData['error_code'] = 'VALIDATION_ERROR';
                $responseData['status_code'] = Response::HTTP_BAD_REQUEST;
                $statusCode = Response::HTTP_BAD_REQUEST;

                break;
            case $exception instanceof HttpExceptionInterface:
                $responseData['message'] = $exception->getMessage();
                $responseData['error_code'] = 'HTTP_EXCEPTION';
                $responseData['status_code'] = $exception->getStatusCode();
                $statusCode = $exception->getStatusCode();

                break;
            case $exception instanceof MethodNotAllowedException:
                $responseData['message'] = $exception->getMessage();
                $responseData['error_code'] = 'METHOD_NOT_ALLOWED';
                $responseData['status_code'] = Response::HTTP_METHOD_NOT_ALLOWED;
                $statusCode = Response::HTTP_METHOD_NOT_ALLOWED;

                break;
            default:
                $message = $this->debugMode
                    ? $exception->getMessage()
                    : 'An error occurred while processing your request';

                $responseData['message'] = $message;
                $responseData['error_code'] = 'INTERNAL_ERROR';
                $responseData['status_code'] = Response::HTTP_INTERNAL_SERVER_ERROR;
                $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        $response->setData($responseData);
        $response->setStatusCode($statusCode);

        return $response;
    }

    private function logException(\Throwable $exception, Request $request): void
    {
        $context = [
            'exception' => $exception,
            'request_uri' => $request->getUri(),
            'client_ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
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
