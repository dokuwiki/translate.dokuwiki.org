<?php
namespace org\dokuwiki\translatorBundle\Services\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;

class RepositoryManager {

    /**
     * @var string Path to the data folder. configured in Resources/config/services.yml
     */
    private $dataFolder;

    /**
     * @var EntityManager The Symfony entity manager
     */
    private $entityManager;

    function __construct($dataFolder, EntityManager $entityManager) {
        $this->dataFolder = $dataFolder;
        $this->entityManager = $entityManager;
    }

    public function getRepositoriesToUpdate() {
        $repositories = $this->findRepositoriesToUpdate();
        $result = array();
        foreach ($repositories as $repository) {
            /**
             * @var RepositoryEntity $repository
             */
            if ($repository->getType() === Repository::$TYPE_CORE) {
                $result[] = new CoreRepository($this->dataFolder, $this->entityManager, $repository);
            }
        }

        return $result;
    }

    private function findRepositoriesToUpdate() {
        $query = $this->entityManager->createQuery(
            'SELECT repository
             FROM dokuwikiTranslatorBundle:RepositoryEntity repository
             WHERE repository.lastUpdate < :timeToUpdate
             ORDER BY repository.lastUpdate ASC'
        );
        $query->setParameter('timeToUpdate', time() - 60*60*24);
        $query->setMaxResults(10);
        try {
            return $query->getResult();
        } catch (NoResultException $ignored) {
            return array();
        }
    }
}
