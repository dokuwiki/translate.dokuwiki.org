<?php

namespace org\dokuwiki\translatorBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use org\dokuwiki\translatorBundle\Entity\TranslationUpdateEntity;
use org\dokuwiki\translatorBundle\Entity\TranslationUpdateEntityRepository;
use org\dokuwiki\translatorBundle\Services\Repository;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use org\dokuwiki\translatorBundle\Services\Repository\RepositoryManager;

class UpdateCommand extends ContainerAwareCommand {

    /**
     * @var RepositoryManager
     */
    private $repositoryManager;

    protected function configure() {
        $this->setName('dokuwiki:updateGit')
            ->setDescription('Update local git repositories and send pending translations');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        if (!$this->lock()) {
            $this->getContainer()->get('logger')->error('Updater is already running');
            return;
        }

        $this->repositoryManager = $this->getContainer()->get('repository_manager');

        try {
            $this->runUpdate();
            $this->processPendingTranslations();
        } catch (\PDOException $e) {
            $this->getContainer()->get('logger')->error('Updater had an exception occurring');
        }
        $this->unlock();

        $transport = $this->getContainer()->get('mailer')->getTransport();
        if (!$transport instanceof \Swift_Transport_SpoolTransport) {
            return;
        }

        $spool = $transport->getSpool();
        if (!$spool instanceof \Swift_MemorySpool) {
            return;
        }

        $spool->flushQueue($this->getContainer()->get('swiftmailer.transport.real'));
    }

    private function runUpdate() {
        $repositories = $this->repositoryManager->getRepositoriesToUpdate();
        foreach($repositories as $repository) {
            /**
             * @var \org\dokuwiki\translatorBundle\Services\Repository\Repository $repository
             */
            $repository->update();
        }
    }

    private function processPendingTranslations() {
        $updates = $this->getTranslationUpdateRepository()->getPendingTranslationUpdates();

        foreach ($updates as $update) {
            /**
             * @var TranslationUpdateEntity $update
             * @var \org\dokuwiki\translatorBundle\Services\Repository\Repository $repository
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
        $lockfile = $this->getLockFilePath();

        // If lock file exists, check if stale.  If exists and is not stale, return TRUE
        // else, create lock file and return FALSE.
        if(@symlink("/proc/".getmypid(), $lockfile) !== false)
            return true;

        // link already exists, check if it's stale
        if(is_link($lockfile) && !is_dir($lockfile)) {
            $this->getContainer()->get('logger')->err('The updater is locked, but its PID is gone, ignoring the lock');
            $this->unlock();
            return $this->lock();
        }
        return false;
    }

    private function unlock() {
        unlink($this->getLockFilePath());
    }

    private function getLockFilePath() {
        $path = $this->getContainer()->getParameter('data');
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