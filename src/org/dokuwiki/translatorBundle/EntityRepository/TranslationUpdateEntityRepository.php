<?php

namespace org\dokuwiki\translatorBundle\EntityRepository;

use Doctrine\ORM\EntityRepository;
use org\dokuwiki\translatorBundle\EntityRepository\TranslationUpdateEntity;

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
