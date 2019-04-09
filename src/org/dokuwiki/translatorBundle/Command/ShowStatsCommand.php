<?php

namespace org\dokuwiki\translatorBundle\Command;

use DateTime;
use Exception;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntityRepository;
use org\dokuwiki\translatorBundle\Services\Repository\RepositoryManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowStatsCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('dokuwiki:showStats')
            ->setDescription('Show some statistics for maintenance');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /** @var RepositoryManager $repoManager */
        $repoManager = $this->getContainer()->get('repository_manager');
        /** @var RepositoryEntityRepository $repoRepository */
        $repoRepository = $this->getContainer()->get('doctrine')->getRepository('dokuwikiTranslatorBundle:RepositoryEntity');

        /** @var RepositoryEntity[] $repositories */
        $repositories = $repoRepository->findAll();
        $output->writeln('found ' . count($repositories) . ' repositories');
        foreach ($repositories as $repoEntity) {
            try {
                $output->write(sprintf('%-45s', 'creating ' . $repoEntity->getDisplayName() . ' '));
                $output->write(sprintf('%-18s', $repoEntity->getState()));

                $date = new DateTime("@{$repoEntity->getLastUpdate()}");
                $output->write($date->format('Y-m-d H:i:s') . ' ');
                $output->write('cnt:' . $repoEntity->getErrorCount() . ' ' . str_replace("\n", "\n   ", $repoEntity->getErrorMsg()));

                $repo = $repoManager->getRepository($repoEntity);
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
