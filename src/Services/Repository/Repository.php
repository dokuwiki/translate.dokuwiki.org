<?php

namespace App\Services\Repository;

use App\Services\Git\GitAddException;
use App\Services\Git\GitBranchException;
use App\Services\Git\GitCheckoutException;
use App\Services\Git\GitCreatePatchException;
use App\Services\Git\GitNoRemoteException;
use App\Services\Git\GitPushException;
use App\Services\GitHub\GitHubCreatePullRequestException;
use App\Services\GitLab\GitLabCreateMergeRequestException;
use App\Services\GitLab\GitLabForkException;
use App\Services\GitLab\GitLabServiceException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Exception\ORMException;
use Exception;
use App\Entity\LanguageNameEntity;
use App\Entity\RepositoryEntity;
use App\Entity\TranslationUpdateEntity;
use App\Services\Git\GitCloneException;
use App\Services\Git\GitCommandException;
use App\Services\Git\GitCommitException;
use App\Services\Git\GitException;
use App\Services\Git\GitRepository;
use App\Services\Git\GitService;
use App\Services\GitHub\GitHubForkException;
use App\Services\GitHub\GitHubServiceException;
use App\Services\Language\LanguageFileDoesNotExistException;
use App\Services\Language\LanguageFileIsEmptyException;
use App\Services\Language\LanguageManager;
use App\Services\Language\LanguageParseException;
use App\Services\Language\LocalText;
use App\Services\Language\NoDefaultLanguageException;
use App\Services\Language\NoLanguageFolderException;
use App\Services\Mail\MailService;
use App\Services\Repository\Behavior\RepositoryBehavior;
use Github\Exception\MissingArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

abstract class Repository
{

    private string $dataFolder;
    private ?string $basePath = null;
    /**
     * @var GitRepository|null cloned git repository of the forked or original repository
     */
    private ?GitRepository $git = null;
    protected RepositoryEntity $entity;
    /**
     * @var EntityManager
     */
    protected EntityManagerInterface $entityManager;
    protected RepositoryStats $repositoryStats;
    private GitService $gitService;
    private RepositoryBehavior $behavior;
    private LoggerInterface $logger;
    private MailService $mailService;


    /**
     * Repository constructor.
     *
     * @param string $dataFolder
     * @param EntityManagerInterface $entityManager
     * @param RepositoryEntity $entity
     * @param RepositoryStats $repositoryStats
     * @param GitService $gitService
     * @param RepositoryBehavior $behavior
     * @param LoggerInterface $logger
     * @param MailService $mailService
     */
    public function __construct($dataFolder, EntityManagerInterface $entityManager, RepositoryEntity $entity, RepositoryStats $repositoryStats,
                                GitService $gitService, RepositoryBehavior $behavior, LoggerInterface $logger, MailService $mailService)
    {
        $this->dataFolder = $dataFolder;
        $this->entityManager = $entityManager;
        $this->entity = $entity;
        $this->repositoryStats = $repositoryStats;
        $this->gitService = $gitService;
        $this->behavior = $behavior;
        $this->logger = $logger;
        $this->mailService = $mailService;
    }

    /**
     * Create or update the local repository fork and update cached language files
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransportExceptionInterface
     */
    public function update(): void
    {
        try {
            $this->updateWithException();
            $this->entity->setLastUpdate(time());
            $this->entity->setErrorCount(0);
            if ($this->entity->getState() === RepositoryEntity::STATE_INITIALIZING) {
                $this->initialized();
            }
        } catch (Exception $e) {
            $reporter = new RepositoryErrorReporter($this->mailService, $this->logger);
            $reporter->handleUpdateError($e, $this); //stores error in the repository entity
        }
        $this->entityManager->persist($this->entity);
        $this->entityManager->flush();
        $this->unlock();
    }

    /**
     * Tries to create or update the local repository fork, push, and update serialized language files
     *
     * @throws GitCloneException
     * @throws GitException
     * @throws GitHubForkException|GitLabForkException
     * @throws GitHubServiceException|GitLabServiceException
     * @throws LanguageFileDoesNotExistException
     * @throws LanguageParseException
     * @throws NoDefaultLanguageException
     * @throws NoLanguageFolderException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function updateWithException(): void
    {
        $this->logger->debug('updating ' . $this->entity->getType() . ' ' . $this->entity->getName());
        $path = $this->buildBaseDirectoryPath();
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        if ($this->isLocked()) {
            $this->logger->debug(sprintf(
                'Repository %s (%d) is locked - skipping', $this->entity->getName(), $this->entity->getId()));
            return;
        }
        $this->lock();

        $changed = $this->updateFromRemote();
        if ($changed) {
            $this->updateLanguage();
        }
    }

    /**
     * If initialization succeeded, sent notification to plugin author
     *
     * @throws TransportExceptionInterface
     */
    private function initialized(): void
    {
        $this->logger->debug('Initializing ' . $this->entity->getType() . ' ' . $this->entity->getName());
        $this->entity->setState(RepositoryEntity::STATE_ACTIVE);
        $this->mailService->sendEmail(
            $this->entity->getEmail(),
            'Your ' . $this->entity->getType() . ' is now active',
            'mail/extensionReady.txt.twig',
            ['repository' => $this->entity]
        );
    }

