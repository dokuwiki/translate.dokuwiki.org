<?php

namespace App\Repository;

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

}
