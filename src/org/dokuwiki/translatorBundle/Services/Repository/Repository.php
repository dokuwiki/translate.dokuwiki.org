<?php
namespace org\dokuwiki\translatorBundle\Services\Repository;

use Symfony\Component\DependencyInjection\Container;

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
        if ($changed) {
            // TODO parse changes
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

    protected abstract function getRepositoryUrl();
    protected abstract function getBranch();
    protected abstract function getName();
    protected abstract function getType();
}
