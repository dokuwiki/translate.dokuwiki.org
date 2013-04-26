<?php

namespace org\dokuwiki\translatorBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

class TranslationUpdateEntityRepository extends EntityRepository {

    public function getPendingTranslationUpdates() {
        $query = $this->getEntityManager()->createQuery(
            'SELECT job
             FROM dokuwikiTranslatorBundle:TranslationUpdateEntity job
             JOIN job.repository repository
             WHERE job.state = :state'
        );
        $query->setParameter('state', TranslationUpdateEntity::$STATE_UNDONE);
        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return array();
        }
    }

}
