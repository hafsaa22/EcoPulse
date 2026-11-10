<?php

namespace App\Controller;

use App\Entity\Measurement;
use App\Repository\MeasurementRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API de lecture des mesures, consommée par le dashboard.
 * Ces routes ne sont PAS mesurées (seule la route "api_demo" l'est).
 */
#[AsController]
#[Route('/api', methods: ['GET'])]
final class MetricsController
{
    public function __construct(private readonly MeasurementRepository $repository)
    {
    }

    /** Dernières mesures brutes (pour le graphique). */
    #[Route('/metrics', name: 'api_metrics')]
    public function metrics(
        #[MapQueryParameter(
            options: ['min_range' => 1, 'max_range' => 200],
            validationFailedStatusCode: Response::HTTP_BAD_REQUEST,
        )]
        int $limit = 50,
    ): JsonResponse {
        $measurements = $this->repository->findLatest($limit);

        return new JsonResponse([
            'items' => array_map(static fn (Measurement $m): array => [
                'id' => $m->getId(),
                'endpoint' => $m->getEndpoint(),
                'method' => $m->getMethod(),
                'statusCode' => $m->getStatusCode(),
                'durationMs' => $m->getDurationMs(),
                'responseSize' => $m->getResponseSize(),
                'createdAt' => $m->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ], $measurements),
        ]);
    }

    /** Statistiques agrégées : résumé global + détail par endpoint. */
    #[Route('/stats', name: 'api_stats')]
    public function stats(): JsonResponse
    {
        return new JsonResponse([
            'summary' => $this->repository->getSummary(),
            'endpoints' => $this->repository->getStatsByEndpoint(),
        ]);
    }
}
