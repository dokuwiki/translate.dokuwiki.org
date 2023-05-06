<?php

namespace App\Command;

use Exception;
use App\Entity\RepositoryEntity;
use App\Repository\RepositoryEntityRepository;
use App\Services\Repository\RepositoryManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateLanguagesCommand extends Command {

    private RepositoryManager $repositoryManager;
    private RepositoryEntityRepository $repositoryEntityRepository;

    protected static $defaultName = 'dokuwiki:updateLanguages';
    protected static $defaultDescription = 'Updates all language information from local repository. Refreshes the cached translation objects';

    public function __construct(RepositoryManager $repositoryManager, RepositoryEntityRepository $repositoryEntityRepository)
    {
        $this->repositoryManager = $repositoryManager;
        $this->repositoryEntityRepository = $repositoryEntityRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var RepositoryEntity[] $repositories */
        $repositories = $this->repositoryEntityRepository->findAll();
        $output->writeln('found ' . count($repositories) . ' repositories');
        foreach ($repositories as $repoEntity) {
            try {
                $output->write('creating ' . $repoEntity->getDisplayName() . ' ... ');
                $repo = $this->repositoryManager->getRepository($repoEntity);
                if (!$repo->hasGit()) {
                    $output->writeln('no local checkout found - skipping');
                    continue;
                }
                $repo->updateLanguage();
                $output->writeln('done');
            } catch (Exception $e) {
                $output->writeln('error ' . $e->getMessage());
            }
        }
        return Command::SUCCESS;
    }
}