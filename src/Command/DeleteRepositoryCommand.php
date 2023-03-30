<?php

namespace App\Command;

use App\Entity\LanguageStatsEntity;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use App\Entity\RepositoryEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class DeleteRepositoryCommand extends Command
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    protected static $defaultName = 'dokuwiki:deleteRepo';

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag) {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Delete a repository')
            ->addArgument('type', InputArgument::REQUIRED, 'template, plugin or core')
            ->addArgument('name', InputArgument::REQUIRED, 'repository name');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $type = $input->getArgument('type');

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
            $repo = $this->entityManager->getRepository(RepositoryEntity::class)
                ->getRepository($type, $name);
        } catch (NoResultException $e) {
            $output->writeln('nothing found');
            return 1;
        }

        $this->entityManager->getRepository(LanguageStatsEntity::class)
            ->clearStats($repo);
        $this->entityManager->remove($repo);
        $this->entityManager->flush();
        $directory = $this->parameterBag->get('app.dataDir');
        $directory .= sprintf('/%s/%s/', $type, $name);

        $fs = new Filesystem();
        if (is_dir($directory)) {
            // some files are write-protected by git - this removes write protection
            $fs->chmod($directory, 0777, 0000, true);
            // https://bugs.php.net/bug.php?id=52176
            $fs->remove($directory);
        }

        return 0;
    }

}
