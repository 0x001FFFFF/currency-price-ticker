<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\CurrencyRate\Entity\CurrencyRate;
use App\Domain\CurrencyRate\Repository\CurrencyRateRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CurrencyRate>
 */
class CurrencyRateRepository extends ServiceEntityRepository implements CurrencyRateRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CurrencyRate::class);
    }

    public function findById(int $id): ?CurrencyRate
    {
        /** @var CurrencyRate|null */
        return $this->find($id);
    }

    public function findLatestByPair(string $pair): ?CurrencyRate
    {
        return $this->createQueryBuilder('cr')
            ->where('cr.pair = :pair')
            ->setParameter('pair', $pair)
            ->orderBy('cr.timestamp', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByPairAndDateRange(
        string $pair,
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): array {
        return $this->createQueryBuilder('cr')
            ->where('cr.pair = :pair')
            ->andWhere('cr.timestamp BETWEEN :start AND :end')
            ->setParameter('pair', $pair)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('cr.timestamp', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(CurrencyRate $rate): void
    {
        $this->getEntityManager()->persist($rate);
        $this->getEntityManager()->flush();
    }

    public function saveBatch(array $rates): void
    {
        $em = $this->getEntityManager();

        foreach ($rates as $rate) {
            $em->persist($rate);
        }

        $em->flush();
        $em->clear();
    }
}
