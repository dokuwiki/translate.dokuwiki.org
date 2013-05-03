<?php
namespace org\dokuwiki\translatorBundle\Services\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Services\Git\GitService;
use org\dokuwiki\translatorBundle\Services\Mail\MailService;

class RepositoryManager {

    /**
     * @var string Path to the data folder. configured in Resources/config/services.yml
     */
    private $dataFolder;

    /**
     * @var EntityManager The Symfony entity manager
     */
    private $entityManager;

    /**
     * @var RepositoryStats
     */
    private $repositoryStats;

    /**
     * @var GitService
     */
    private $gitService;

    /**
     * @var MailService
     */
    private $mailService;

    private $repositoryAgeToUpdate;
    private $maxRepositoriesToUpdatePerRun;

    function __construct($dataFolder, EntityManager $entityManager, $repositoryAgeToUpdate,
                $maxRepositoriesToUpdatePerRun, RepositoryStats $repositoryStats,
                GitService $gitService, MailService $mailService) {
        $this->dataFolder = $dataFolder;
        $this->entityManager = $entityManager;
        $this->repositoryAgeToUpdate = $repositoryAgeToUpdate;
        $this->maxRepositoriesToUpdatePerRun = $maxRepositoriesToUpdatePerRun;
        $this->repositoryStats = $repositoryStats;
        $this->gitService = $gitService;
        $this->mailService = $mailService;
    }

    public function getRepositoriesToUpdate() {
        $repositories = $this->findRepositoriesToUpdate();
        $result = array();
        foreach ($repositories as $repository) {
            $result[] = $this->getRepository($repository);
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
        $query->setParameter('timeToUpdate', time() - $this->repositoryAgeToUpdate);
        $query->setMaxResults($this->maxRepositoriesToUpdatePerRun);
        try {
            return $query->getResult();
        } catch (NoResultException $ignored) {
            return array();
        }
    }

    /**
     * @param RepositoryEntity $repository
     * @return Repository
     */
    public function getRepository(RepositoryEntity $repository) {
        if ($repository->getType() === RepositoryEntity::$TYPE_PLUGIN) {
            return new PluginRepository($this->dataFolder, $this->entityManager, $repository, $this->repositoryStats,
                    $this->gitService, $this->mailService);
        }
        return new CoreRepository($this->dataFolder, $this->entityManager, $repository, $this->repositoryStats,
                $this->gitService, $this->mailService);
    }
}
