<?php

namespace App\Services\Repository;

use App\Services\GitLab\GitLabService;
use App\Services\GitLab\GitLabStatusService;
use App\Services\Repository\Behavior\GitLabBehavior;
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

class RepositoryManager
{

    /**
     * @var string Path to the data folder. Configured in .env/.env.local/etc files
     */
    private string $dataFolder;
    private RepositoryStats $repositoryStats;
    private GitService $gitService;
    private MailService $mailService;
    /**
     * @var EntityManager
     */
    private EntityManagerInterface $entityManager;
    private RepositoryEntityRepository $repositoryRepository;
    private GitHubService $gitHubService;
    private GitHubStatusService $gitHubStatus;
    private LoggerInterface $logger;
    private int $maxErrors;
    private int $repositoryAgeToUpdate;
    private int $maxRepositoriesToUpdatePerRun;
    private GitLabService $gitLabService;
    private GitLabStatusService $gitLabStatus;


    function __construct(string $dataFolder, int $repositoryAgeToUpdate, int $maxErrors, int $maxRepositoriesToUpdatePerRun,
                         EntityManagerInterface $entityManager, RepositoryStats $repositoryStats,
                         GitService $gitService, MailService $mailService, LoggerInterface $logger,
                         GitHubService $gitHubService, GitHubStatusService $gitHubStatus,
                         GitLabService $gitLabService, GitLabStatusService $gitLabStatus
    )
    {

        $this->dataFolder = $dataFolder;
        $this->entityManager = $entityManager;
        $this->repositoryAgeToUpdate = $repositoryAgeToUpdate;
        $this->maxErrors = $maxErrors;
        $this->maxRepositoriesToUpdatePerRun = $maxRepositoriesToUpdatePerRun;
        $this->repositoryStats = $repositoryStats;
        $this->gitService = $gitService;
        $this->mailService = $mailService;
        $this->repositoryRepository = $entityManager->getRepository(RepositoryEntity::class);
        $this->logger = $logger;
        $this->gitHubService = $gitHubService;
        $this->gitHubStatus = $gitHubStatus;
        $this->gitLabService = $gitLabService;
        $this->gitLabStatus = $gitLabStatus;
    }

    /**
     * Returns Repositories that need creation/update of local repository forks
     *
     * @return Repository[]
     */
    public function getRepositoriesToUpdate(): array
    {
        $repositories = $this->findRepositoriesToUpdate();
        $result = [];
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
    private function findRepositoriesToUpdate(): array
    {
        return $this->repositoryRepository->getRepositoriesToUpdate($this->repositoryAgeToUpdate, $this->maxRepositoriesToUpdatePerRun, $this->maxErrors);
    }

    /**
     * Retrieve Repository for given RepositoryEntity
     *
     * @param RepositoryEntity $repository
     * @return Repository
     */
    public function getRepository(RepositoryEntity $repository): Repository
    {
        $behavior = $this->getRepositoryBehavior($repository);

        if ($repository->getType() === RepositoryEntity::TYPE_PLUGIN) {
            return new PluginRepository($this->dataFolder, $this->entityManager, $repository, $this->repositoryStats,
                $this->gitService, $behavior, $this->logger, $this->mailService);
        }
        if ($repository->getType() === RepositoryEntity::TYPE_TEMPLATE) {
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
    private function getRepositoryBehavior(RepositoryEntity $repository): RepositoryBehavior
    {
        $url = $repository->getUrl();
        if (preg_match('/^(git:\/\/|https:\/\/|git@)github\.com/i', $url)) {
            return new GitHubBehavior($this->gitHubService, $this->gitHubStatus);
        }
        if (preg_match('/^(git:\/\/|https:\/\/|git@)gitlab\.com/i', $url)) {
            //build manually as Repository is not yet available..
            $repoFolder = $this->dataFolder . '/gitlab_projectids/' . $repository->getType() . '/' . $repository->getName() . '/';
            $this->gitLabService->setProjectIdFolder($repoFolder);
            return new GitLabBehavior($this->gitLabService, $this->gitLabStatus);
        }
        return new PlainBehavior($this->mailService);
    }
}
