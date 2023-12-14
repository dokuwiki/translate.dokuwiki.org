<?php

namespace App\Command;

use App\Entity\TranslationUpdateEntity;
use App\Repository\TranslationUpdateEntityRepository;
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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ShowInfoCommand extends Command
{
    public const NEWLINE = true;
    private RepositoryManager $repositoryManager;
    private RepositoryEntityRepository $repositoryEntityRepository;
    private TranslationUpdateEntityRepository $translationUpdateEntityRepository;
    private ParameterBagInterface $parameterBag;

    protected static $defaultName = 'dokuwiki:showInfo';
    protected static $defaultDescription = 'Show status for maintenance for all or a specific repo, or basic info for all';

    public function __construct(RepositoryEntityRepository $repositoryEntityRepository,
                                TranslationUpdateEntityRepository $translationUpdateEntityRepository,
                                RepositoryManager $repositoryManager, ParameterBagInterface $parameterBag)
    {
        $this->repositoryEntityRepository = $repositoryEntityRepository;
        $this->translationUpdateEntityRepository = $translationUpdateEntityRepository;
        $this->repositoryManager = $repositoryManager;
        $this->parameterBag = $parameterBag;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('type', InputArgument::OPTIONAL, '<info>template</info>, <info>plugin</info> or <info>core</info>. Or <info>onlyerrors</info> for filtering or <info>basicinfo</info> for urls/branch/email listing.')
            ->addArgument('name', InputArgument::OPTIONAL, 'repository name')
            ->addArgument('showmore', InputArgument::OPTIONAL, '<info>showmore</info> shows the entire error of the translation updates. (only if showing a single repo)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repositories = [];
        $name = $input->getArgument('name');
        $type = $input->getArgument('type');
        $showMore = $input->getArgument('showmore') === 'showmore';

        if (isset($name) && isset($type)) {
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

        if(count($repositories) === 1) {
            $this->showExtendedInfoRepository($output, $repositories[0], $showMore);
            return Command::SUCCESS;
        }

        if ($type == 'basicinfo') {
            $this->showBasicInfoRepositories($output, $repositories);
        } else {
            $this->showStatusInfo($output, $repositories, $type);
        }

        return Command::SUCCESS;
    }

    /**
     * Writes message left-justified with spaces to the output
     * (If message is longer than given length, it is not shorted.)
     *
     * @param OutputInterface $output
     * @param int $length width of the column
     * @param string $message
     * @param bool $newline Whether to add a newline
     * @return void
     */
    private function writeJustified(OutputInterface $output, int $length, string $message, bool $newline = false): void
    {
        $output->write(sprintf("%-{$length}s", $message), $newline);
    }

    /**
     * @param OutputInterface $output
     * @param RepositoryEntity[] $repositories
     * @param string|null $type
     * @return void
     */
    private function showStatusInfo(OutputInterface $output, array $repositories, ?string $type): void
    {
        // header
        $dashLine = str_repeat('-', 35 - 1) . ' '
            . str_repeat('-', 9 - 1) . ' '
            . str_repeat('-', 15 - 1) . ' '
            . str_repeat('-', 18 - 1) . ' '
            . str_repeat('-', 20 - 1) . ' '
            . str_repeat('-', 4 - 1) . ' '
            . str_repeat('-', 15 - 1);

        $output->writeln($dashLine);
        $this->writeJustified($output, 35, 'Display name');
        $this->writeJustified($output, 9, 'Type');
        $this->writeJustified($output, 15, 'Name');
        $this->writeJustified($output, 18, 'State');
        $this->writeJustified($output, 20, 'Last update');
        $this->writeJustified($output, 4, 'Cnt');
        $this->writeJustified($output, 15, 'Error messages', self::NEWLINE);
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
                $output->write($fgOpenTag);
                $this->writeJustified($output, 35, substr($repoEntity->getDisplayName() . ' ', 0, 34));
                $this->writeJustified($output, 9, $repoEntity->getType() . ' ');
                $this->writeJustified($output, 15, $repoEntity->getName() . ' ');
                $this->writeJustified($output, 18, $repoEntity->getState());

                if ($repoEntity->getLastUpdate() === 0) {
                    $date = 'unknown';
                } else {
                    $date = new DateTime("@{$repoEntity->getLastUpdate()}");
                    $date = $date->format('Y-m-d H:i:s') . ' ';
                }
                $this->writeJustified($output, 20, $date);

                $count = $repoEntity->getErrorCount();
                if ($count >= 3) {
                    $output->write('<error>');
                }
                $this->writeJustified($output, 3, $count);
                if ($count >= 3) {
                    $output->write('</error>');
                }
                $output->write(' ');

                //short error message
                $errorMsg = ltrim(str_replace("\n", " ", $repoEntity->getErrorMsg()));
                $errorMsg = strlen($errorMsg) > 50 ? substr($errorMsg, 0, 50) . '... ' : $errorMsg;
                $output->write($errorMsg);

                $repo = $this->repositoryManager->getRepository($repoEntity);
                if (!$repo->hasGit()) {
                    $output->write('No local checkout found');
                }
                if ($repo->isLocked()) {
                    $output->write('Repository is locked');
                }

                $output->writeln($fgCloseTag);

                //count of failed translations submits
                $count = $this->translationUpdateEntityRepository->count([
                    'repository' => $repoEntity,
                    'state' => TranslationUpdateEntity::STATE_FAILED
                ]);
                if($count > 0) {
                    $output->writeln('     <error>' . $count. ' failed translations</error>');
                }
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
    private function showBasicInfoRepositories(OutputInterface $output, array $repositories): void
    {
        // Table
        // header
        $dashLine = str_repeat('-', 35 - 1) . ' '
            . str_repeat('-', 9 - 1) . ' '
            . str_repeat('-', 15 - 1) . ' '
            . str_repeat('-', 65 - 1) . ' '
            . str_repeat('-', 10 - 1) . ' '
            . str_repeat('-', 25 - 1) . ' '
            . str_repeat('-', 17 - 1);

        $output->writeln($dashLine);
        $this->writeJustified($output, 35, 'Display name');
        $this->writeJustified($output, 9, 'Type');
        $this->writeJustified($output, 15, 'Name');
        $this->writeJustified($output, 65, 'GitUrl');
        $this->writeJustified($output, 10, 'Branch');
        $this->writeJustified($output, 25, 'Email');
        $this->writeJustified($output, 17, 'English Readonly', self::NEWLINE);
        $output->writeln($dashLine);

        //rows
        $countTotal = count($repositories);
        foreach ($repositories as $repoEntity) {
            $this->writeJustified($output, 35, substr($repoEntity->getDisplayName() . ' ', 0, 34));
            $this->writeJustified($output, 9, $repoEntity->getType() . ' ');
            $this->writeJustified($output, 15, $repoEntity->getName() . ' ');
            $this->writeJustified($output, 65, $repoEntity->getUrl() . ' ');
            $this->writeJustified($output, 10, $repoEntity->getBranch() . ' ');
            $this->writeJustified($output, 25, $repoEntity->getEmail() . ' ');
            $this->writeJustified($output, 6, $repoEntity->getEnglishReadonly() ? 'true' : 'false', self::NEWLINE);
        }
        $output->writeln('');
        $output->writeln('found ' . $countTotal . ' repositories');
    }

    private function showExtendedInfoRepository(OutputInterface $output, RepositoryEntity $repoEntity, bool $showMore): void {
        //list
        try {
            $dashLine = str_repeat('-', 30 - 1) . ' '
                . str_repeat('-', 40 - 1);

            $output->writeln($dashLine);
            $this->writeJustified($output, 30, 'Property');
            $output->writeln('Value');
            $output->writeln($dashLine);

            $this->writeJustified($output, 30, 'Database id');
            $output->writeln($repoEntity->getId());
            $this->writeJustified($output, 30, 'Type');
            $output->writeln($repoEntity->getType());
            $this->writeJustified($output, 30, 'Name');
            $output->writeln($repoEntity->getName());
            $this->writeJustified($output, 30, 'Display name (from wiki)');
            $output->writeln($repoEntity->getDisplayName());
            $this->writeJustified($output, 30, 'Description (from wiki)');
            $output->writeln($repoEntity->getDescription());
            $this->writeJustified($output, 30, 'Tags (from wiki)');
            $output->writeln($repoEntity->getTags());
            $this->writeJustified($output, 30, 'Author (from wiki)');
            $output->writeln($repoEntity->getAuthor());
            $this->writeJustified($output, 30, 'E-mail of author');
            $output->writeln($repoEntity->getEmail());
            $this->writeJustified($output, 30, 'Popularity score (from wiki)');
            $output->writeln($repoEntity->getPopularity());
            $this->writeJustified($output, 30, 'Number of translations');
            $output->writeln($repoEntity->getTranslations()->count());
            $this->writeJustified($output, 30, 'Git clone url');
            $output->writeln($repoEntity->getUrl());
            $this->writeJustified($output, 30, 'Branch');
            $output->writeln($repoEntity->getBranch());
            $this->writeJustified($output, 30, 'English read-only');
            $output->writeln($repoEntity->getEnglishReadonly() ? 'yes' : 'no');
            $this->writeJustified($output, 30, 'Activation/edit key');
            $output->writeln($repoEntity->getActivationKey());

            $this->writeJustified($output, 30, 'Last update');
            if ($repoEntity->getLastUpdate() === 0) {
                $date = 'unknown';
            } else {
                $date = new DateTime("@{$repoEntity->getLastUpdate()}");
                $date = $date->format('Y-m-d H:i:s') . ' ';
            }
            $output->writeln($date);

            $this->writeJustified($output, 30, 'State');
            $output->writeln($repoEntity->getState());
            //line text color
            $fgOpenTag = $fgCloseTag = $msg = '';
            if ($repoEntity->getErrorCount() >= 3) {
                $fgOpenTag = '<fg=red>';
                $fgCloseTag = '</>';
                $msg = ' (Code updates are paused)';
            } elseif ($repoEntity->getErrorCount() > 0) {
                $fgOpenTag = '<fg=yellow>';
                $fgCloseTag = '</>';
            }
            $output->write($fgOpenTag);
            $this->writeJustified($output, 30, 'Error count');
            $output->writeln($repoEntity->getErrorCount() . $msg . $fgCloseTag);

            $this->writeJustified($output, 30, 'Error message:');
            $output->writeln("\n   " . str_replace("\n", "\n   ", ltrim($repoEntity->getErrorMsg())));

            $repo = $this->repositoryManager->getRepository($repoEntity);
            if (!$repo->hasGit()) {
                $output->writeln('No local checkout found');
            }
            if ($repo->isLocked()) {
                $output->writeln('Repository is locked');
            }

            $this->showTranslationUpdates($output, $repoEntity, $showMore);


        } catch (Exception $e) {
            $output->writeln('error ' . $e->getMessage());
        }
    }

    /**
     * @param OutputInterface $output
     * @param RepositoryEntity $repoEntity
     * @param bool $showMore
     * @return void
     */
    private function showTranslationUpdates(OutputInterface $output, RepositoryEntity $repoEntity, bool $showMore): void
    {
        $updates = [];

        $directory = $this->parameterBag->get('app.dataDir');
        $directory .= sprintf('/%s/%s/updates/', $repoEntity->getType(), $repoEntity->getName());

        if (is_dir($directory)) {
            $folders = scandir($directory);
            foreach ($folders as $languageFile) {
                if ($languageFile === '.' || $languageFile === '..') {
                    continue;
                }

                $id = (int) rtrim($languageFile, '.update');
                $updates[$id] = [
                    'hasPatch' => true,
                    'created' => filemtime($directory . $languageFile)
                ];
            }
        }

        /** @var TranslationUpdateEntity[] $updates */
        $translationUpdates = $this->translationUpdateEntityRepository->findBy(['repository' => $repoEntity]);
        foreach ($translationUpdates as $update) {
            $updates[$update->getId()]['entity'] = $update;
        }

        $output->writeln("\nTranslation updates for this repository:\n");
        // header
        $dashLine = str_repeat('-', 5 - 1) . ' '
            . str_repeat('-', 6 - 1) . ' '
            . str_repeat('-', 7 - 1) . ' '
            . str_repeat('-', 40 - 1) . ' '
            . str_repeat('-', 30 - 1) . ' '
            . str_repeat('-', 25 - 1) . ' '
            . str_repeat('-', 30 - 1);

        $output->writeln($dashLine);
        $this->writeJustified($output, 5, 'Id');
        $this->writeJustified($output, 6, 'Lang');
        $this->writeJustified($output, 7, 'State');
        $this->writeJustified($output, 40, 'File created        Submit/Last failed');
        $this->writeJustified($output, 30, 'Author');
        $this->writeJustified($output, 25, 'Email');
        $this->writeJustified($output, 30, 'Error',self::NEWLINE);
        $output->writeln($dashLine);

        foreach ($updates as $id => $update) {
            $this->writeJustified($output, 5, $id);

            if (isset($update['entity'])) {
                /** @var TranslationUpdateEntity $entity */
                $entity = $update['entity'];
                $this->writeJustified($output, 6, $entity->getLanguage(). ' ');
                $state = $entity->getState();
                if($state === TranslationUpdateEntity::STATE_FAILED) {
                    $state = '<error>' . $state  . '</error> ';
                }
                $this->writeJustified($output, 7, $state);
                $dateFile = '';
                if(isset($update['created'])) {
                    $dateFile = new DateTime("@{$update['created']}");
                    $dateFile = $dateFile->format('Y-m-d H:i:s');
                }
                $this->writeJustified($output, 20, $dateFile);
                $dateEntity = new DateTime("@{$entity->getUpdated()}");
                $dateEntity = $dateEntity->format('Y-m-d H:i:s');
                if($dateEntity === $dateFile) {
                    //print once
                    $dateEntity = '';
                }
                $this->writeJustified($output, 20, $dateEntity);
                $this->writeJustified($output, 30, $entity->getAuthor());
                $this->writeJustified($output, 25, $entity->getEmail());
                if ($showMore) {
                    //entire message
                    $errorMsg = "see below\n   ";
                    $errorMsg .= '<error>' . str_replace("\n", "\n   ", $entity->getErrorMsg()) . '</error>';
                } else {
                    //short error message
                    $errorMsg = ltrim(str_replace("\n", " ", $entity->getErrorMsg()));
                    $errorMsg = strlen($errorMsg) > 50 ? substr($errorMsg, 0, 50) . '... (use <info>showmore</>)' : $errorMsg;
                }
                $output->write($errorMsg);
            } else {
                $output->write('<comment>');
                $this->writeJustified($output, 6, 'n/a');
                $this->writeJustified($output, 7, 'n/a');
                $dateFile = new DateTime("@{$update['created']}");
                $dateFile = $dateFile->format('Y-m-d H:i:s');
                $this->writeJustified($output, 40, $dateFile);
                $this->writeJustified($output, 7, '(only submitted translations, no entity with the metadata) ');
                $output->write('</>');
            }
            $output->writeln('');
        }

        if(count($updates) === 0) {
            $output->writeln('No updates available.');
        }
    }
}
