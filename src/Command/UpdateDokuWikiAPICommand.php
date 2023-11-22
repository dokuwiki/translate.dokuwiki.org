<?php
namespace App\Command;

use Doctrine\ORM\Exception\ORMException;
use App\Services\DokuWikiRepositoryAPI\DokuWikiRepositoryAPI;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDokuWikiAPICommand extends Command {

    private DokuWikiRepositoryAPI $api;

    protected static $defaultName = 'dokuwiki:updateDwApi';
    protected static $defaultDescription = 'Update cache from dokuwiki api';

    public function __construct(DokuWikiRepositoryAPI $dokuWikiRepositoryAPI) {
        $this->api = $dokuWikiRepositoryAPI;

        parent::__construct();
    }

    protected function configure(): void
    {
    }

    /**
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->api->updateCache()) {
            $output->writeln('Update failed');
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

}
