<?php

namespace App\Repository;

use App\Entity\Measurement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Point d'accès en lecture aux mesures : toutes les requêtes sur la table "measurement" passent ici.
 *
 * @extends ServiceEntityRepository<Measurement>
 */
class MeasurementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Measurement::class);
    }

    /**
     * Dernières mesures, de la plus récente à la plus ancienne.
     *
     * @return Measurement[]
     */
    public function findLatest(int $limit): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques agrégées par endpoint, calculées par PostgreSQL (GROUP BY),
     * triées du plus lent au plus rapide.
     *
     * @return list<array{endpoint: string, requests: int, avgDurationMs: float, minDurationMs: float, maxDurationMs: float, avgSizeBytes: float, totalBytes: int}>
     */
    public function getStatsByEndpoint(): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select(
                'm.endpoint AS endpoint',
                'COUNT(m.id) AS requests',
                'AVG(m.durationMs) AS avgDurationMs',
                'MIN(m.durationMs) AS minDurationMs',
                'MAX(m.durationMs) AS maxDurationMs',
                'AVG(m.responseSize) AS avgSizeBytes',
                'SUM(m.responseSize) AS totalBytes',
            )
            ->groupBy('m.endpoint')
            ->orderBy('avgDurationMs', 'DESC')
            ->getQuery()
            ->getArrayResult();

        // PostgreSQL renvoie les agrégats sous forme de chaînes : on les type explicitement.
        return array_map(static fn (array $row): array => [
            'endpoint' => $row['endpoint'],
            'requests' => (int) $row['requests'],
            'avgDurationMs' => round((float) $row['avgDurationMs'], 1),
            'minDurationMs' => round((float) $row['minDurationMs'], 1),
            'maxDurationMs' => round((float) $row['maxDurationMs'], 1),
            'avgSizeBytes' => round((float) $row['avgSizeBytes']),
            'totalBytes' => (int) $row['totalBytes'],
        ], $rows);
    }

    /**
     * Résumé global : nombre total de requêtes, durée moyenne et volume total.
     *
     * @return array{totalRequests: int, avgDurationMs: float, totalBytes: int}
     */
    public function getSummary(): array
    {
        $row = $this->createQueryBuilder('m')
            ->select('COUNT(m.id) AS totalRequests', 'AVG(m.durationMs) AS avgDurationMs', 'SUM(m.responseSize) AS totalBytes')
            ->getQuery()
            ->getSingleResult();

        // Table vide : AVG et SUM renvoient NULL, d'où les conversions vers 0.
        return [
            'totalRequests' => (int) $row['totalRequests'],
            'avgDurationMs' => round((float) $row['avgDurationMs'], 1),
            'totalBytes' => (int) $row['totalBytes'],
        ];
    }
}
