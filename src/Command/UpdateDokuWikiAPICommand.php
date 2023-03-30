<?php
namespace App\Command;

use Doctrine\ORM\ORMException;
use App\Services\DokuWikiRepositoryAPI\DokuWikiRepositoryAPI;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDokuWikiAPICommand extends Command {

    /**
     * @var DokuWikiRepositoryAPI
     */
    private $api;

    protected static $defaultName = 'dokuwiki:updateDwApi';

    public function __construct(DokuWikiRepositoryAPI $dokuWikiRepositoryAPI) {
        $this->api = $dokuWikiRepositoryAPI;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Update cache from dokuwiki api');
    }

    /**
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->api->updateCache()) {
            $output->writeln('Update failed');
            return 1;
        }
        return 0;
    }

}
