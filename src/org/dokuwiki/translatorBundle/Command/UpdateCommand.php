<?php

namespace org\dokuwiki\translatorBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use org\dokuwiki\translatorBundle\Services\Repository;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use org\dokuwiki\translatorBundle\Services\Repository\RepositoryManager;

require_once dirname(__FILE__) . '/../../../../../lib/Git.php';
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
        $this->setupGit();
        $this->repositoryManager = $this->getContainer()->get('repository_manager');
        $repositories = $this->repositoryManager->getRepositoriesToUpdate();
        echo 'found ' . count($repositories) .' repositories';
        foreach ($repositories as $repository) {
            /**
             * @var \org\dokuwiki\translatorBundle\Services\Repository\Repository $repository
             */
            $repository->update();
        }
    }

    private function setupGit() {
        \Git::set_bin('"' . $this->getContainer()->getParameter('git_bin') . '"');
    }
}