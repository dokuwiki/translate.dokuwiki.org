<?php

namespace App\Command;

use DateTime;
use Doctrine\ORM\NoResultException;
use Exception;
use App\Entity\RepositoryEntity;
use App\Repository\RepositoryEntityRepository;
use App\Services\Repository\RepositoryManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
            ->setDescription('Show some statistics for maintenance')
            ->addArgument('type', InputArgument::OPTIONAL, 'template, plugin or core')
            ->addArgument('name', InputArgument::OPTIONAL, 'repository name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $type = $input->getArgument('type');

        if(isset($name) && isset($type)) {
            $repositoryTypes = [
                RepositoryEntity::$TYPE_CORE,
                RepositoryEntity::$TYPE_PLUGIN,
                RepositoryEntity::$TYPE_TEMPLATE
            ];
            if (!in_array($type, $repositoryTypes)) {
                $output->writeln(sprintf(
                    'Type must be %s, %s or %s',
                    RepositoryEntity::$TYPE_CORE,
                    RepositoryEntity::$TYPE_PLUGIN,
                    RepositoryEntity::$TYPE_TEMPLATE
                ));
                return 1;
            }
            try {
                $repo = $this->repositoryEntityRepository->getRepository($type, $name);
            } catch (NoResultException $e) {
                $output->writeln('nothing found');
                return 1;
            }
            $repositories[] = $repo;
        } else {
            /** @var RepositoryEntity[] $repositories */
            $repositories = $this->repositoryEntityRepository->findAll();
        }

        // header
        $output->writeln('found ' . count($repositories) . ' repositories');
        $dashLine = str_repeat('-', 9-1) . ' '
            . str_repeat('-', 15-1) . ' '
            . str_repeat('-', 35-1) . ' '
            . str_repeat('-', 18-1) . ' '
            . str_repeat('-', 20-1) . ' '
            . str_repeat('-', 4-1) . ' '
            . str_repeat('-', 15-1);
        $output->writeln($dashLine);
        $output->writeln(
            sprintf('%-9s', 'Type ')
            . sprintf('%-15s', 'Name ')
            . sprintf('%-35s', 'Display name ')
            . sprintf('%-18s', 'State ')
            . sprintf('%-20s', 'Last update')
            . sprintf('%-4s', 'Cnt')
            . sprintf('%-15s', 'Error messages')
        );
        $output->writeln($dashLine);

        $count = count($repositories);
        foreach ($repositories as $repoEntity) {
            try {
                $output->write(sprintf('%-9s', $repoEntity->getType() . ' '));
                $output->write(sprintf('%-15s', $repoEntity->getName() . ' '));
                $output->write(sprintf('%-35s', substr($repoEntity->getDisplayName() . ' ', 0, 34)));
                $output->write(sprintf('%-18s', $repoEntity->getState()));

                $date = new DateTime("@{$repoEntity->getLastUpdate()}");
                $output->write($date->format('Y-m-d H:i:s') . ' ');
                $output->write(sprintf('%-4s', $repoEntity->getErrorCount() . ' '));
                $errorMsg = str_replace("\n", "\n   ", $repoEntity->getErrorMsg());
                if($count > 1) {
                    $errorMsg = strlen($errorMsg) > 50 ? substr($errorMsg,0,50) . ' (more..) ': $errorMsg;
                } else {
                    $errorMsg = "see below\n   " . $errorMsg;
                }
                $output->write($errorMsg);


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
