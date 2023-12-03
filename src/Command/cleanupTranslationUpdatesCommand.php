<?php

namespace App\Command;

use Doctrine\ORM\NoResultException;
use Exception;
use App\Entity\RepositoryEntity;
use App\Repository\RepositoryEntityRepository;
use App\Services\Repository\RepositoryManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class cleanupTranslationUpdatesCommand extends Command
{

    private RepositoryManager $repositoryManager;
    private RepositoryEntityRepository $repositoryEntityRepository;

    protected static $defaultName = 'dokuwiki:cleanupTranslationUpdates';
    protected static $defaultDescription = 'Deletes incomplete, sent or failed (older than 1 day) translation updates (<info>all</info> or per repository)';

    public function __construct(RepositoryManager $repositoryManager, RepositoryEntityRepository $repositoryEntityRepository)
    {
        $this->repositoryManager = $repositoryManager;
        $this->repositoryEntityRepository = $repositoryEntityRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('type', InputArgument::REQUIRED, '<info>template</info>, <info>plugin</info> or <info>core</info>. Or <info>all</info> for cleaning all repositories; <info>all-dry</info> shows summary without deleting.')
            ->addArgument('name', InputArgument::OPTIONAL, 'repository name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repositories = [];
        $type = $input->getArgument('type');
        $name = $input->getArgument('name');


        if (isset($type) && isset($name)) {
            $repositoryTypes = [
                RepositoryEntity::TYPE_CORE,
                RepositoryEntity::TYPE_PLUGIN,
                RepositoryEntity::TYPE_TEMPLATE
            ];
            if (!in_array($type, $repositoryTypes)) {
                $output->writeln(sprintf(
                    'Type must be <info>%s</info>, <info>%s</info> or <info>%s</info>',
                    RepositoryEntity::TYPE_CORE,
                    RepositoryEntity::TYPE_PLUGIN,
                    RepositoryEntity::TYPE_TEMPLATE
                ));
                return Command::INVALID;
            }
            try {
                $repo = $this->repositoryEntityRepository->getRepository($type, $name);
            } catch (NoResultException $e) {
                $output->writeln('nothing found');
                return Command::FAILURE;
            }
            $repositories[] = $repo;
        } else {
            if ($type === 'all' || $type === 'all-dry') {
                /** @var RepositoryEntity[] $repositories */
                $repositories = $this->repositoryEntityRepository->findAll();
            } else {
                $output->writeln('Not recognized. Use <info>all</info>/<info>all-dry</info> to confirm all repositories must be '
                    . 'cleaned, or provide <info><type> <name></info> of the repository to clean.');
                return Command::INVALID;
            }
        }

        /** @var RepositoryEntity[] $repositories */
        $output->writeln('found ' . count($repositories) . ' repositories');
        foreach ($repositories as $repoEntity) {
            try {
                $output->write($repoEntity->getDisplayName() . ' ... ');
                $repo = $this->repositoryManager->getRepository($repoEntity);

                if (!$repo->hasGit()) {
                    $output->writeln('no local checkout found - skipping');
                    continue;
                }

                if ($summary = $repo->removeOldTranslationUpdates($type === 'all-dry')) {
                    $output->write("\n<comment>");
                    $output->writeln($summary);
                    $output->write('</comment>');
                }

                $output->writeln('<info>done</info>');
            } catch (Exception $e) {
                $output->writeln('<error>error ' . $e->getMessage() . '</error>');
            }
        }
        return Command::SUCCESS;
    }
}