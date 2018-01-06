<?php
namespace org\dokuwiki\translatorBundle\Services\Repository;

use Github\Exception\RuntimeException;
use Monolog\Logger;
use org\dokuwiki\translatorBundle\Entity\LanguageNameEntity;
use org\dokuwiki\translatorBundle\Services\Git\GitAddException;
use org\dokuwiki\translatorBundle\Services\Git\GitBranchException;
use org\dokuwiki\translatorBundle\Services\Git\GitCheckoutException;
use org\dokuwiki\translatorBundle\Services\Git\GitCloneException;
use org\dokuwiki\translatorBundle\Services\Git\GitCommitException;
use org\dokuwiki\translatorBundle\Services\Git\GitCreatePatchException;
use org\dokuwiki\translatorBundle\Services\Git\GitException;
use org\dokuwiki\translatorBundle\Services\Git\GitNoRemoteException;
use org\dokuwiki\translatorBundle\Services\Git\GitPullException;
use org\dokuwiki\translatorBundle\Services\Git\GitPushException;
use org\dokuwiki\translatorBundle\Services\GitHub\GitHubCreatePullRequestException;
use org\dokuwiki\translatorBundle\Services\GitHub\GitHubForkException;
use org\dokuwiki\translatorBundle\Services\GitHub\GitHubServiceException;
use org\dokuwiki\translatorBundle\Services\Language\LanguageFileDoseNotExistException;
use org\dokuwiki\translatorBundle\Services\Language\LanguageFileIsEmptyException;
use org\dokuwiki\translatorBundle\Services\Language\LanguageParseException;
use org\dokuwiki\translatorBundle\Services\Language\NoDefaultLanguageException;
use org\dokuwiki\translatorBundle\Services\Language\NoLanguageFolderException;
use org\dokuwiki\translatorBundle\Services\Mail\MailService;
use org\dokuwiki\translatorBundle\Services\Repository\Behavior\RepositoryBehavior;
use Symfony\Component\DependencyInjection\Container;
use org\dokuwiki\translatorBundle\Entity\TranslationUpdateEntity;
use org\dokuwiki\translatorBundle\Services\Git\GitRepository;
use org\dokuwiki\translatorBundle\Services\Git\GitService;
use org\dokuwiki\translatorBundle\Services\Language\LanguageManager;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use Doctrine\ORM\EntityManager;
use org\dokuwiki\translatorBundle\Services\Language\LocalText;
use Symfony\Component\Filesystem\Filesystem;

abstract class Repository {

    private $dataFolder;
    private $basePath = null;

    /**
     * @var GitRepository
     */
    private $git = null;

    /**
     * @var RepositoryEntity Database representation
     */
    protected $entity;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var RepositoryStats
     */
    protected $repositoryStats;

    /**
     * @var GitService
     */
    private $gitService;

    /**
     * @var RepositoryBehavior
     */
    private $behavior;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var MailService
     */
    private $mailService;


    public function __construct($dataFolder, EntityManager $entityManager, $entity, RepositoryStats $repositoryStats,
                GitService $gitService, RepositoryBehavior $behavior, Logger $logger, MailService $mailService) {
        $this->dataFolder = $dataFolder;
        $this->entityManager = $entityManager;
        $this->entity = $entity;
        $this->repositoryStats = $repositoryStats;
        $this->gitService = $gitService;
        $this->behavior = $behavior;
        $this->logger = $logger;
        $this->mailService = $mailService;
    }

    public function update() {
        try {
            $this->updateWithException();
            $this->entity->setLastUpdate(intval(time()));
            $this->entity->setErrorCount(0);
            if ($this->entity->getState() === RepositoryEntity::$STATE_INITIALIZING) {
                $this->initialized();
            }
        } catch (\Exception $e) {
            $reporter = new RepositoryErrorReporter($this->mailService, $this->logger);
            $msg = $reporter->handleUpdateError($e, $this);
            $this->entity->setErrorMsg($msg);
        }
        $this->entityManager->flush($this->entity);
        $this->unlock();
    }

