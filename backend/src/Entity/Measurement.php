<?php

namespace App\Entity;

use App\Repository\MeasurementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une mesure de performance d'un appel à un endpoint de démonstration.
 * Immuable : une mesure est un fait passé, on ne la modifie pas.
 */
#[ORM\Entity(repositoryClass: MeasurementRepository::class)]
#[ORM\Index(name: 'idx_measurement_created_at', columns: ['created_at'])]
class Measurement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        /** Profil appelé : fast, medium ou slow. */
        #[ORM\Column(length: 20)]
        private string $endpoint,

        #[ORM\Column(length: 10)]
        private string $method,

        #[ORM\Column(type: Types::SMALLINT)]
        private int $statusCode,

        /** Temps de traitement côté serveur, en millisecondes. */
        #[ORM\Column]
        private float $durationMs,

        /** Taille du corps de la réponse, en octets. */
        #[ORM\Column]
        private int $responseSize,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getDurationMs(): float
    {
        return $this->durationMs;
    }

    public function getResponseSize(): int
    {
        return $this->responseSize;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
