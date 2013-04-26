<?php

namespace org\dokuwiki\translatorBundle\Services\Git;

class GitService {

    private $gitBinary;

    public function __construct($gitBinary) {
        $this->gitBinary = $gitBinary;
    }

    /**
     * Check if a folder is a git repository
     *
     * @param string $path
     * @return bool
     */
    public function isRepository($path) {
        return (file_exists($path));
    }

    public function getGitBinary() {
        return $this->gitBinary;
    }

    public function openRepository($path) {
        if (!$this->isRepository($path)) {
            throw new GitException("$path is no git repository");
        }
        return new GitRepository($this, $path);
    }

    public function createRepositoryFromRemote($source, $destination) {
        $repository = new GitRepository($this, $destination);
        $repository->cloneFrom($source, $destination);

        return $repository;
    }
}
