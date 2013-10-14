<?php
namespace org\dokuwiki\translatorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use org\dokuwiki\translatorBundle\Services\DokuWikiRepositoryAPI\DokuWikiRepositoryAPI;

class UpdateDokuWikiAPICommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('dokuwiki:updateDwApi')
            ->setDescription('Update cache from dokuwiki api');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /**
         * @var $api DokuWikiRepositoryAPI
         */
        $api = $this->getContainer()->get('doku_wiki_repository_api');
        if (!$api->updateCache()) {
            $output->writeln('Update failed');
        }
    }

}
