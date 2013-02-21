<?php

namespace org\dokuwiki\translatorBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use org\dokuwiki\translatorBundle\Services\Repository;
use org\dokuwiki\translatorBundle\Services\CoreRepository;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use org\dokuwiki\translatorBundle\Services\RepositoryManager;

require_once dirname(__FILE__) . '/../../../../../lib/Git.php';
class UpdateCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('dokuwiki:updateGit')
            ->setDescription('Update git repositories');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->setupGit();
        $repositoryManager = $this->getContainer()->get('repository_manager');

        $coreRepository = $repositoryManager->getCoreRepository();
        $coreRepository->update();
    }

    private function setupGit() {
        \Git::set_bin('"' . $this->getContainer()->getParameter('git_bin') . '"');
    }
}