<?php

namespace org\dokuwiki\translatorBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use App\Entity\TranslationUpdateEntity;
use org\dokuwiki\translatorBundle\EntityRepository\TranslationUpdateEntityRepository;
use org\dokuwiki\translatorBundle\Services\Repository\Repository;
use org\dokuwiki\translatorBundle\Services\Repository\RepositoryManager;
use PDOException;
use Swift_MemorySpool;
use Swift_Transport_SpoolTransport;
use Swift_TransportException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends ContainerAwareCommand {

    /**
     * @var RepositoryManager
     */
    private $repositoryManager;

    protected function configure() {
        $this->setName('dokuwiki:updateGit')
            ->setDescription('Update local git repositories and send pending translations');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     *
     * @throws OptimisticLockException
     * @throws Swift_TransportException
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        if (!$this->lock()) {
            $this->getContainer()->get('logger')->error('Updater is already running');
            return;
        }

        $this->repositoryManager = $this->getContainer()->get(RepositoryManager::class);

        try {
            $this->runUpdate();
            $this->processPendingTranslations();
        } catch (PDOException $e) {
            $this->getContainer()->get('logger')->error('Updater had an exception occurring');
        }
        $this->unlock();

        $transport = $this->getContainer()->get('mailer')->getTransport();
        if (!$transport instanceof Swift_Transport_SpoolTransport) {
            return;
        }

        $spool = $transport->getSpool();
        if (!$spool instanceof Swift_MemorySpool) {
            return;
        }

        $spool->flushQueue($this->getContainer()->get('swiftmailer.transport.real'));
    }

    /**
     * Run the
     *
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function runUpdate() {
        $repositories = $this->repositoryManager->getRepositoriesToUpdate();
        foreach($repositories as $repository) {
            /**
             * @var Repository $repository
             */
            $repository->update();
        }
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function processPendingTranslations() {
        $updates = $this->getTranslationUpdateRepository()->getPendingTranslationUpdates();

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
            $this->getContainer()->get('logger')->error('The updater is locked, but its PID is gone, ignoring the lock');
            $this->unlock();
            return $this->lock();
        }
        return false;
    }

    private function unlock() {
        unlink($this->getLockFilePath());
    }

    private function getLockFilePath() {
        $path = $this->getContainer()->getParameter('app.dataDir');
        $path .= '/dokuwiki-importer.lock';
        return $path;
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager() {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return TranslationUpdateEntityRepository
     */
    private function getTranslationUpdateRepository() {
        return $this->getEntityManager()->getRepository('dokuwikiTranslatorBundle:TranslationUpdateEntity');
    }
}
