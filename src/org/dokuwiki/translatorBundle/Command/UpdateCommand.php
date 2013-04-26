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
            ->setDescription('Update git repositories');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        if ($this->isLock()) {
            $output->writeln('Updater is already running');
            return;
        }
        $this->lock();

        $this->repositoryManager = $this->getContainer()->get('repository_manager');

        try {
            $this->runUpdate($output);
            $this->processPendingTranslations($output);
        } catch (\PDOException $e) {
            $output->writeln('Cannot connect to database');
        }
        $this->unlock();
    }

    private function runUpdate(OutputInterface $output) {
        $repositories = $this->repositoryManager->getRepositoriesToUpdate();
        $output->writeln('found ' . count($repositories) . ' repositories');
        foreach($repositories as $repository) {
            /**
             * @var \org\dokuwiki\translatorBundle\Services\Repository\Repository $repository
             */
            $repository->update();
        }
    }

    private function processPendingTranslations(OutputInterface $output) {
        $updates = $this->getTranslationUpdateRepository()->getPendingTranslationUpdates();

        foreach ($updates as $update) {
            /**
             * @var TranslationUpdateEntity $update
             */
            $repository = $this->repositoryManager->getRepository($update->getRepository());
            $repository->createAndSendPatch($update);
        }
    }

    private function lock() {
        touch($this->getLockFilePath());
    }

    private function unlock() {
        unlink($this->getLockFilePath());
    }

    private function isLock() {
        return (file_exists($this->getLockFilePath()));
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