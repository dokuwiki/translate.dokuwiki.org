<?php

namespace App\Command;

use App\Repository\TranslationUpdateEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Exception\ORMException;
use App\Entity\TranslationUpdateEntity;
use App\Services\Repository\Repository;
use App\Services\Repository\RepositoryManager;
use PDOException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class UpdateCommand extends Command {

    private RepositoryManager $repositoryManager;
    private LoggerInterface $logger;
    private TranslationUpdateEntityRepository $translationUpdateEntityRepository;
    private ParameterBagInterface $parameterBag;

    protected static $defaultName = 'dokuwiki:updateGit';
    protected static $defaultDescription = 'Update local git repositories (and eventually the fork) and send pending translations';

    public function __construct(TranslationUpdateEntityRepository $translationUpdateEntityRepository, RepositoryManager $repositoryManager, ParameterBagInterface $parameterBag, LoggerInterface $logger) {
        $this->repositoryManager = $repositoryManager;
        $this->translationUpdateEntityRepository = $translationUpdateEntityRepository;
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure(): void
    {
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        if (!$this->lock()) {
            $this->logger->error('Updater is already running');
            return Command::FAILURE;
        }

        try {
            $this->runUpdateOfRepositories();
            $this->processPendingTranslations();
        } catch (PDOException $e) {
            $this->logger->error('Updater had an exception occurring');
        }
        $this->unlock();
        return Command::SUCCESS;
    }

    /**
     * Update local fork and cached language files
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransportExceptionInterface
     */
    private function runUpdateOfRepositories() {
        $repositories = $this->repositoryManager->getRepositoriesToUpdate();
        foreach($repositories as $repository) {
            $repository->update();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransportExceptionInterface
     */
    private function processPendingTranslations() {
        $updates = $this->translationUpdateEntityRepository->getPendingTranslationUpdates();

        foreach ($updates as $update) {
            /**
             * @var TranslationUpdateEntity $update
             * @var Repository $repository
             */
            $repository = $this->repositoryManager->getRepository($update->getRepository());
            $repository->createAndSendPatch($update);
        }
    }

    /**
     * Try to lock the current process
     *
     * Uses a symlink to the current process in /proc (atomic operation)
     *
     * @author Radu Cristescu
     * @link http://de1.php.net/manual/en/function.getmypid.php#112782
     * @return bool false if still locked
     */
    private function lock() {
        $lockFile = $this->getLockFilePath();

        // If lock file exists, check if stale.  If exists and is not stale, return TRUE
        // else, create lock file and return FALSE.
        if(@symlink("/proc/".getmypid(), $lockFile) !== false) {
            return true;
        }

        // link already exists, check if it's stale
        if(is_link($lockFile) && !is_dir($lockFile)) {
            $this->logger->error('The updater is locked, but its PID is gone, ignoring the lock');
            $this->unlock();
            return $this->lock();
        }
        return false;
    }

    private function unlock() {
        unlink($this->getLockFilePath());
    }

    private function getLockFilePath() {
        $path = $this->parameterBag->get('app.dataDir');
        $path .= '/dokuwiki-importer.lock';
        return $path;
    }

}