    /**
     * Update local repository if already exist by pull, otherwise create local one
     *
     * @return boolean true if the repository is changed.
     *
     * @throws GitCloneException
     * @throws GitException
     * @throws GitHubForkException|GitLabForkException
     * @throws GitHubServiceException|GitLabServiceException
     */
    private function updateFromRemote(): bool
    {
        $this->openRepository();
        if ($this->git) {
            return $this->behavior->pull($this->git, $this->entity);
        }

        //no repository exists yet
        $this->logger->debug('No existing repo, (if applicable fork) and clone. ' . $this->entity->getUrl());
        try {
            $forkedCloneUrl = $this->behavior->createOriginURL($this->entity);
            $this->git = $this->gitService->createRepositoryFromRemote($forkedCloneUrl, $this->getCloneDirectoryPath());
        } catch (GitCloneException|GitHubForkException|GitLabForkException $e) {
            $this->deleteCloneDirectory();
            throw $e;
        }
        return true;
    }

    /**
     * Try to open repository, if existing set the GitRepository object
     *
     * @throws GitException
     */
    private function openRepository(): void
    {
        $cloneRepositoryPath = $this->getCloneDirectoryPath();
        if ($this->gitService->isRepository($cloneRepositoryPath)) {
            $this->git = $this->gitService->openRepository($cloneRepositoryPath);
        }
    }

    /**
     * Path to folder of cloned git repository for this repository
     *
     * @return string
     */
    private function getCloneDirectoryPath(): string
    {
        return $this->buildBaseDirectoryPath() . 'repository/';
    }

    /**
     * Path of base folder of repository
     *
     * @return string path
     */
    private function buildBaseDirectoryPath(): string
    {
        $path = $this->buildDataDirectoryPath();
        $type = $this->getType();
        if ($type !== '') {
            $path .= "$type/";
        }
        $path .= $this->getName() . '/';
        return $path;
    }

    /**
     * Path to general data folder of translation tool
     *
     * @return string path
     */
    private function buildDataDirectoryPath(): string
    {
        if ($this->basePath === null) {
            $base = $this->dataFolder;
            $base = str_replace('\\', '/', $base);
            $base = trim($base);
            $base = rtrim($base, '/');
            $this->basePath = $base . '/';
        }
        return $this->basePath;
    }

    /**
     * Read languages and store the serialized LocalText[] arrays of the translations
     *
     * @throws LanguageFileDoesNotExistException
     * @throws LanguageParseException
     * @throws NoDefaultLanguageException
     * @throws NoLanguageFolderException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateLanguage(): void
    {
        $languageFolders = $this->getLanguageFolders();

        $translations = array();
        foreach ($languageFolders as $languageFolder) {
            $languageFolder = rtrim($languageFolder, '/');

            $translated = LanguageManager::readLanguages($this->buildBaseDirectoryPath() . "repository/$languageFolder", $languageFolder);
            $translations = array_merge_recursive($translations, $translated);
        }

        $this->updateTranslationStatistics($translations);
        $this->saveLanguage($translations);
    }

    /**
     * Refresh the statistics based on (last version of) translations
     *
     * @param LocalText[] $translations
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function updateTranslationStatistics($translations): void
    {
        $this->repositoryStats->clearStats($this->entity);
        $this->repositoryStats->createStats($translations, $this->entity);
    }

    /**
     * Store existing language data serialized on disk
     *
     * @param LocalText[] $translations
     */
    private function saveLanguage($translations): void
    {
        $langFolder = $this->buildBaseDirectoryPath() . 'lang/';

        // delete entire folder to ensure clean up deleted files
        if (file_exists($langFolder)) {
            $this->recursiveRemoveDirectory($langFolder);
        }

        mkdir($langFolder, 0777, true);

        foreach ($translations as $langCode => $files) {
            file_put_contents("$langFolder$langCode.ser", serialize($files));
        }
    }

    /**
     * Retrieve language file objects for a language code from disk
     *
     * @param string $code language code
     * @return LocalText[] array language data. array will be empty, if no language data is available
     */
    public function getLanguage($code): array
    {
        $code = strtolower($code);
        if (!preg_match('/^[a-z-]+$/i', $code)) {
            return array();
        }

        $langFile = $this->buildBaseDirectoryPath() . "lang/$code.ser";
        if (!file_exists($langFile)) {
            return array();
        }
        return unserialize(file_get_contents($langFile));
    }

