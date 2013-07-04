<?php
namespace org\dokuwiki\translatorBundle\Services\Repository;

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

    public function __construct($dataFolder, EntityManager $entityManager, $entity, RepositoryStats $repositoryStats,
                GitService $gitService, RepositoryBehavior $behavior) {
        $this->dataFolder = $dataFolder;
        $this->entityManager = $entityManager;
        $this->entity = $entity;
        $this->repositoryStats = $repositoryStats;
        $this->gitService = $gitService;
        $this->behavior = $behavior;
    }

    public function update() {
        $path = $this->buildBasePath();
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $this->lock();
        $changed = $this->updateFromRemote();
        if ($changed) {
            $this->updateLanguage();
        }

        $this->entity->setLastUpdate(intval(time()));
        $this->entityManager->flush($this->entity);
        $this->unlock();
    }

    /**
     * Update local repository
     * @return boolean true if the repository is changed.
     */
    private function updateFromRemote() {
        if ($this->gitService->isRepository($this->getRepositoryPath())) {
            $this->git = $this->gitService->openRepository($this->getRepositoryPath());
            return $this->behavior->pull($this->git, $this->entity);
        }

        $remote = $this->behavior->createOriginURL($this->entity);
        $this->git = $this->gitService->createRepositoryFromRemote($remote, $this->getRepositoryPath());
        return true;
    }

    private function getRepositoryPath() {
        return $this->buildBasePath() . 'repository/';
    }

    private function buildBasePath() {
        $path = $this->buildDataPath();
        $type = $this->getType();
        if ($type !== '') {
            $path .= "$type/";
        }
        $path .= $this->getName().'/';
        return $path;
    }

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

    private function updateLanguage() {
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

    private function updateTranslationStatistics($translations) {
        $this->repositoryStats->clearStats($this->entity);
        $this->repositoryStats->createStats($translations, $this->entity);
    }



    private function saveLanguage($translations) {
        $langFolder = $this->buildBasePath() . 'lang/';
        if (!file_exists($langFolder)) {
            mkdir($langFolder, 0777, true);
        }

        foreach ($translations as $langCode => $files) {
            file_put_contents("$langFolder$langCode.ser", serialize($files));
        }
    }

    /**
     * Get language Data for a language code
     * @param string $code language code
     * @return array language data. array will be empty, if no language data is available
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

    private function lock() {
        touch($this->getLockPath());
    }

    private function unlock() {
        @unlink($this->getLockPath());
    }

    private function getLockPath() {
        $path = $this->buildBasePath();
        $path .= 'locked';
        return $path;
    }

    /**
     * Scedule a new Translation update
     *
     * @param array $translation Translated text
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

    private function getUpdatePath($id) {
        $path = $this->buildBasePath() . 'updates/';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $path .= $id . '.update';
        return $path;
    }

    public function createAndSendPatch(TranslationUpdateEntity $update) {
        $tmpDir = $this->buildTempPath($update->getId());
        $tmpGit = $this->gitService->createRepositoryFromRemote($this->getRepositoryPath(), $tmpDir);
        $author = $this->prepareAuthor($update);
        $this->applyChanges($tmpGit, $tmpDir, $update);

        $tmpGit->commit('translation update', $author);

        $this->behavior->sendChange($tmpGit, $update);

        $this->rrmdir($tmpDir);
        $this->entityManager->remove($update);
        $this->entityManager->flush();
    }

    private function rrmdir($folder) {
        $fs = new Filesystem();
        // some files are write-protected by git - this removes write protection
        $fs->chmod($folder, 0666, 0000, true);
        // https://bugs.php.net/bug.php?id=52176
        $fs->remove($folder);
    }

    private function buildTempPath($id) {
        $path = $this->buildBasePath();
        $path .= "tmp";
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return "$path/$id/";
    }

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

    private function applyChanges(GitRepository $git, $folder, TranslationUpdateEntity $update) {
        $translations = unserialize(file_get_contents($this->getUpdatePath($update->getId())));

        foreach ($translations as $path => $translation) {
            /**
             * @var LocalText $translation
             */
            $path = $folder . $path;
            $langFolder = dirname($path);
            $file = $langFolder . '/' . $update->getLanguage() . '/' . basename($path);
            file_put_contents($file, $translation->render());
            $git->add($file);
        }
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
     * @return array|string Relative path to the language folder. i.e. lang/ for plugins
     */
    protected abstract function getLanguageFolder();
}
