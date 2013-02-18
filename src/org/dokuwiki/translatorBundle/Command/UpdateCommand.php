<?php

namespace org\dokuwiki\translatorBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

require_once dirname(__FILE__) . '/../../../../../lib/Git.php';
class UpdateCommand extends Command {

    protected function configure() {
        $this->setName('dokuwiki:updateGit')
            ->setDescription('Update git repositories');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('weee.');
        $this->setupGit();
    }

    private function setupGit() {
        \Git::set_bin('"' . $this->getApplication()->getKernel()->getContainer()->getParameter('git_bin') . '"');
        $git = \Git::create($this->getApplication()->getKernel()->getContainer()->getParameter('data'));
        $git->run('remote add origin git://github.com/splitbrain/dokuwiki.git');
        $git->pull('origin', 'master');
    }

}