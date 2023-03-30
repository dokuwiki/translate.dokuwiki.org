<?php

namespace App\Command;

use DateTime;
use Exception;
use App\Entity\RepositoryEntity;
use App\Repository\RepositoryEntityRepository;
use App\Services\Repository\RepositoryManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowStatsCommand extends Command {

    /**
     * @var RepositoryManager
     */
    private $repositoryManager;

    /**
     * @var RepositoryEntityRepository
     */
    private $repositoryEntityRepository;

    protected static $defaultName = 'dokuwiki:showStats';

    public function __construct(RepositoryEntityRepository $repositoryEntityRepository, RepositoryManager $repositoryManager) {
        $this->repositoryEntityRepository = $repositoryEntityRepository;
        $this->repositoryManager = $repositoryManager;

        parent::__construct();
    }

    protected function configure(): void {
        $this
            ->setDescription('Show some statistics for maintenance');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        /** @var RepositoryEntity[] $repositories */
        $repositories = $this->repositoryEntityRepository->findAll();

        // header
        $output->writeln('found ' . count($repositories) . ' repositories');
        $dashLine = str_repeat('-', 15-1) . ' '
            . str_repeat('-', 45-1) . ' '
            . str_repeat('-', 18-1) . ' '
            . str_repeat('-', 20-1) . ' '
            . str_repeat('-', 4-1) . ' '
            . str_repeat('-', 15-1);
        $output->writeln($dashLine);
        $output->writeln(
            sprintf('%-15s', 'name ')
            . sprintf('%-45s', 'Display name ')
            . sprintf('%-18s', 'State ')
            . sprintf('%-20s', 'Last update')
            . sprintf('%-4s', 'Cnt')
            . sprintf('%-15s', 'Error messages')
        );
        $output->writeln($dashLine);

        foreach ($repositories as $repoEntity) {
            try {
                $output->write(sprintf('%-15s', $repoEntity->getName() . ' '));
                $output->write(sprintf('%-45s', $repoEntity->getDisplayName() . ' '));
                $output->write(sprintf('%-18s', $repoEntity->getState()));

                $date = new DateTime("@{$repoEntity->getLastUpdate()}");
                $output->write($date->format('Y-m-d H:i:s') . ' ');
                $output->write(sprintf('%-4s', $repoEntity->getErrorCount() . ' '));
                $output->write(str_replace("\n", "\n   ", $repoEntity->getErrorMsg()));

                $repo = $this->repositoryManager->getRepository($repoEntity);
                if (!$repo->hasGit()) {
                    $output->write('No local checkout found');
                }
                //TODO at check if locked

                $output->writeln('');
            } catch (Exception $e) {
                $output->writeln('error ' . $e->getMessage());
            }
        }
        return 0;
    }
}
