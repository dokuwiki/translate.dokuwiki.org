<?php

namespace org\dokuwiki\translatorBundle\Command;

use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('dokuwiki:add')
            ->setDescription('Adds a repository')
            ->addArgument('type', null, 'Repository type: core or plugin')
            ->addArgument('name', null, 'Name of the repository (lower case, no special chars or blanks)')
            ->addArgument('gitUrl', null, 'public git url')
            ->addArgument('branch', null, 'default branch')
            ->addArgument('popularity', null, 'popularity value (used to sort)')
            ->addArgument('displayName', null, 'name to display')
            ->addArgument('email', null, 'author email address')
            ->addArgument('author', null, 'author name')
            ->addArgument('englishReadonly', null, 'If readonly, English translations can not be submitted in the tool');

    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $type = $input->getArgument('type');
        if (!in_array($type, array(RepositoryEntity::$TYPE_CORE, RepositoryEntity::$TYPE_PLUGIN))) {
            $output->writeln('Unknown type');
            return;
        }

        $repo = new RepositoryEntity();
        $repo->setLastUpdate(0);
        $repo->setUrl($input->getArgument('gitUrl'));
        $repo->setBranch($input->getArgument('branch'));
        $repo->setName($input->getArgument('name'));
        $repo->setPopularity($input->getArgument('popularity'));
        $repo->setDisplayName($input->getArgument('displayName'));
        $repo->setEmail($input->getArgument('email'));
        $repo->setAuthor($input->getArgument('author'));
        $repo->setType($type);
        $repo->setState(RepositoryEntity::$STATE_INITIALIZING);
        $repo->setErrorCount(0);
        $repo->setDescription('');
        $repo->setTags('');
        $repo->setEnglishReadonly($input->getArgument('englishReadonly') == 'true');

        $this->getContainer()->get('doctrine')->getManager()->persist($repo);
        $this->getContainer()->get('doctrine')->getManager()->flush();

    }
}