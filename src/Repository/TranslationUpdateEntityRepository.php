<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\TranslationUpdateEntity;

class TranslationUpdateEntityRepository extends EntityRepository {

    /**
     * @return TranslationUpdateEntity[]
     */
    public function getPendingTranslationUpdates() {
        $query = $this->getEntityManager()->createQuery(
            'SELECT job
             FROM dokuwikiTranslatorBundle:TranslationUpdateEntity job
             JOIN job.repository repository
             WHERE job.state = :state'
        );
        $query->setParameter('state', TranslationUpdateEntity::$STATE_UNDONE);
        return $query->getResult();
    }

}
