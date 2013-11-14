<?php

namespace org\dokuwiki\translatorBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;

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

    public function getCoreRepositoryInformation($language) {
        $query = $this->getEntityManager()->createQuery(
            'SELECT stats.completionPercent, repository.displayName, repository.state
             FROM dokuwikiTranslatorBundle:LanguageStatsEntity stats
             JOIN stats.repository repository
             WHERE repository.type = :type
             AND stats.language = :language');

        $query->setParameter('type', RepositoryEntity::$TYPE_CORE);
        $query->setParameter('language', $language);

        try {
            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return array(
                'completionPercent' => 0,
                'displayName' => 'DokuWiki',
                'state' => RepositoryEntity::$STATE_ACTIVE
            );
        }
    }

    public function getPluginRepositoryInformation($language) {
        $query = $this->getEntityManager()->createQuery('
            SELECT stats.completionPercent, repository.name, repository.displayName, repository.state
            FROM dokuwikiTranslatorBundle:RepositoryEntity repository
            LEFT OUTER JOIN repository.translations stats
            WITH (stats.language = :language OR stats.language IS NULL)
            WHERE repository.type != :type
            AND repository.state in (:stateActive, :stateInit)
            ORDER BY repository.popularity DESC
            '
        );

        $query->setParameter('type', RepositoryEntity::$TYPE_CORE);
        $query->setParameter('stateActive', RepositoryEntity::$STATE_ACTIVE);
        $query->setParameter('stateInit', RepositoryEntity::$STATE_INITIALIZING);
        $query->setParameter('language', $language);

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return array();
        }
    }

    public function getCoreTranslation() {
        return $this->getTranslation('dokuwiki', RepositoryEntity::$TYPE_CORE);
    }

    public function getPluginTranslation($name) {
        return $this->getTranslation($name, RepositoryEntity::$TYPE_PLUGIN);
    }

    private function getTranslation($name, $type) {
        $query = $this->getEntityManager()->createQuery('
        SELECT repository, translations, lang
            FROM dokuwikiTranslatorBundle:RepositoryEntity repository
            JOIN repository.translations translations
            JOIN translations.language lang
            WHERE repository.type = :type
            AND repository.name = :name
        ');

        $query->setParameter('type', $type);
        $query->setParameter('name', $name);

        return $query->getSingleResult();
    }


    public function getRepositoryByNameAndActivationKey($name, $activationKey) {
        $query = $this->getEntityManager()->createQuery(
            'SELECT repository
             FROM dokuwikiTranslatorBundle:RepositoryEntity repository
             WHERE repository.name = :name
             AND repository.activationKey = :key
             AND repository.state = :state'
        );
        $query->setParameter('name', $name);
        $query->setParameter('key', $activationKey);
        $query->setParameter('state', RepositoryEntity::$STATE_WAITING_FOR_APPROVAL);
        return $query->getSingleResult();
    }

    public function getRepositoriesToUpdate($maxAge, $maxResults, $maxErrors) {
        $query = $this->getEntityManager()->createQuery(
            'SELECT repository
             FROM dokuwikiTranslatorBundle:RepositoryEntity repository
             WHERE repository.lastUpdate < :timeToUpdate
             AND repository.errorCount < :maxErrors
             AND repository.state IN (:active, :initializing)
             ORDER BY repository.lastUpdate ASC'
        );

        $query->setParameter('timeToUpdate', time() - $maxAge);
        $query->setParameter('maxErrors', $maxErrors);
        $query->setParameter('active', RepositoryEntity::$STATE_ACTIVE);
        $query->setParameter('initializing', RepositoryEntity::$STATE_INITIALIZING);

        $query->setMaxResults($maxResults);
        return $query->getResult();
    }

}
