<?php

namespace App\Command;

use App\Entity\LanguageStatsEntity;
use App\Entity\TranslationUpdateEntity;
use App\Services\Git\GitException;
use App\Services\Repository\RepositoryManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Exception\ORMException;
use App\Entity\RepositoryEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class DeleteRepoCommand extends Command
{
    /**
     * @var EntityManager
     */
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBag;

    protected static $defaultName = 'dokuwiki:deleteRepo';
    protected static $defaultDescription = 'Delete a repository';
    private RepositoryManager $repositoryManager;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag, RepositoryManager $repositoryManager) {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
        $this->repositoryManager = $repositoryManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('type', InputArgument::REQUIRED, '<info>template</info>, <info>plugin</info> or <info>core</info>')
            ->addArgument('name', InputArgument::REQUIRED, 'repository name');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws GitException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $type = $input->getArgument('type');

        $repositoryTypes = [
            RepositoryEntity::TYPE_CORE,
            RepositoryEntity::TYPE_PLUGIN,
            RepositoryEntity::TYPE_TEMPLATE
        ];
        if (!in_array($type, $repositoryTypes)) {
            $output->writeln(sprintf(
                'Type must be %s, %s or %s',
                RepositoryEntity::TYPE_CORE,
                RepositoryEntity::TYPE_PLUGIN,
                RepositoryEntity::TYPE_TEMPLATE
            ));
            return Command::INVALID;
        }
        try {
            $repo = $this->entityManager->getRepository(RepositoryEntity::class)
                ->getRepository($type, $name);
        } catch (NoResultException $e) {
            $output->writeln('nothing found');
            return Command::FAILURE;
        }

        $this->entityManager->getRepository(LanguageStatsEntity::class)
            ->clearStats($repo);

        //the directory deleted below contains also the corresponding /updates/<id>.update files
        $this->entityManager->getRepository(TranslationUpdateEntity::class)
            ->clearUpdates($repo);

        $this->repositoryManager->getRepository($repo)->removeFork();

        $this->entityManager->remove($repo);
        $this->entityManager->flush();
        $directory = $this->parameterBag->get('app.dataDir');
        $directory .= sprintf('/%s/%s/', $type, $name);

        $fs = new Filesystem();
        if (is_dir($directory)) {
            // some files are write-protected by git - this removes write protection
           // $fs->chmod($directory, 0777, 0000, true);
            // https://bugs.php.net/bug.php?id=52176
            $fs->remove($directory);
        }

        return Command::SUCCESS;
    }

}
