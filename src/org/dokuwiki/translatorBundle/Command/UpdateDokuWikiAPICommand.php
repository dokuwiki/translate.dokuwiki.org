<?php
namespace org\dokuwiki\translatorBundle\Command;

use org\dokuwiki\translatorBundle\Services\DokuWikiRepositoryAPI\DokuWikiRepositoryAPI;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDokuWikiAPICommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('dokuwiki:updateDwApi')
            ->setDescription('Update cache from dokuwiki api');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /**
         * @var DokuWikiRepositoryAPI $api
         */
        $api = $this->getContainer()->get('doku_wiki_repository_api');
        if (!$api->updateCache()) {
            $output->writeln('Update failed');
        }
    }

}
