<?php

namespace App\Services\Git;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GitService {

    private $gitBinary;
    private $commandTimeout;

    /**
     * GitService constructor.
     */
    public function __construct(ParameterBagInterface $params) {
        //path to the git executable
        $this->gitBinary = $params->get('app.gitBinary');
        //max time a git command can run in sec
        $this->commandTimeout = $params->get('app.commandTimeout');
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
