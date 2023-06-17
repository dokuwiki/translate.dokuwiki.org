<?php

namespace App\Command;

use DateTime;
use Doctrine\ORM\NoResultException;
use Exception;
use App\Entity\RepositoryEntity;
use App\Repository\RepositoryEntityRepository;
use App\Services\Repository\RepositoryManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowInfoCommand extends Command {

    private RepositoryManager $repositoryManager;
    private RepositoryEntityRepository $repositoryEntityRepository;

    protected static $defaultName = 'dokuwiki:showInfo';
    protected static $defaultDescription = 'Show status for maintenance for all or a specific repo, or basic info for all';

    public function __construct(RepositoryEntityRepository $repositoryEntityRepository, RepositoryManager $repositoryManager) {
        $this->repositoryEntityRepository = $repositoryEntityRepository;
        $this->repositoryManager = $repositoryManager;

        parent::__construct();
    }

    protected function configure(): void {
        $this->addArgument('type', InputArgument::OPTIONAL, '<info>template</info>, <info>plugin</info> or <info>core</info>. Or <info>onlyerrors</info> for filtering or <info>basicinfo</info> for urls/branch/email listing.')
            ->addArgument('name', InputArgument::OPTIONAL, 'repository name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $type = $input->getArgument('type');

        if(isset($name) && isset($type)) {
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
                $repo = $this->repositoryEntityRepository->getRepository($type, $name);
            } catch (NoResultException $e) {
                $output->writeln('nothing found');
                return Command::FAILURE;
            }
            $repositories[] = $repo;
        } else {
            /** @var RepositoryEntity[] $repositories */
            $repositories = $this->repositoryEntityRepository->findAll();
        }

        if($type == 'basicinfo') {
            $this->showBasicInfo($output, $repositories);
        } else {
            $this->showStatusInfo($output, $repositories, $type);
        }

        return Command::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     * @param RepositoryEntity[] $repositories
     * @param $type
     * @return void
     */
    private function showStatusInfo(OutputInterface $output, array $repositories, $type): void
    {
        // header
        $dashLine = str_repeat('-', 9 - 1) . ' '
            . str_repeat('-', 15 - 1) . ' '
            . str_repeat('-', 35 - 1) . ' '
            . str_repeat('-', 18 - 1) . ' '
            . str_repeat('-', 20 - 1) . ' '
            . str_repeat('-', 4 - 1) . ' '
            . str_repeat('-', 15 - 1);
        $output->writeln($dashLine);
        $output->writeln(
            sprintf('%-9s', 'Type ')
            . sprintf('%-15s', 'Name ')
            . sprintf('%-35s', 'Display name ')
            . sprintf('%-18s', 'State ')
            . sprintf('%-20s', 'Last update')
            . sprintf('%-4s', 'Cnt')
            . sprintf('%-15s', 'Error messages')
        );
        $output->writeln($dashLine);

        $countTotal = count($repositories);
        $countWithErrors = 0;
        foreach ($repositories as $repoEntity) {
            if ($type == 'onlyerrors' && $repoEntity->getErrorCount() == 0) {
                continue;
            }

            //line text color
            $fgOpenTag = $fgCloseTag = '';
            if ($repoEntity->getErrorCount() >= 3) {
                $fgOpenTag = '<fg=red>';
                $fgCloseTag = '</>';
            } elseif ($repoEntity->getErrorCount() > 0) {
                $fgOpenTag = '<fg=yellow>';
                $fgCloseTag = '</>';
            }

            $countWithErrors++;
            try {
                $output->write($fgOpenTag . sprintf('%-9s', $repoEntity->getType() . ' '));
                $output->write(sprintf('%-15s', $repoEntity->getName() . ' '));
                $output->write(sprintf('%-35s', substr($repoEntity->getDisplayName() . ' ', 0, 34)));
                $output->write(sprintf('%-18s', $repoEntity->getState()));

                $date = new DateTime("@{$repoEntity->getLastUpdate()}");
                $output->write($date->format('Y-m-d H:i:s') . ' ');
                $output->write(sprintf('%-4s', $repoEntity->getErrorCount() . ' '));
                $errorMsg = str_replace("\n", "\n   ", $repoEntity->getErrorMsg());
                if ($countTotal > 1) {
                    $errorMsg = strlen($errorMsg) > 50 ? substr($errorMsg, 0, 50) . ' (more..) ' : $errorMsg;
                } else {
                    $errorMsg = "see below\n   " . $errorMsg;
                }
                $output->write($errorMsg);


                $repo = $this->repositoryManager->getRepository($repoEntity);
                if (!$repo->hasGit()) {
                    $output->write('No local checkout found');
                }
                //TODO at check if locked

                $output->writeln($fgCloseTag);
            } catch (Exception $e) {
                $output->writeln('error ' . $e->getMessage());
            }
        }
        $output->writeln('');
        $output->writeln(
            'found ' . $countTotal . ' repositories'
            . ($type == 'onlyerrors' && $countWithErrors > 0 ? ', ' . $countWithErrors . ' with errors' : '')
        );
    }

    /**
     * @param OutputInterface $output
     * @param RepositoryEntity[] $repositories
     * @return void
     */
    private function showBasicInfo(OutputInterface $output, array $repositories)
    {
        // header
        $dashLine = str_repeat('-', 35 - 1) . ' '
            . str_repeat('-', 9 - 1) . ' '
            . str_repeat('-', 15 - 1) . ' '
            . str_repeat('-', 65 - 1) . ' '
            . str_repeat('-', 10 - 1) . ' '
            . str_repeat('-', 25 - 1) . ' '
            . str_repeat('-', 17 - 1);
        $output->writeln($dashLine);
        $output->writeln(
            sprintf('%-35s', 'Display name ')
            . sprintf('%-9s', 'Type ')
            . sprintf('%-15s', 'Name ')
            . sprintf('%-65s', 'GitUrl ')
            . sprintf('%-10s', 'Branch')
            . sprintf('%-25s', 'Email')
            . sprintf('%-17s', 'English Readonly')
        );
        $output->writeln($dashLine);

        $countTotal = count($repositories);
        foreach ($repositories as $repoEntity) {
            $output->write(sprintf('%-35s', substr($repoEntity->getDisplayName() . ' ', 0, 34)));
            $output->write(sprintf('%-9s', $repoEntity->getType() . ' '));
            $output->write(sprintf('%-15s', $repoEntity->getName() . ' '));
            $output->write(sprintf('%-65s', $repoEntity->getUrl() . ' '));
            $output->write(sprintf('%-10s', $repoEntity->getBranch() . ' '));
            $output->write(sprintf('%-25s', $repoEntity->getEmail() . ' '));
            $output->writeln(sprintf('%-6s', $repoEntity->getEnglishReadonly() ? 'true' : 'false' ));
        }
        $output->writeln('');
        $output->writeln(
            'found ' . $countTotal . ' repositories'
        );
    }
}
