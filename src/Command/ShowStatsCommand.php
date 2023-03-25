<?php

namespace org\dokuwiki\translatorBundle\Command;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Exception;
use App\Entity\RepositoryEntity;
use App\Repository\RepositoryEntityRepository;
use org\dokuwiki\translatorBundle\Services\Repository\RepositoryManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowStatsCommand extends ContainerAwareCommand {

    /**
     * @var RepositoryManager
     */
    private $repositoryManager;

    /**
     * @var RepositoryEntityRepository
     */
    private $repositoryEntityRepository;

    public function __construct(Registry $doctrine, RepositoryManager $repositoryManager) {

        $this->repositoryEntityRepository = $doctrine->getManager()->getRepository(RepositoryEntity::class);
        $this->repositoryManager = $repositoryManager;
        parent::__construct();
    }
    protected function configure() {
        $this->setName('dokuwiki:showStats')
            ->setDescription('Show some statistics for maintenance');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        /** @var \App\Entity\RepositoryEntity[] $repositories */
        $repositories = $this->repositoryEntityRepository->findAll();
        $output->writeln('found ' . count($repositories) . ' repositories');
        foreach ($repositories as $repoEntity) {
            try {
                $output->write(sprintf('%-45s', 'creating ' . $repoEntity->getDisplayName() . ' '));
                $output->write(sprintf('%-18s', $repoEntity->getState()));

                $date = new DateTime("@{$repoEntity->getLastUpdate()}");
                $output->write($date->format('Y-m-d H:i:s') . ' ');
                $output->write('cnt:' . $repoEntity->getErrorCount() . ' ' . str_replace("\n", "\n   ", $repoEntity->getErrorMsg()));

                $repo = $this->repositoryManager->getRepository($repoEntity);
                if (!$repo->hasGit()) {
                    $output->writeln('- no local checkout found');
                    continue;
                }

                $output->writeln('');
            } catch (Exception $e) {
                $output->writeln('error ' . $e->getMessage());
            }
        }

    }
}
