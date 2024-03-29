<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use App\Entity\RepositoryEntity;
use Doctrine\Persistence\ManagerRegistry;

class RepositoryEntityRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, RepositoryEntity::class);
    }

    /**
     * @return RepositoryEntity
     *
     * @throws NonUniqueResultException If the query result is not unique.
     * @throws NoResultException        If the query returned no result.
     */
    public function getCoreRepository(): RepositoryEntity
    {
        $query = $this->getEntityManager()->createQuery(/** @lang DQL */
            'SELECT repository
             FROM App\Entity\RepositoryEntity repository
             WHERE repository.type = :type');

        $query->setParameter('type', RepositoryEntity::TYPE_CORE);

        return $query->getSingleResult();
    }

    /**
     * @param string $type
     * @param string $name
     * @return RepositoryEntity
     *
     * @throws NoResultException
     */
    public function getRepository($type, $name): RepositoryEntity
    {
        $repository = $this->findOneBy(['type' => $type, 'name' => $name]);
        if (!$repository) {
            throw new NoResultException();
        }
        return $repository;
    }

    /**
     * @param $language
     * @return array
     *
     * @throws NonUniqueResultException If the query result is not unique.
     */
    public function getCoreRepositoryInformation($language): array {
        $query = $this->getEntityManager()->createQuery(/** @lang DQL */
            'SELECT stats.completionPercent, repository.displayName, repository.state, repository.englishReadonly,
                        repository.errorCount
             FROM App\Entity\LanguageStatsEntity stats
             JOIN stats.repository repository
             WHERE repository.type = :type
             AND stats.language = :language');

        $query->setParameter('type', RepositoryEntity::TYPE_CORE);
        $query->setParameter('language', $language);

        try {
            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return [
                'completionPercent' => 0,
                'displayName' => 'DokuWiki', //TODO why guessing the properties for repository.* if a language does not exist?
                'state' => RepositoryEntity::STATE_ACTIVE,
                'englishReadonly' => true,
                'errorCount' => 0
            ];
        }
    }

    /**
     * @param $language
     * @return array
     */
    public function getExtensionRepositoryInformation($language): array {
        $query = $this->getEntityManager()->createQuery(/** @lang DQL */'
            SELECT stats.completionPercent, repository.name, repository.type, repository.displayName, repository.state,
                   repository.englishReadonly, repository.errorCount
            FROM App\Entity\RepositoryEntity repository
            LEFT OUTER JOIN repository.translations stats
            WITH (stats.language = :language OR stats.language IS NULL)
            WHERE repository.type != :type
            AND repository.state in (:stateActive, :stateInit)
            ORDER BY repository.popularity DESC
            '
        );

        $query->setParameter('type', RepositoryEntity::TYPE_CORE);
        $query->setParameter('stateActive', RepositoryEntity::STATE_ACTIVE);
        $query->setParameter('stateInit', RepositoryEntity::STATE_INITIALIZING);
        $query->setParameter('language', $language);

        return $query->getResult();
    }

    /**
     * @return RepositoryEntity
     *
     * @throws NoResultException If the query returned no result.
     * @throws NonUniqueResultException If the query result is not unique.
     */
    public function getCoreTranslation(): RepositoryEntity
    {
        return $this->getTranslation(RepositoryEntity::TYPE_CORE, 'dokuwiki');
    }

    /**
     * @param string $type
     * @param string $name
     * @return RepositoryEntity
     *
     * @throws NoResultException If the query returned no result.
     * @throws NonUniqueResultException If the query result is not unique.
     */
    public function getExtensionTranslation($type, $name): RepositoryEntity
    {
        return $this->getTranslation($type, $name);
    }

    /**
     * @param string $type
     * @param string $name
     * @return RepositoryEntity
     *
     * @throws NoResultException If the query returned no result.
     * @throws NonUniqueResultException If the query result is not unique.
     */
    private function getTranslation($type, $name): RepositoryEntity
    {
        $query = $this->getEntityManager()->createQuery(/** @lang DQL */'
        SELECT repository, translations, lang
            FROM App\Entity\RepositoryEntity repository
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
     * @return RepositoryEntity
     *
     * @throws NonUniqueResultException If the query result is not unique.
     * @throws NoResultException        If the query returned no result.
     */
    public function getRepositoryByNameAndActivationKey($type, $name, $activationKey): RepositoryEntity
    {
        return $this->getRepositoryByNameAndKey($type, $name, $activationKey);
    }

    /**
     * Returns editable repository, if is not waiting for approval and the key matches
     *
     * @param string $type
     * @param string $name
     * @param string $editKey
     * @return RepositoryEntity
     *
     * @throws NonUniqueResultException If the query result is not unique.
     * @throws NoResultException        If the query returned no result.
     */
    public function getRepositoryByNameAndEditKey($type, $name, $editKey): RepositoryEntity
    {
        return $this->getRepositoryByNameAndKey($type, $name, $editKey, false);
    }

    /**
     * Returns repository for matching edit or activation key if in the correct state
     *
     * @param string $type
     * @param string $name
     * @param string $key
     * @param bool $isActivation
     * @return mixed
     *
     * @throws NonUniqueResultException If the query result is not unique.
     * @throws NoResultException        If the query returned no result.
     */
    private function getRepositoryByNameAndKey($type, $name, $key, bool $isActivation = true): RepositoryEntity {
        $qb = $this->createQueryBuilder('repository')
            ->where('repository.type = :type')
            ->andWhere('repository.name = :name')
            ->andWhere('repository.activationKey = :key')

            ->setParameter('type', $type)
            ->setParameter('name', $name)
            ->setParameter('key', $key)
            ->setParameter('state', RepositoryEntity::STATE_WAITING_FOR_APPROVAL);

        //activation key & waiting, or edit key & not waiting
        if($isActivation) {
            $qb->andWhere('repository.state = :state');
        } else {
            $qb->andWhere('repository.state <> :state');
        }

        $query = $qb->getQuery();
        return $query->getSingleResult();
    }

    /**
     * Get the repositories
     *
     * @param int $maxAge in seconds
     * @param int $maxResults
     * @param int $maxErrors
     * @return RepositoryEntity[]
     */
    public function getRepositoriesToUpdate($maxAge, $maxResults, $maxErrors): array {
        $query = $this->getEntityManager()->createQuery(/** @lang DQL */
            'SELECT repository
             FROM App\Entity\RepositoryEntity repository
             WHERE repository.lastUpdate < :timeToUpdate
             AND repository.errorCount < :maxErrors
             AND repository.state IN (:active, :initializing)
             ORDER BY repository.lastUpdate ASC'
        );

        $query->setParameter('timeToUpdate', time() - $maxAge);
        $query->setParameter('maxErrors', $maxErrors);
        $query->setParameter('active', RepositoryEntity::STATE_ACTIVE);
        $query->setParameter('initializing', RepositoryEntity::STATE_INITIALIZING);

        $query->setMaxResults($maxResults);
        return $query->getResult();
    }

}
