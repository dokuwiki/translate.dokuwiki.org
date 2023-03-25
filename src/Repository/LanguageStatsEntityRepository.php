<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\RepositoryEntity;

class LanguageStatsEntityRepository extends EntityRepository {

    public function clearStats(RepositoryEntity $repository) {
        $query = $this->getEntityManager()->createQuery('
            DELETE FROM App\Entity\LanguageStatsEntity langStats
            WHERE langStats.repository = :repository
        ');
        $query->setParameter('repository', $repository);
        $query->execute();
    }
}
