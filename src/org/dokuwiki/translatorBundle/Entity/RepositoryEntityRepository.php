<?php

namespace org\dokuwiki\translatorBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

class RepositoryEntityRepository extends  EntityRepository {

    public function getCoreRepository() {
        $query = $this->getEntityManager()->createQuery(
            'SELECT repository
             FROM dokuwikiTranslatorBundle:RepositoryEntity repository
             WHERE repository.type = :type');

        $query->setParameter('type', RepositoryEntity::$TYPE_CORE);

        return $query->getSingleResult();
    }

    /**
     * @param string $type
     * @param string $name
     * @return RepositoryEntity
     * @throws \Doctrine\ORM\NoResultException
     */
    public function getRepository($type, $name) {
        $repository = $this->findOneBy(
            array('type' => $type, 'name' => $name)
        );
        if (!$repository) {
            throw new NoResultException();
        }
        return $repository;
    }

}
