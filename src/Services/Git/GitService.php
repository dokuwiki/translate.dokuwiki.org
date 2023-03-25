<?php

namespace org\dokuwiki\translatorBundle\Services\Git;

class GitService {

    private $gitBinary;
    private $commandTimeout;

    /**
     * GitService constructor.
     *
     * @param string $gitBinary path to the git executable
     * @param int $commandTimeout max time a git command can run in sec
     */
    public function __construct($gitBinary, $commandTimeout) {
        $this->gitBinary = $gitBinary;
        $this->commandTimeout = $commandTimeout;
    }

    /**
     * Check if folder where repository is (normally) stored exists
     *
     * @param string $path folder containing the git repository
     * @return bool
     */
    public function isRepository($path) {
        return (file_exists($path));
    }

    /**
     * @return string
     */
    public function getGitBinary() {
        return $this->gitBinary;
    }

    /**
     * Returns GitRepository object for given path
     *
     * @param string $path folder containing the git repository
     * @return GitRepository
     *
     * @throws GitException
     */
    public function openRepository($path) {
        if (!$this->isRepository($path)) {
            throw new GitException("no git repository", $path);
        }
        return new GitRepository($this, $path, $this->commandTimeout);
    }

    /**
     * Create in destination folder a git repository cloned from the local source path or remote source url
     *
     * @param string $source local path or remote url
     * @param string $destination path
     * @return GitRepository
     *
     * @throws GitCloneException
     */
    public function createRepositoryFromRemote($source, $destination) {
        $repository = new GitRepository($this, $destination, $this->commandTimeout);
        $repository->cloneFrom($source, $destination);

        return $repository;
    }
}
