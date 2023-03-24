<?php

namespace org\dokuwiki\translatorBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use App\Entity\RepositoryEntity;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddCommand extends ContainerAwareCommand {

    /**
     * @var Registry
     */
    private $entityManager;

    public function __construct(Registry $doctrine) {

        $this->entityManager = $doctrine->getManager();
        parent::__construct();
    }

    protected function configure() {
        $this->setName('dokuwiki:add')
            ->setDescription('Adds a repository')
            ->addArgument('type', null, 'Repository type: core, plugin or template')
            ->addArgument('name', null, 'Name of the repository (lower case, no special chars or blanks)')
            ->addArgument('gitUrl', null, 'public git url')
            ->addArgument('branch', null, 'default branch')
            ->addArgument('popularity', null, 'popularity value (used to sort)')
            ->addArgument('displayName', null, 'name to display')
            ->addArgument('email', null, 'author email address')
            ->addArgument('author', null, 'author name')
            ->addArgument('englishReadonly', null, 'If readonly, English translations can not be submitted in the tool');

    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     * @return void null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $type = $input->getArgument('type');
        $repositoryTypes = array(RepositoryEntity::$TYPE_CORE, RepositoryEntity::$TYPE_PLUGIN, RepositoryEntity::$TYPE_TEMPLATE);
        if (!in_array($type, $repositoryTypes)) {
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

        $this->entityManager->persist($repo);
        $this->entityManager->flush();

    }
}
