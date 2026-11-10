<?php

namespace App\EventSubscriber;

use App\Entity\Measurement;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Mesure le temps de traitement côté serveur et la taille des réponses
 * des endpoints de démonstration, puis enregistre la mesure en base.
 *
 * Mesuré : du début du traitement de la requête (avant le routage) jusqu'à la réponse finale.
 * Non mesuré : démarrage du framework, latence réseau, rendu côté navigateur.
 */
final class PerformanceSubscriber implements EventSubscriberInterface
{
    /** Seule route mesurée : les appels du dashboard ne faussent pas les statistiques. */
    public const MEASURED_ROUTE = 'api_demo';

    private const START_ATTRIBUTE = '_ecopulse_start';
    private const MEASUREMENT_ATTRIBUTE = '_ecopulse_measurement';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité haute : on démarre avant le routage (RouterListener = 32).
            KernelEvents::REQUEST => ['startTimer', 512],
            // Priorité basse : on s'arrête quand tous les autres listeners ont modifié la réponse.
            KernelEvents::RESPONSE => ['stopTimer', -512],
            KernelEvents::TERMINATE => 'persistMeasurement',
        ];
    }

    public function startTimer(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Horloge monotone en nanosecondes : insensible aux changements d'heure système.
        $event->getRequest()->attributes->set(self::START_ATTRIBUTE, hrtime(true));
    }

    public function stopTimer(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $start = $request->attributes->get(self::START_ATTRIBUTE);

        if (!$event->isMainRequest() || null === $start
            || self::MEASURED_ROUTE !== $request->attributes->get('_route')) {
            return;
        }

        $durationMs = (hrtime(true) - $start) / 1_000_000;
        $response = $event->getResponse();
        $content = $response->getContent();

        $request->attributes->set(self::MEASUREMENT_ATTRIBUTE, new Measurement(
            endpoint: (string) $request->attributes->get('profile'),
            method: $request->getMethod(),
            statusCode: $response->getStatusCode(),
            durationMs: round($durationMs, 3),
            // strlen compte des octets (et non des caractères) : c'est ce qui transite sur le réseau.
            responseSize: false === $content ? 0 : \strlen($content),
        ));
    }

    public function persistMeasurement(TerminateEvent $event): void
    {
        $measurement = $event->getRequest()->attributes->get(self::MEASUREMENT_ATTRIBUTE);

        if (!$measurement instanceof Measurement) {
            return;
        }

        try {
            $this->entityManager->persist($measurement);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            // La démo doit continuer à répondre même si la base est indisponible.
            $this->logger->error('EcoPulse : échec de l\'enregistrement de la mesure', ['exception' => $e]);
        }
    }
}
