<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * API de lecture : /api/metrics et /api/stats.
 */
final class MetricsControllerTest extends ApiTestCase
{
    public function testReadEndpointsDoNotCreateMeasurements(): void
    {
        $this->client->request('GET', '/api/stats');
        $this->client->request('GET', '/api/metrics');

        self::assertSame(0, $this->countMeasurements(), 'Le dashboard ne doit pas fausser les statistiques.');
    }

    public function testStatsOnEmptyDatabase(): void
    {
        $json = $this->jsonRequest('GET', '/api/stats');

        self::assertResponseIsSuccessful();
        self::assertSame(0, $json['summary']['totalRequests']);
        self::assertSame(0, $json['summary']['totalBytes']);
        // JSON ne distingue pas 0 et 0.0 : on compare la valeur, pas le type.
        self::assertEquals(0, $json['summary']['avgDurationMs']);
        self::assertSame([], $json['endpoints']);
    }

    public function testStatsAggregatesMeasurementsPerEndpoint(): void
    {
        $sizes = [];
        foreach (['fast', 'fast', 'medium'] as $profile) {
            $this->client->request('GET', "/api/demo/$profile");
            $sizes[$profile] = \strlen((string) $this->client->getResponse()->getContent());
        }

        $json = $this->jsonRequest('GET', '/api/stats');

        self::assertSame(3, $json['summary']['totalRequests']);
        self::assertSame(2 * $sizes['fast'] + $sizes['medium'], $json['summary']['totalBytes']);

        // Tri du plus lent au plus rapide : medium (150 ms simulés) avant fast.
        self::assertSame(['medium', 'fast'], array_column($json['endpoints'], 'endpoint'));

        [$medium, $fast] = $json['endpoints'];
        self::assertSame(1, $medium['requests']);
        self::assertSame(2, $fast['requests']);
        self::assertSame(2 * $sizes['fast'], $fast['totalBytes']);
        self::assertGreaterThanOrEqual(150, $medium['avgDurationMs']);
        self::assertLessThanOrEqual($fast['maxDurationMs'], $fast['avgDurationMs']);
    }

    public function testMetricsRespectsLimitAndReturnsNewestFirst(): void
    {
        foreach (['fast', 'medium', 'fast'] as $profile) {
            $this->client->request('GET', "/api/demo/$profile");
        }

        $json = $this->jsonRequest('GET', '/api/metrics?limit=2');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $json['items']);
        self::assertSame(['fast', 'medium'], array_column($json['items'], 'endpoint'));
        self::assertGreaterThan($json['items'][1]['id'], $json['items'][0]['id']);
    }

    public static function invalidLimitProvider(): iterable
    {
        yield 'zéro' => ['0'];
        yield 'au-dessus du maximum' => ['201'];
        yield 'non numérique' => ['abc'];
    }

    #[DataProvider('invalidLimitProvider')]
    public function testMetricsRejectsInvalidLimitWithJson400(string $limit): void
    {
        $json = $this->jsonRequest('GET', "/api/metrics?limit=$limit");

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['status' => 400, 'message' => 'Bad Request'], $json['error']);
    }
}
