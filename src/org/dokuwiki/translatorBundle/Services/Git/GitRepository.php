<?php

namespace org\dokuwiki\translatorBundle\Services\Git;

use Symfony\Component\Process\Process;

class GitRepository {

    public static $PULL_CHANGED = 'changed';
    public static $PULL_UNCHANGED = 'unchanged';

    private $gitService;
    private $path;
    private $commandTimeout;

    public function __construct(GitService $gitService, $path, $commandTimeout) {
        $this->gitService = $gitService;
        $this->path = $path;
        $this->commandTimeout = $commandTimeout;
    }

    public function cloneFrom($source, $destination, $retries = 3) {
        try {
            $result = $this->run('clone', $source, $destination);
        } catch (GitCommandException $e) {
            throw new GitCloneException("Failed to clone $source to $destination in ", $this->path, $e);
        }

        while (true) {
            try {
                $this->run('config', '--local', 'core.pager', 'S'); // Don't use less on long outputs
                break;
            } catch (GitCommandException $e) {
                if ($retries == 0) {
                    throw new GitCloneException('Failed to configure local repository', $this->path, $e);
                }
                $retries--;
                sleep(10);
            }
        }
        return $result;
    }

    /**
     * Pull from remote repository
     * @param string $remote repository alias or url
     * @param string $branch remote branch to pull
     * @return string
     * @throws GitPullException
     */
    public function pull($remote = 'origin', $branch = 'master') {
        try {
            $result = $this->run('pull', $remote, $branch);
        } catch (GitCommandException $e) {
            throw new GitPullException("Failed to pull $remote/$branch", $this->path, $e);
        }

        // empty result -> new, contains already up2date -> unchanged, else updated
        if (strstr($result->getOutput(), 'Already up-to-date') !== false) {
            return GitRepository::$PULL_UNCHANGED;
        }
        return GitRepository::$PULL_CHANGED;
    }

    public function remoteAdd($name, $path) {
        try {
            return $this->run('remote', 'add', $name, $path);
        } catch (GitCommandException $e) {
            throw new GitAddException("Could not add remote $name $path", $this->path, $e);
        }
    }

    public function commit($message, $author) {
        try {
            return $this->run('commit', '-m', $message, '--author', $author);
        } catch (GitCommandException $e) {
            throw new GitCommitException('Failed to commit', $this->path, $e);
        }
    }

    public function createPatch($revision = 'HEAD~1') {
        try {
            $result = $this->run('format-patch', '--stdout', $revision);
            return $result->getOutput();
        } catch (GitCommandException $e) {
            throw new GitCreatePatchException('Failed to create patch', $this->path, $e);
        }
    }

    public function add($file) {
        return $this->run('add', $file);
    }

    public function push($origin, $branch) {
        try {
            return $this->run('push', $origin, $branch);
        } catch (GitCommandException $e) {
            throw new GitPushException("Failed to push to $origin/$branch", $this->path, $e);
        }
    }

    public function getRemoteUrl() {
        $config = $this->path . '/.git/config';
        if (!file_exists($config)) throw new GitNoRemoteException('Repo has no config', $this->path);

        $content = file_get_contents($config);
        if (!preg_match('/url = (git@\S*.?github.com\S*)/i', $content, $matches)) {
            throw new GitNoRemoteException('Repo has no remote configured', $this->path);
        }

        return $matches[1];
    }

    public function branch($name) {
        try {
            return $this->run('branch', $name);
        } catch (GitCommandException $e) {
            throw new GitBranchException("Failed to create branch $name", $this->path, $e);
        }
    }

    public function checkout($name) {
        try {
            return $this->run('checkout', $name);
        } catch (GitCommandException $e) {
            throw new GitCheckoutException("Failed to checkout $name", $this->path, $e);
        }
    }

    private function run() {
        $arguments = func_get_args();
        $command = array($this->gitService->getGitBinary());

        foreach ($arguments as $argument) {
            $command[] = escapeshellarg($argument);
        }

        $command = implode(' ', $command);
        $result = $this->runCommand($command);

        if ($result->getExitCode()) {
            throw new GitCommandException($result);
        }

        return $result;
    }

    private function runCommand($command) {
        if (file_exists($this->path)) {
            $process = new Process($command, $this->path);
        } else {
            $process = new Process($command);
        }
        $process->setTimeout($this->commandTimeout);
        $process->start();
        while($process->isRunning()) {
            $process->checkTimeout();
            usleep(1000000);
        }

        return new ProgrammCallResult(
            $process->getExitCode(),
            $process->getOutput(),
            $process->getErrorOutput(),
            $command
        );
    }
}
