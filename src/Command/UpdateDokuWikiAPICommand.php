<?php
namespace App\Command;

use Doctrine\ORM\ORMException;
use App\Services\DokuWikiRepositoryAPI\DokuWikiRepositoryAPI;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDokuWikiAPICommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('dokuwiki:updateDwApi')
            ->setDescription('Update cache from dokuwiki api');
    }

    /**
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        /**
         * @var DokuWikiRepositoryAPI $api
         */
        $api = $this->getContainer()->get(DokuWikiRepositoryAPI::class);
        if (!$api->updateCache()) {
            $output->writeln('Update failed');
        }
    }

}
