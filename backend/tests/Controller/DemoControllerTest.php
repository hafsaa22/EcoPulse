<?php

namespace App\Tests\Controller;

use App\Controller\DemoController;
use App\Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Endpoints de démonstration + enregistrement automatique des mesures.
 */
final class DemoControllerTest extends ApiTestCase
{
    public static function profileProvider(): iterable
    {
        foreach (DemoController::PROFILES as $profile => $config) {
            yield $profile => [$profile, $config['items']];
        }
    }

    #[DataProvider('profileProvider')]
    public function testDemoEndpointReturnsSimulatedJson(string $profile, int $expectedItems): void
    {
        $json = $this->jsonRequest('GET', "/api/demo/$profile");

        self::assertResponseIsSuccessful();
        self::assertSame($profile, $json['endpoint']);
        self::assertTrue($json['simulation']['simulated'], 'La simulation doit être déclarée dans la réponse.');
        self::assertCount($expectedItems, $json['data']);
    }

    public function testDemoCallRecordsOneMeasurement(): void
    {
        $this->client->request('GET', '/api/demo/medium');
        $body = (string) $this->client->getResponse()->getContent();

        self::assertSame(1, $this->countMeasurements());

        $row = $this->connection()->fetchAssociative('SELECT * FROM measurement');
        self::assertSame('medium', $row['endpoint']);
        self::assertSame('GET', $row['method']);
        self::assertSame(200, (int) $row['status_code']);
        // La taille enregistrée correspond exactement aux octets renvoyés.
        self::assertSame(\strlen($body), (int) $row['response_size']);
        // La durée mesurée inclut au moins le délai simulé (150 ms).
        self::assertGreaterThanOrEqual(150, (float) $row['duration_ms']);
    }

    public function testUnknownEndpointReturnsJson404AndIsNotRecorded(): void
    {
        $json = $this->jsonRequest('GET', '/api/demo/turbo');

        self::assertResponseStatusCodeSame(404);
        self::assertSame(['status' => 404, 'message' => 'Not Found'], $json['error']);
        self::assertSame(0, $this->countMeasurements());
    }

    public function testWrongHttpMethodReturnsJson405(): void
    {
        $json = $this->jsonRequest('POST', '/api/demo/fast');

        self::assertResponseStatusCodeSame(405);
        self::assertResponseHeaderSame('Allow', 'GET');
        self::assertSame(405, $json['error']['status']);
        self::assertSame(0, $this->countMeasurements());
    }
}