    private function updateWithException() {
        $this->logger->debug('updating ' . $this->entity->getType() . ' ' . $this->entity->getName());
        $path = $this->buildBasePath();
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

    private function initialized() {
        $this->logger->debug('Initializing ' . $this->entity->getType() . ' ' . $this->entity->getName());
        $this->entity->setState(RepositoryEntity::$STATE_ACTIVE);
        $this->mailService->sendEmail($this->entity->getEmail(), 'Your ' . $this->entity->getType() . ' is now active',
                'dokuwikiTranslatorBundle:Mail:extensionReady.txt.twig', array('repo' => $this->entity));
    }

    /**
     * Update local repository if already exist by pull, otherwise create local one
     *
     * @throws GitCloneException
     * @throws GitHubForkException
     *
     * @return boolean true if the repository is changed.
     */
    private function updateFromRemote() {
        $this->openRepository();
        if ($this->git) {
            return $this->behavior->pull($this->git, $this->entity);
        }

        //no repository exists yet
        try {
            $remote = $this->behavior->createOriginURL($this->entity);
            $this->git = $this->gitService->createRepositoryFromRemote($remote, $this->getCloneDirectoryPath());
        } catch (GitCloneException $e) {
            $this->deleteCloneDirectory();
            throw $e;
        } catch (GitHubForkException $e) {
            $this->deleteCloneDirectory();
            throw $e;
        }
        return true;
    }

    /**
     * Try to open repository, if existing set the GitRepository object
     */
    private function openRepository() {
        if ($this->gitService->isRepository($this->getCloneDirectoryPath())) {
            $this->git = $this->gitService->openRepository($this->getCloneDirectoryPath());
        }
    }

    /**
     * Path to folder of git repository for this repository
     *
     * @return string
     */
    private function getCloneDirectoryPath() {
        return $this->buildBasePath() . 'repository/';
    }

    /**
     * Path of base folder of repository
     *
     * @return string path
     */
    private function buildBasePath() {
        $path = $this->buildDataPath();
        $type = $this->getType();
        if ($type !== '') {
            $path .= "$type/";
        }
        $path .= $this->getName().'/';
        return $path;
    }

    /**
     * Path to general data folder of translation tool
     *
     * @return string path
     */
    private function buildDataPath() {
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
     */
    public function updateLanguage() {
        $languageFolders = $this->getLanguageFolder();

        $translations = array();
        foreach ($languageFolders as $languageFolder) {
            $languageFolder = rtrim($languageFolder, '/');
            $languageFolder .= '/';

            $translated = LanguageManager::readLanguages($this->buildBasePath() . "repository/$languageFolder", $languageFolder);
            $translations = array_merge_recursive($translations, $translated);
        }

        $this->updateTranslationStatistics($translations);
        $this->saveLanguage($translations);
    }

    /**
     * Refresh the statistics based on (last version of) translations
     *
     * @param LocalText[] $translations
     */
    private function updateTranslationStatistics($translations) {
        $this->repositoryStats->clearStats($this->entity);
        $this->repositoryStats->createStats($translations, $this->entity);
    }

    /**
     * Store existing language data serialized on disk
     *
     * @param LocalText[] $translations
     */
    private function saveLanguage($translations) {
        $langFolder = $this->buildBasePath() . 'lang/';

        // delete entire folder to ensure clean up deleted files
        if (file_exists($langFolder)) {
            $this->rrmdir($langFolder);
        }

        mkdir($langFolder, 0777, true);

        foreach ($translations as $langCode => $files) {
            file_put_contents("$langFolder$langCode.ser", serialize($files));
        }
    }

    /**
     * Retrieve language Data for a language code from disk
     *
     * @param string $code language code
     * @return LocalText[] array language data. array will be empty, if no language data is available
     */
    public function getLanguage($code) {
        $code = strtolower($code);
        if (!preg_match('/^[a-z-]+$/i', $code)) {
            return array();
        }

        $langFile = $this->buildBasePath() . "lang/$code.ser";
        if (!file_exists($langFile)) {
            return array();
        }
        return unserialize(file_get_contents($langFile));
    }

    /**
     * @return bool True when repository is locked
     */
    public function isLocked() {
        return file_exists($this->getLockPath());
    }

    /**
     * Set lock in base folder of repository
     */
    private function lock() {
        touch($this->getLockPath());
    }

    /**
     * Remove the lock
     */
    private function unlock() {
        @unlink($this->getLockPath());
    }

    /**
     * path to lock file
     *
     * @return string
     */
    private function getLockPath() {
        $path = $this->buildBasePath();
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
     */
    public function addTranslationUpdate($translation, $author, $email, $language) {
        $translationUpdate = new TranslationUpdateEntity();
        $translationUpdate->setAuthor($author);
        $translationUpdate->setEmail($email);
        $translationUpdate->setRepository($this->entity);
        $translationUpdate->setUpdated(time());
        $translationUpdate->setState(TranslationUpdateEntity::$STATE_UNDONE);
        $translationUpdate->setLanguage($language);

        $this->entityManager->persist($translationUpdate);
        $this->entityManager->flush();

        $path = $this->getUpdatePath($translationUpdate->getId());
        file_put_contents($path, serialize($translation));

        return $translationUpdate->getId();
    }

    /**
     * Path to folder were submitted user translations are stored
     *
     * @param int $id
     * @return string
     */
    private function getUpdatePath($id) {
        $path = $this->buildBasePath() . 'updates/';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $path .= $id . '.update';
        return $path;
    }

    /**
     * Create and send patch for the submitted translations
     *
     * @param TranslationUpdateEntity $update
     */
    public function createAndSendPatch(TranslationUpdateEntity $update) {
        $tmpDir = $this->buildTempPath($update->getId());
        try {
            $this->createAndSendPatchWithException($update, $tmpDir);
        } catch (\Exception $e) {
            $reporter = new RepositoryErrorReporter($this->mailService, $this->logger);
            $msg = $reporter->handleTranslationError($e, $this);
            $this->entity->setErrorMsg($msg);
        }
        $this->rrmdir($tmpDir);
        $this->entityManager->remove($update);
        $this->entityManager->flush();
    }

    /**
     * Actual creating and sending of patch for submitted translations
     *
     * @param TranslationUpdateEntity $update
     * @param string $tmpDir path to folder of temporary local git repository
     */
    private function createAndSendPatchWithException(TranslationUpdateEntity $update, $tmpDir) {
        $this->logger->debug('send patch ' . $this->getType() . ' ' . $this->getName() . ' langupdate' . $update->getId());
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
     * @param $folder
     */
    private function rrmdir($folder) {
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
    private function buildTempPath($id) {
        $path = $this->buildBasePath();
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
    private function prepareAuthor(TranslationUpdateEntity $update) {
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
     */
    private function applyChanges(GitRepository $git, $folder, TranslationUpdateEntity $update) {
        /** @var LocalText[] $translations */
        $translations = unserialize(file_get_contents($this->getUpdatePath($update->getId())));

        $changes = false;
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
            $changes = true;
        }
        if (!$changes) throw new NoLanguageFileWrittenException();
    }

    /**
     * Deletes the folder with the git repository checkout
     */
    public function deleteCloneDirectory() {
        $path = $this->getCloneDirectoryPath();
        if (!file_exists($path)) return;
        $this->rrmdir($path);
    }

    /**
     * Check if folder with git repository checkout exists
     *
     * @return bool
     */
    public function hasGit() {
        return is_dir($this->getCloneDirectoryPath());
    }

    /**
     * Get information about the open pull requests i.e. url and count
     *
     * @param LanguageNameEntity $languageNameEntity
     * @return array with count and list url
     */
    public function getOpenPRlistInfo(LanguageNameEntity $languageNameEntity) {
        return $this->behavior->getOpenPRlistInfo($this->entity, $languageNameEntity);
    }

    /**
     * @return string The url to the remote Git repository
     */
    protected function getRepositoryUrl() {
        return $this->entity->getUrl();
    }

    /**
     * @return string The default branch to pull
     */
    protected function getBranch() {
        return $this->entity->getBranch();
    }

    /**
     * @return string The name of the extension
     */
    protected function getName() {
        return $this->entity->getName();
    }

    /**
     * @return string Type of repository.
     */
    protected function getType() {
        return $this->entity->getType();
    }

    /**
     * @return array|string Relative path to the language folder. i.e. lang/ for plugins and templates
     */
    protected abstract function getLanguageFolder();

    public function isFunctional() {
        return $this->behavior->isFunctional();
    }

    /**
     * @return \org\dokuwiki\translatorBundle\Entity\RepositoryEntity
     */
    public function getEntity() {
        return $this->entity;
    }
}