    /**
     * @return bool True when repository is locked
     */
    public function isLocked(): bool
    {
        return file_exists($this->getLockPath());
    }

    /**
     * Set lock in base folder of repository
     */
    private function lock(): void
    {
        touch($this->getLockPath());
    }

    /**
     * Remove the lock
     */
    private function unlock(): void
    {
        @unlink($this->getLockPath());
    }

    /**
     * path to lock file
     *
     * @return string
     */
    private function getLockPath(): string
    {
        $path = $this->buildBaseDirectoryPath();
        $path .= 'locked';
        return $path;
    }

    /**
     * Schedule an submitted Translation update and store translation
     *
     * @param LocalText[] $translation Translated text
     * @param string $author Author of the translation
     * @param string $email Authors email address of the translation
     * @param string $language language code for translation
     * @return int id of queue element in database
     *
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function addTranslationUpdate($translation, $author, $email, $language): int
    {
        $translationUpdate = new TranslationUpdateEntity();
        $translationUpdate->setAuthor($author);
        $translationUpdate->setEmail($email);
        $translationUpdate->setRepository($this->entity);
        $translationUpdate->setUpdated(time());
        $translationUpdate->setState(TranslationUpdateEntity::STATE_UNDONE);
        $translationUpdate->setLanguage($language);

        $this->entityManager->persist($translationUpdate);
        $this->entityManager->flush();

        $path = $this->buildUpdateFilePath($translationUpdate->getId());
        file_put_contents($path, serialize($translation));

        return $translationUpdate->getId();
    }

    /**
     * Path to folder were submitted user translations are stored
     *
     * @return string
     */
    private function buildUpdateDirectoryPath(): string
    {
        $path = $this->buildBaseDirectoryPath() . 'updates/';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    /**
     * Path to file where submitted user translations are stored
     *
     * @param int $id
     * @return string
     */
    private function buildUpdateFilePath($id): string
    {
        $path = $this->buildUpdateDirectoryPath();
        $path .= $id . '.update';
        return $path;
    }

    /**
     * Create and send patch for the submitted translations
     *
     * @param TranslationUpdateEntity $update
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransportExceptionInterface
     */
    public function createAndSendPatch(TranslationUpdateEntity $update): void
    {
        $tmpDir = $this->buildTempFolderPath($update->getId());
        try {
            $this->createAndSendPatchWithException($update, $tmpDir);
            $update->setState(TranslationUpdateEntity::STATE_SENT);
        } catch (Exception $e) {
            // mail for:
            //   GitHubCreatePullRequestException|GitLabCreateMergeRequestException
            // only logged:
            //   GitCloneException  GitCommandException  GitCommitException  GitException
            //   GitHubServiceException GitLabServiceException
            //   NoLanguageFileWrittenException  TransportExceptionInterface  GitAddException  GitBranchException
            //   GitCheckoutException GitCreatePatchException  GitNoRemoteException
            //   GitPushException  MissingArgumentException
            $reporter = new RepositoryErrorReporter($this->mailService, $this->logger);
            $msg = $reporter->handleTranslationError($e, $this); //stores error in the repository entity

            $update->setErrorMsg($msg);
            $update->setState(TranslationUpdateEntity::STATE_FAILED);
            $update->setUpdated(time());
        }
        $this->recursiveRemoveDirectory($tmpDir);
        $this->entityManager->flush(); //stores changes changed entities i.e. RepositoryEntities and TranslationUpdateEntities.
    }

    /**
     * Actual creating and sending of patch for submitted translations
     *
     * @param TranslationUpdateEntity $update
     * @param string $tmpDir path to folder of temporary local git repository with patch of language update
     *
     * @throws GitAddException
     * @throws GitBranchException
     * @throws GitCheckoutException
     * @throws GitCloneException
     * @throws GitCommandException
     * @throws GitCommitException
     * @throws GitCreatePatchException
     * @throws GitException
     * @throws GitHubCreatePullRequestException|GitLabCreateMergeRequestException
     * @throws GitHubServiceException|GitLabServiceException
     * @throws GitNoRemoteException
     * @throws GitPushException
     * @throws MissingArgumentException
     * @throws NoLanguageFileWrittenException
     * @throws TransportExceptionInterface
     */
    private function createAndSendPatchWithException(TranslationUpdateEntity $update, $tmpDir): void
    {
        $this->logger->debug('send patch ' . $this->getType() . ' ' . $this->getName() . ' language update ' . $update->getId());
        $this->openRepository();

        // clone the local temporary git repository
        $tmpGit = $this->gitService->createRepositoryFromRemote($this->getCloneDirectoryPath(), $tmpDir);
        // add files to local temporary git repository
        $this->applyChanges($tmpGit, $tmpDir, $update);
        // commit files to local temporary git repository
        $author = $this->prepareAuthor($update);
        $tmpGit->commit('translation update', $author);

        $this->behavior->sendChange($tmpGit, $update, $this->git);
    }

    /**
     * Recursively remove entire folder
     *
     * @param $folder
     */
    private function recursiveRemoveDirectory($folder): void
    {
        $fs = new Filesystem();
        // some files are write-protected by git - this removes write protection
        $fs->chmod($folder, 0777, 0000, true);
        // https://bugs.php.net/bug.php?id=52176
        $fs->remove($folder);
    }

    /**
     * path to tmp folder for e.g. storing branch for creating patch
     *
     * @param string|int $id
     * @return string
     */
    private function buildTempFolderPath($id): string
    {
        $path = $this->buildBaseDirectoryPath();
        $path .= "tmp";
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return "$path/$id/";
    }

    /**
     * Prepare author string for commit
     *
     * @param TranslationUpdateEntity $update
     * @return string
     */
    private function prepareAuthor(TranslationUpdateEntity $update): string
    {
        $author = $update->getAuthor();
        $email = $update->getEmail();
        if (empty($author)) {
            return "DokuWiki Translation <>";
        }
        if (!empty($email)) {
            $author .= " <$email>";
        }

        return escapeshellarg($author);
    }

    /**
     * Create new language file(s) in folder of git repository and add these to git
     *
     * @param GitRepository $git
     * @param string $folder temporary clone directory of repository
     * @param TranslationUpdateEntity $update
     *
     * @throws NoLanguageFileWrittenException
     * @throws GitCommandException
     */
    private function applyChanges(GitRepository $git, $folder, TranslationUpdateEntity $update): void
    {
        /** @var LocalText[] $translations */
        $translations = unserialize(file_get_contents($this->buildUpdateFilePath($update->getId())));

        $languageFileWritten = false;
        foreach ($translations as $path => $translation) {
            $path = $folder . $path;
            $langFolder = dirname($path) . '/' . $update->getLanguage() . '/';
            if (!is_dir($langFolder)) {
                mkdir($langFolder, 0777, true);
            }
            $file = $langFolder . basename($path);

            try {
                $content = $translation->render();
            } catch (LanguageFileIsEmptyException $e) {
                continue;
            }
            if ($content === '') {
                continue;
            }
            file_put_contents($file, $content);
            $git->add($file);
            $languageFileWritten = true;
        }
        if (!$languageFileWritten) throw new NoLanguageFileWrittenException();
    }

    /**
     * Deletes the folder with the git repository checkout
     */
    public function deleteCloneDirectory(): void
    {
        $path = $this->getCloneDirectoryPath();
        if (!file_exists($path)) return;
        $this->recursiveRemoveDirectory($path);
    }

    /**
     * TODO combine with deleteCloneDirectory??
     * @return void
     *
     * @throws GitException
     * @throws GitHubServiceException|GitLabServiceException
     * @throws GitNoRemoteException
     */
    public function removeFork(): void
    {
        $this->openRepository();
        if ($this->git) {
            $this->behavior->removeRemoteFork($this->git);
        }
    }

    /**
     * Check if folder with git repository checkout exists
     *
     * @return bool
     */
    public function hasGit(): bool
    {
        return is_dir($this->getCloneDirectoryPath());
    }

    /**
     * Get information about the open pull requests i.e. url and count
     *
     * @param LanguageNameEntity $languageNameEntity
     * @return array with count and list url
     *
     * @throws GitHubServiceException
     */
    public function getOpenPRListInfo(LanguageNameEntity $languageNameEntity): array
    {
        return $this->behavior->getOpenPRListInfo($this->entity, $languageNameEntity);
    }

    /**
     * @return string The url to the remote Git repository
     */
    protected function getRepositoryUrl(): string
    {
        return $this->entity->getUrl();
    }

    /**
     * @return string The default branch to pull
     */
    protected function getBranch(): string
    {
        return $this->entity->getBranch();
    }

    /**
     * @return string The name of the extension
     */
    protected function getName(): string
    {
        return $this->entity->getName();
    }

    /**
     * @return string Type of repository.
     */
    protected function getType(): string
    {
        return $this->entity->getType();
    }

    /**
     * @return string[] Array with relative path to the language folder(s). i.e. lang/ for plugins and templates
     */
    protected abstract function getLanguageFolders(): array;

    /**
     * Check if remote repository is functional
     *
     * @return bool
     */
    public function isFunctional(): bool
    {
        return $this->behavior->isFunctional();
    }

    /**
     * @return RepositoryEntity
     */
    public function getEntity(): RepositoryEntity
    {
        return $this->entity;
    }
}
