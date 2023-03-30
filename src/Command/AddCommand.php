<?php

namespace App\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use App\Entity\RepositoryEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AddCommand extends Command
{
    /**
     * @var Registry
     */
    private $entityManager;
    /**
     * @var ValidatorInterface
     */
    private $validator;

    protected static $defaultName = 'dokuwiki:add';

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Adds a repository')
            ->addArgument('type', InputArgument::REQUIRED, 'Repository type: core, plugin or template')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the repository (lower case, no special chars or blanks, as on dokuwiki.org)')
            ->addArgument('gitUrl', InputArgument::REQUIRED, 'Public git url')
            ->addArgument('branch', InputArgument::REQUIRED, 'Default branch')
            ->addArgument('email', InputArgument::REQUIRED, 'Author email address')
            ->addArgument('englishReadonly', InputArgument::OPTIONAL, "If readonly, English translations can not be submitted in the tool. (true=readonly)")
            ->addArgument('displayName', InputArgument::OPTIONAL, 'Template/plugin name to display')
            ->addArgument('author', InputArgument::OPTIONAL, 'Author name (updated later from dokuwiki.org)')
            ->addArgument('popularity', InputArgument::OPTIONAL, 'Popularity value (used to sort)  (updated later from dokuwiki.org)');
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     * @return int 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument('type');
        $repositoryTypes = [
            RepositoryEntity::$TYPE_CORE,
            RepositoryEntity::$TYPE_PLUGIN,
            RepositoryEntity::$TYPE_TEMPLATE
        ];
        if (!in_array($type, $repositoryTypes)) {
            $output->writeln("Unknown type. Use 'core', 'plugin' or 'template'");
            return 1;
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

        $errors = $this->validator->validate($repo);
        if(count($errors) > 0) {
            $errorsString = (string) $errors;
            $output->writeln($errorsString);
            return 1;
        }

        $this->entityManager->persist($repo);
        $this->entityManager->flush();

        return 0;
    }
}
