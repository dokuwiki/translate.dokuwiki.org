<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use App\Entity\LanguageStatsEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Entity\RepositoryEntity;
use Doctrine\Persistence\ManagerRegistry;

class LanguageStatsEntityRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, LanguageStatsEntity::class);
    }

    public function clearStats(RepositoryEntity $repository): void {
        $query = $this->getEntityManager()->createQuery(/** @lang DQL */'
            DELETE FROM App\Entity\LanguageStatsEntity langStats
            WHERE langStats.repository = :repository
        ');
        $query->setParameter('repository', $repository);
        $query->execute();
    }
}
