<?php

namespace org\dokuwiki\translatorBundle\Services\Git;

class GitService {

    private $gitBinary;
    private $commandTimeout;

    public function __construct($gitBinary, $commandTimeout) {
        $this->gitBinary = $gitBinary;
        $this->commandTimeout = $commandTimeout;
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
            throw new GitException("no git repository", $path);
        }
        return new GitRepository($this, $path, $this->commandTimeout);
    }

    public function createRepositoryFromRemote($source, $destination) {
        $repository = new GitRepository($this, $destination, $this->commandTimeout);
        $repository->cloneFrom($source, $destination);

        return $repository;
    }
}
