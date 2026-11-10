<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Endpoints de démonstration dont EcoPulse mesure les performances.
 *
 * Les délais et volumes sont SIMULÉS pour obtenir des comportements différents :
 * la charge est artificielle, mais les mesures prises côté serveur sont réelles.
 */
#[AsController]
final class DemoController
{
    /**
     * Profil de chaque endpoint : délai artificiel (ms) et nombre d'éléments renvoyés.
     */
    public const PROFILES = [
        'fast' => ['delay_ms' => 0, 'items' => 5],
        'medium' => ['delay_ms' => 150, 'items' => 50],
        'slow' => ['delay_ms' => 600, 'items' => 200],
    ];

    #[Route(
        '/api/demo/{profile}',
        name: 'api_demo',
        requirements: ['profile' => 'fast|medium|slow'],
        methods: ['GET'],
    )]
    public function __invoke(string $profile): JsonResponse
    {
        $config = self::PROFILES[$profile];

        // Simulation de charge : on met le processus en pause (microsecondes).
        if ($config['delay_ms'] > 0) {
            usleep($config['delay_ms'] * 1000);
        }

        $data = [];
        for ($i = 1; $i <= $config['items']; ++$i) {
            $data[] = [
                'id' => $i,
                'label' => sprintf('Élément %s #%d', $profile, $i),
                'value' => ($i * 37) % 100,
            ];
        }

        $response = new JsonResponse([
            'endpoint' => $profile,
            'simulation' => [
                'simulated' => true,
                'delay_ms' => $config['delay_ms'],
                'items' => $config['items'],
            ],
            'data' => $data,
        ]);

        // UTF-8 brut plutôt que "é" : JSON plus lisible et plus léger.
        $response->setEncodingOptions($response->getEncodingOptions() | \JSON_UNESCAPED_UNICODE);

        return $response;
    }
}
