<?php

namespace org\dokuwiki\translatorBundle\Entity;

use Doctrine\ORM\EntityRepository;

class RepositoryEntityRepository extends  EntityRepository {

    public function getCoreRepository() {
        $query = $this->getEntityManager()->createQuery(
            'SELECT repository
             FROM dokuwikiTranslatorBundle:RepositoryEntity repository
             WHERE repository.type = :type');

        $query->setParameter('type', RepositoryEntity::$TYPE_CORE);

        return $query->getSingleResult();
    }

}
