<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use App\Entity\TranslationUpdateEntity;
use App\Services\Repository\Repository;
use App\Services\Repository\RepositoryManager;
use PDOException;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Swift_MemorySpool;
use Swift_Transport;
use Swift_Transport_SpoolTransport;
use Swift_TransportException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class UpdateCommand extends Command {

    /**
     * @var RepositoryManager
     */
    private $repositoryManager;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;
    /**
     * @var Swift_Mailer
     */
    private $mailer;
    /**
     * @var Swift_Transport
     */
    private $transport;

    protected static $defaultName = 'dokuwiki:updateGit';
    protected static $defaultDescription = 'Update local git repositories and send pending translations';

    public function __construct(EntityManagerInterface $entityManager, RepositoryManager $repositoryManager, ParameterBagInterface $parameterBag, LoggerInterface $logger, Swift_Mailer $mailer, Swift_Transport $transport) {
        $this->entityManager = $entityManager;
        $this->repositoryManager = $repositoryManager;
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->transport = $transport;

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
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws Swift_TransportException
     * @throws SyntaxError
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

        $transport = $this->mailer->getTransport();
        if (!$transport instanceof Swift_Transport_SpoolTransport) {
            return Command::SUCCESS;
        }

        $spool = $transport->getSpool();
        if (!$spool instanceof Swift_MemorySpool) {
            return Command::SUCCESS;
        }

        $spool->flushQueue($this->transport);
        return Command::SUCCESS;
    }

    /**
     * Update local fork and cached language files
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function runUpdateOfRepositories() {
        $repositories = $this->repositoryManager->getRepositoriesToUpdate();
        foreach($repositories as $repository) {
            $repository->update();
        }
    }

    /**
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function processPendingTranslations() {
        $updates = $this->entityManager->getRepository(TranslationUpdateEntity::class)
            ->getPendingTranslationUpdates();

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
        if(@symlink("/proc/".getmypid(), $lockFile) !== false)
            return true;

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
