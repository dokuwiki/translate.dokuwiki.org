<?php

namespace App\Repository;

use App\Entity\RepositoryEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Entity\TranslationUpdateEntity;
use Doctrine\Persistence\ManagerRegistry;

class TranslationUpdateEntityRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, TranslationUpdateEntity::class);
    }

    /**
     * @return TranslationUpdateEntity[]
     */
    public function getPendingTranslationUpdates() {
        $query = $this->getEntityManager()->createQuery(/** @lang DQL */
            'SELECT job
             FROM App\Entity\TranslationUpdateEntity job
             JOIN job.repository repository
             WHERE job.state = :state'
        );
        $query->setParameter('state', TranslationUpdateEntity::STATE_UNDONE);
        return $query->getResult();
    }

    public function clearUpdates(RepositoryEntity $repository)
    {
        $query = $this->getEntityManager()->createQuery(/** @lang DQL */'
            DELETE FROM App\Entity\TranslationUpdateEntity updates
            WHERE updates.repository = :repository
        ');
        $query->setParameter('repository', $repository);
        $query->execute();
    }

}
