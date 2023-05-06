<?php
namespace App\Command;


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

class SoftResetCommand extends Command {

    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;
    protected static $defaultName = 'dokuwiki:softReset';
    protected static $defaultDescription = 'Reset lock, tmp folder, error count and last updated';

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag) {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('type', InputArgument::REQUIRED, 'plugin, template or core')
            ->addArgument('name', InputArgument::REQUIRED, 'repository name');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

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
            return Command::FAILURE;
        }
        try {
            $repo = $this->entityManager->getRepository(RepositoryEntity::class)
                ->getRepository($type, $name);
        } catch (NoResultException $e) {
            $output->writeln('nothing found');
            return Command::FAILURE;
        }
        try {
            $this->resetRepo($repo);
        }catch (OptimisticLockException $e) {
            $output->writeln('database locked');
            return Command::FAILURE;
        }

        $directory = $this->parameterBag->get('app.dataDir');
        $directory .= sprintf('/%s/%s/', $type, $name);
        $fs = new Filesystem();
        if (is_dir($directory .'tmp')) {
            // some files are write-protected by git - this removes write protection
            $fs->chmod($directory . 'tmp', 0777, 0000, true);
            // https://bugs.php.net/bug.php?id=52176
            $fs->remove($directory . 'tmp');
            $this->output->write('/tmp folder deleted. ');
        }

        if (is_file($directory . 'locked')) {
            $fs->remove($directory . 'locked');
            $this->output->write('Lock removed. ');
        }
        $this->output->writeln('done');
        return Command::SUCCESS;
    }

    /**
     * @param RepositoryEntity $repo
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function resetRepo(RepositoryEntity $repo) {
        $repo->setState(RepositoryEntity::STATE_ACTIVE);
        $repo->setErrorCount(0);
        $repo->setLastUpdate(0);

        $this->entityManager->persist($repo);
        $this->entityManager->flush();
        $this->output->write('Repository state, error count and update date reset. ');
    }


}
