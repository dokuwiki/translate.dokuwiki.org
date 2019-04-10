<?php

namespace org\dokuwiki\translatorBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;

class RepositoryEntityRepository extends  EntityRepository {

    /**
     * @return RepositoryEntity
     */
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
     *
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

    /**
     * @param $language
     * @return array
     */
    public function getCoreRepositoryInformation($language) {
        $query = $this->getEntityManager()->createQuery(
            'SELECT stats.completionPercent, repository.displayName, repository.state, repository.englishReadonly
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
                'state' => RepositoryEntity::$STATE_ACTIVE,
                'englishReadonly' => true
            );
        }
    }

    /**
     * @param $language
     * @return array
     */
    public function getExtensionRepositoryInformation($language) {
        $query = $this->getEntityManager()->createQuery('
            SELECT stats.completionPercent, repository.name, repository.type, repository.displayName, repository.state, repository.englishReadonly
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

        return $query->getResult();
    }

    /**
     * @return array
     */
    public function getCoreTranslation() {
        return $this->getTranslation(RepositoryEntity::$TYPE_CORE, 'dokuwiki');
    }

    /**
     * @param $type
     * @param $name
     * @return array
     */
    public function getExtensionTranslation($type, $name) {
        return $this->getTranslation($type, $name);
    }

    /**
     * @param $type
     * @param $name
     * @return array
     */
    private function getTranslation($type, $name) {
        $query = $this->getEntityManager()->createQuery('
        SELECT repository, translations, lang
            FROM dokuwikiTranslatorBundle:RepositoryEntity repository
            LEFT OUTER JOIN repository.translations translations
            LEFT OUTER JOIN translations.language lang
            WHERE repository.type = :type
            AND repository.name = :name
        ');

        $query->setParameter('type', $type);
        $query->setParameter('name', $name);

        return $query->getSingleResult();
    }

    /**
     * Returns repository if waiting for approval and the key matches
     *
     * @param string $type
     * @param string $name
     * @param string $activationKey
     * @return \org\dokuwiki\translatorBundle\Entity\RepositoryEntity
     */
    public function getRepositoryByNameAndActivationKey($type, $name, $activationKey) {
        return $this->getRepositoryByNameAndKey($type, $name, $activationKey, $activation = true);
    }

    /**
     * Returns editable repository, if is not waiting for approval and the key matches
     *
     * @param string $type
     * @param string $name
     * @param string $editKey
     * @return \org\dokuwiki\translatorBundle\Entity\RepositoryEntity
     */
    public function getRepositoryByNameAndEditKey($type, $name, $editKey) {
        return $this->getRepositoryByNameAndKey($type, $name, $editKey, $activation = false);
    }

    /**
     * Returns repository for matching edit or activation key
     *
     * @param string $type
     * @param string $name
     * @param string $key
     * @param bool $activation
     * @return mixed
     */
    private function getRepositoryByNameAndKey($type, $name, $key, $activation = true) {
        $operator = ($activation ? '=' : '<>');

        $query = $this->getEntityManager()->createQuery(
            "SELECT repository
             FROM dokuwikiTranslatorBundle:RepositoryEntity repository
             WHERE repository.type = :type
             AND repository.name = :name
             AND repository.activationKey = :key
             AND repository.state $operator :state "
        );
        $query->setParameter('type', $type);
        $query->setParameter('name', $name);
        $query->setParameter('key', $key);
        $query->setParameter('state', RepositoryEntity::$STATE_WAITING_FOR_APPROVAL);
        return $query->getSingleResult();
    }

    /**
     * @param $maxAge
     * @param $maxResults
     * @param $maxErrors
     * @return \org\dokuwiki\translatorBundle\Entity\RepositoryEntity[]
     */
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
