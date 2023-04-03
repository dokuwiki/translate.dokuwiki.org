<?php

namespace App\Command;

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

class EditRepoEntityCommand extends Command {
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var EntityManager
     */
    private $entityManager;

    protected static $defaultName = 'dokuwiki:editRepo';

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Let edit some properties of repository. Supported: giturl, branch, state, email, englishReadonly')
            ->addArgument('type', InputArgument::REQUIRED, 'plugin, template or core')
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
            RepositoryEntity::$TYPE_CORE,
            RepositoryEntity::$TYPE_PLUGIN,
            RepositoryEntity::$TYPE_TEMPLATE
        ];
        if (!in_array($type, $repositoryTypes)) {
            $output->writeln(sprintf(
                'Type must be %s, %s or %s',
                RepositoryEntity::$TYPE_CORE,
                RepositoryEntity::$TYPE_PLUGIN,
                RepositoryEntity::$TYPE_TEMPLATE));
            return 1;
        }
        try {
            $repo = $this->entityManager->getRepository(RepositoryEntity::class)
                ->getRepository($type, $name);
        } catch (NoResultException $e) {
            $output->writeln('nothing found');
            return 1;
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
    protected function editRepo(RepositoryEntity $repo, $property, $value): int {

        switch($property) {
            case 'giturl':
                $repo->setUrl($value);
                break;

            case 'branch':
                $repo->setBranch($value);
                break;

            case 'state':
                $possibleStates = [
                    RepositoryEntity::$STATE_ACTIVE,
                    RepositoryEntity::$STATE_ERROR,
                    RepositoryEntity::$STATE_INITIALIZING,
                    RepositoryEntity::$STATE_WAITING_FOR_APPROVAL
                ];
                if(!in_array($value, $possibleStates)) {
                    $this->output->writeln('State unknown');
                    return 1;
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
                return 1;
        }

        $this->entityManager->flush($repo);
        $this->output->writeln('done');
        return 0;
    }

    protected function showValue(RepositoryEntity $repo, $property): int
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
                return 1;
        }

        $this->output->writeln(sprintf('No value given. The current value is: %s', $value));
        return 0;
    }

}
