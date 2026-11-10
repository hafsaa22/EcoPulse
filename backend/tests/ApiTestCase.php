<?php

namespace App\Tests;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base des tests fonctionnels de l'API : client HTTP simulé + base de test vidée avant chaque test.
 * La base utilisée est "ecopulse_test" (suffixe _test configuré dans doctrine.yaml).
 */
abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->connection()->executeStatement('DELETE FROM measurement');
    }

    /** Envoie une requête et renvoie le corps JSON décodé. */
    protected function jsonRequest(string $method, string $uri): array
    {
        $this->client->request($method, $uri);

        self::assertResponseHeaderSame('Content-Type', 'application/json');

        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }

    protected function countMeasurements(): int
    {
        return (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM measurement');
    }

    protected function connection(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }
}
