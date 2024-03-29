<?php

namespace App\Command;

use App\Entity\RepositoryEntity;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AddRepoCommand extends Command
{
    /**
     * @var EntityManager
     */
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    protected static $defaultName = 'dokuwiki:addRepo';
    protected static $defaultDescription = 'Adds a repository';

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('type', InputArgument::REQUIRED, 'Repository type: <info>core</info>, <info>plugin</info> or <info>template</info>')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the repository (lower case, no special chars or blanks, as on dokuwiki.org)')
            ->addArgument('gitUrl', InputArgument::REQUIRED, 'Public git url')
            ->addArgument('branch', InputArgument::REQUIRED, 'Default branch')
            ->addArgument('email', InputArgument::REQUIRED, 'Author email address')
            ->addArgument('englishReadonly', InputArgument::OPTIONAL, "If readonly, English translations can not be submitted in the tool. (<info>true</info>=readonly)", 'true')
            ->addArgument('displayName', InputArgument::OPTIONAL, 'Template/plugin name to display')
            ->addArgument('author', InputArgument::OPTIONAL, 'Author name (updated later from dokuwiki.org)', '')
            ->addArgument('popularity', InputArgument::OPTIONAL, 'Popularity value (used to sort)  (updated later from dokuwiki.org)', 0);
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     * @return int 0 if everything went fine, or an error code
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument('type');
        $repositoryTypes = [
            RepositoryEntity::TYPE_CORE,
            RepositoryEntity::TYPE_PLUGIN,
            RepositoryEntity::TYPE_TEMPLATE
        ];
        if (!in_array($type, $repositoryTypes)) {
            $output->writeln("Unknown type. Use 'core', 'plugin' or 'template'");
            return Command::INVALID;
        }

        $repo = new RepositoryEntity();
        $repo->setLastUpdate(0);
        $repo->setUrl($input->getArgument('gitUrl'));
        $repo->setBranch($input->getArgument('branch'));
        $repo->setName($input->getArgument('name'));
        $repo->setPopularity($input->getArgument('popularity'));
        $repo->setDisplayName($input->getArgument('displayName') ?? $input->getArgument('name'));
        $repo->setEmail($input->getArgument('email'));
        $repo->setAuthor($input->getArgument('author'));
        $repo->setType($type);
        $repo->setState(RepositoryEntity::STATE_INITIALIZING);
        $repo->setErrorCount(0);
        $repo->setDescription('');
        $repo->setTags('');
        $repo->setEnglishReadonly($input->getArgument('englishReadonly') == 'true');

        $errors = $this->validator->validate($repo);
        if(count($errors) > 0) {
            $errorsString = (string) $errors;
            $output->writeln($errorsString);
            return Command::FAILURE;
        }

        $this->entityManager->persist($repo);
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
