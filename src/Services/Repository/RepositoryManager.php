<?php
namespace App\Services\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\RepositoryEntity;
use App\Repository\RepositoryEntityRepository;
use App\Services\Git\GitService;
use App\Services\GitHub\GitHubService;
use App\Services\GitHub\GitHubStatusService;
use App\Services\Mail\MailService;
use App\Services\Repository\Behavior\GitHubBehavior;
use App\Services\Repository\Behavior\PlainBehavior;
use App\Services\Repository\Behavior\RepositoryBehavior;
use Psr\Log\LoggerInterface;

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

    /**
     * @var RepositoryEntityRepository
     */
    private $repositoryRepository;

    /**
     * @var GitHubService
     */
    private $gitHubService;

    /**
     * @var GitHubStatusService
     */
    private $gitHubStatus;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $maxErrors;
    private $repositoryAgeToUpdate;
    private $maxRepositoriesToUpdatePerRun;

    function __construct($dataFolder, EntityManagerInterface $entityManager, $repositoryAgeToUpdate,
                $maxRepositoriesToUpdatePerRun, RepositoryStats $repositoryStats,
                GitService $gitService, MailService $mailService, GitHubService $gitHubService,
                LoggerInterface $logger, $maxErrors, GitHubStatusService $gitHubStatus) {

        $this->dataFolder = $dataFolder;
        $this->entityManager = $entityManager;
        $this->repositoryAgeToUpdate = $repositoryAgeToUpdate;
        $this->maxRepositoriesToUpdatePerRun = $maxRepositoriesToUpdatePerRun;
        $this->repositoryStats = $repositoryStats;
        $this->gitService = $gitService;
        $this->mailService = $mailService;
        $this->repositoryRepository = $entityManager->getRepository(RepositoryEntity::class);
        $this->gitHubService = $gitHubService;
        $this->logger = $logger;
        $this->maxErrors = $maxErrors;
        $this->gitHubStatus = $gitHubStatus;
    }

    /**
     * Returns Repositories that need creation/update of local repository forks
     *
     * @return Repository[]
     */
    public function getRepositoriesToUpdate() {
        $repositories = $this->findRepositoriesToUpdate();
        $result = array();
        foreach ($repositories as $repository) {
            $result[] = $this->getRepository($repository);
        }

        return $result;
    }

    /**
     * Find RepositoryEntities to create/update local repository forks (checks for last update date, max per update and max errors per repository)
     *
     * @return RepositoryEntity[]
     */
    private function findRepositoriesToUpdate() {
        return $this->repositoryRepository->getRepositoriesToUpdate($this->repositoryAgeToUpdate, $this->maxRepositoriesToUpdatePerRun, $this->maxErrors);
    }

    /**
     * Retrieve Repository for given RepositoryEntity
     *
     * @param RepositoryEntity $repository
     * @return Repository
     */
    public function getRepository(RepositoryEntity $repository) {
        $behavior = $this->getRepositoryBehavior($repository);

        if ($repository->getType() === RepositoryEntity::$TYPE_PLUGIN) {
            return new PluginRepository($this->dataFolder, $this->entityManager, $repository, $this->repositoryStats,
                    $this->gitService, $behavior, $this->logger, $this->mailService);
        }
        if ($repository->getType() === RepositoryEntity::$TYPE_TEMPLATE) {
            return new TemplateRepository($this->dataFolder, $this->entityManager, $repository, $this->repositoryStats,
                                        $this->gitService, $behavior, $this->logger, $this->mailService);
        }
        return new CoreRepository($this->dataFolder, $this->entityManager, $repository, $this->repositoryStats,
                $this->gitService, $behavior, $this->logger, $this->mailService);
    }

    /**
     * Retrieve RepositoryBehavior for given RepositoryEntity
     *
     * @param RepositoryEntity $repository
     * @return RepositoryBehavior
     */
    private function getRepositoryBehavior(RepositoryEntity $repository) {
        $url = $repository->getUrl();
        if (preg_match('/^(git:\/\/|https:\/\/|git@)github\.com/i', $url)) {
            return new GitHubBehavior($this->gitHubService, $this->gitHubStatus);
        }
        return new PlainBehavior($this->mailService);
    }
}
