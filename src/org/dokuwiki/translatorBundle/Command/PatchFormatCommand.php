<?php

namespace org\dokuwiki\translatorBundle\Command;

use Exception;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntityRepository;
use org\dokuwiki\translatorBundle\Services\Repository\RepositoryManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PatchFormatCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('dokuwiki:updateLanguages')
            ->setDescription('Updates all language information from local repository. Refreshes the cached translation objects');

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
                $output->write('creating ' . $repoEntity->getDisplayName() . ' ... ');
                $repo = $repoManager->getRepository($repoEntity);
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

    }
}