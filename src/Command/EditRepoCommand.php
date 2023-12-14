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

class EditRepoCommand extends Command {
    private OutputInterface $output;

    /**
     * @var EntityManager
     */
    private EntityManagerInterface $entityManager;

    protected static $defaultName = 'dokuwiki:editRepo';
    protected static $defaultDescription = 'Let edit some properties of repository. Supported: <info>giturl</info>, <info>branch</info>, <info>state</info>, <info>email</info>, <info>englishReadonly</info>';

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('type', InputArgument::REQUIRED, '<info>template</info>, <info>plugin</info> or <info>core</info>')
            ->addArgument('name', InputArgument::REQUIRED, 'repository name')
            ->addArgument('property', InputArgument::REQUIRED, 'property name')
            ->addArgument('value', InputArgument::OPTIONAL, 'string or true/false, if no value given current value is shown');
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
                RepositoryEntity::TYPE_TEMPLATE));
            return Command::INVALID;
        }
        try {
            $repo = $this->entityManager->getRepository(RepositoryEntity::class)
                ->getRepository($type, $name);
        } catch (NoResultException $e) {
            $output->writeln('nothing found');
            return Command::FAILURE;
        }

        $property = $input->getArgument('property');
        $value = $input->getArgument('value');

        if(isset($value)) {
            return $this->editRepo($repo, $property, $value);
        } else {
            return $this->showValue($repo, $property);
        }
    }

    /**
     * @param RepositoryEntity $repo
     * @param string $property
     * @param string $value
     * @return int
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function editRepo(RepositoryEntity $repo, string $property, string $value): int {

        switch($property) {
            case 'giturl':
                $repo->setUrl($value);
                break;

            case 'branch':
                $repo->setBranch($value);
                break;

            case 'state':
                $possibleStates = [
                    RepositoryEntity::STATE_ACTIVE,
                    RepositoryEntity::STATE_ERROR,
                    RepositoryEntity::STATE_INITIALIZING,
                    RepositoryEntity::STATE_WAITING_FOR_APPROVAL
                ];
                if(!in_array($value, $possibleStates)) {
                    $this->output->writeln('State unknown');
                    return Command::INVALID;
                }
                $repo->setState($value);
                break;

            case 'englishReadonly':
                $repo->setEnglishReadonly($value === 'true');
                break;

            case 'email':
                $repo->setEmail($value);
                break;

            default:
                $this->output->writeln('property unknown');
                return Command::INVALID;
        }
        $this->entityManager->persist($repo);
        $this->entityManager->flush();
        $this->output->writeln('done');
        return Command::SUCCESS;
    }

    protected function showValue(RepositoryEntity $repo, string $property): int
    {
        switch($property) {
            case 'giturl':
                $value = $repo->getUrl();
                break;

            case 'branch':
                $value = $repo->getBranch();
                break;

            case 'state':
                $value = $repo->getState();
                break;

            case 'englishReadonly':
                $value = $repo->getEnglishReadonly() ? 'true' : 'false';
                break;

            case 'email':
                $value = $repo->getEmail();
                break;

            default:
                $this->output->writeln('property unknown');
                return Command::INVALID;
        }

        $this->output->writeln(sprintf('No value given. The current value is: %s', $value));
        return Command::SUCCESS;
    }

}
