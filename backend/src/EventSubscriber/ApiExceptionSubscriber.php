<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Transforme toute erreur survenant sous /api en réponse JSON homogène :
 * {"error": {"status": 404, "message": "..."}}
 */
final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface) {
            // Erreur HTTP "attendue" (404, 405...) : son message est sûr à afficher.
            $status = $exception->getStatusCode();
            $headers = $exception->getHeaders();
            $message = Response::$statusTexts[$status] ?? 'Erreur';
        } else {
            // Erreur inattendue : on ne divulgue pas les détails internes.
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
            $headers = [];
            $message = 'Erreur interne du serveur';
        }

        $event->setResponse(new JsonResponse(
            ['error' => ['status' => $status, 'message' => $message]],
            $status,
            $headers,
        ));
    }
}
