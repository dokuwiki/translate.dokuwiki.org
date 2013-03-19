<?php
namespace org\dokuwiki\translatorBundle\Services\Repository;

use Symfony\Component\DependencyInjection\Container;
use org\dokuwiki\translatorBundle\Services\Language\LanguageManager;

abstract class Repository {

    public static $TYPE_CORE   = 'core';
    public static $TYPE_PLUGIN = 'plugin';

    private $git = null;
    private $dataFolder;

    public function __construct($dataFolder) {
        $this->dataFolder = $dataFolder;
    }

    public function update() {
        $changed = $this->updateFromRemote();
        $changed = true;
        if ($changed) {
            echo "updating language\n";
            $this->updateLanguage();
        }
        // TODO update "update date"
    }

    private function updateFromRemote() {
        try {
            return $this->doUpdateFromRemote();
        } catch (\Exception $e) {
            throw new GitException('Failed to create/update local repository', 0, $e);
        }
    }

    /**
     * Update local repository
     * @return boolean true if the repository is changed.
     */
    private function doUpdateFromRemote() {
        $path = $this->buildBasePath();
        $branch = $this->getBranch();
        if (file_exists($path)) {
            $this->git = \Git::open($this->getRepositoryPath());
            $this->git->checkout($branch);
        } else {
            mkdir($path, 0777, true);
            $this->git = \Git::create($this->getRepositoryPath());
            $this->git->run('remote add origin ' . $this->getRepositoryUrl());
        }
        $result = $this->git->pull('origin', $branch);
        if ($result === '') {
            return true;
        } else {
            return false;
        }
    }

    private function getRepositoryPath() {
        return $this->buildBasePath() . 'repository/';
    }

    // TODO add caching
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
        $base = $this->dataFolder;
        $base = str_replace('\\', '/', $base);
        $base = trim($base);
        $base = rtrim($base, '/');
        return $base . '/';
    }

    private function updateLanguage() {
        $languageManager = new LanguageManager();
        $languageFolders = $this->getLanguageFolder();

        $translations = array();
        foreach ($languageFolders as $languageFolder) {
            $languageFolder = rtrim($languageFolder, '/');
            $languageFolder .= '/';
            $translated = $languageManager->readLanguages($this->buildBasePath() . "repository/$languageFolder", $languageFolder);
            $translations = array_merge($translations, $translated);
        }

        file_put_contents($this->buildBasePath() . 'translation.ser', serialize($translations));
        echo $this->dataFolder . 'translation.ser';
    }

    /**
     * @return string The url to the remote Git repository
     */
    protected abstract function getRepositoryUrl();

    /**
     * @return string The default branch to pull
     */
    protected abstract function getBranch();

    /**
     * @return string The name of the extension
     */
    protected abstract function getName();

    /**
     * @return string Type of repository.
     */
    protected abstract function getType();

    /**
     * @return array|string Relative path to the language folder. i.e. lang/ for plugins
     */
    protected abstract function getLanguageFolder();


}
