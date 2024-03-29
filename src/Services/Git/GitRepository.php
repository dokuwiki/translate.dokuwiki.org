<?php

namespace App\Services\Git;

use Symfony\Component\Process\Process;

class GitRepository
{

    public const PULL_CHANGED = 'changed';
    public const PULL_UNCHANGED = 'unchanged';

    private GitService $gitService;
    private string $path;
    private ?float $commandTimeout;

    /**
     * GitRepository constructor.
     *
     * @param GitService $gitService
     * @param string $path folder containing git repository
     * @param float|null $commandTimeout max time a git command can run in sec, null disables timeout
     */
    public function __construct(GitService $gitService, string $path, ?float $commandTimeout)
    {
        $this->gitService = $gitService;
        $this->path = $path;
        $this->commandTimeout = $commandTimeout;
    }

    /**
     *
     *
     * @param string $source
     * @param string $destination
     * @param int $retries
     * @return ProgramCallResult
     *
     * @throws GitCloneException
     */
    public function cloneFrom(string $source, string $destination, int $retries = 3): ProgramCallResult
    {
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
     *
     * @param string $remote repository alias or git url
     * @param string $branch remote branch to pull
     * @return string GitRepository::PULL_UNCHANGED or GitRepository::PULL_CHANGED
     *
     * @throws GitPullException
     */
    public function pull(string $remote = 'origin', string $branch = 'master'): string
    {
        try {
            $result = $this->run('pull', '-f', $remote, $branch);
        } catch (GitCommandException $e) {
            throw new GitPullException("Failed to pull $remote/$branch", $this->path, $e);
        }

        // empty result -> new, contains already up2date -> unchanged, else updated
        if (strstr($result->getOutput(), 'Already up-to-date') !== false) {
            return GitRepository::PULL_UNCHANGED;
        }
        return GitRepository::PULL_CHANGED;
    }

    /**
     * Reset to fetch from remote repository, anything else is discarded. Assumes no changes locally.
     *
     * @param string $remote repository alias or git url
     * @param string $branch remote branch to pull
     * @return string GitRepository::PULL_UNCHANGED or GitRepository::PULL_CHANGED
     *
     * @throws GitPullException
     */
    public function reset(string $remote = 'origin', string $branch = 'master'): string
    {
        $fetchHeadBefore = '';
        $fetchHeadFile = $this->path . '/.git/FETCH_HEAD';
        if (file_exists($fetchHeadFile)) {
            $fetchHeadBefore = file_get_contents($fetchHeadFile);
        }

        try {
            $this->run('fetch', $remote, $branch);
            $this->run('reset', '--hard', 'FETCH_HEAD');
        } catch (GitCommandException $e) {
            throw new GitPullException("Failed to reset (pull) from $remote/$branch", $this->path, $e);
        }

        // compare FETCH_HEAD before and after to observe changes
        $fetchHeadAfter = file_get_contents($fetchHeadFile);
        if ($fetchHeadBefore === $fetchHeadAfter) {
            return GitRepository::PULL_UNCHANGED;
        }
        return GitRepository::PULL_CHANGED;
    }

    /**
     * @param string $name alias of the remote
     * @param string $path git url of the remote repository
     * @return ProgramCallResult
     *
     * @throws GitAddException
     */
    public function remoteAdd(string $name, string $path): ProgramCallResult
    {
        try {
            return $this->run('remote', 'add', $name, $path);
        } catch (GitCommandException $e) {
            throw new GitAddException("Could not add remote $name $path", $this->path, $e);
        }
    }

    /**
     * @param string $message
     * @param string $author name < email >
     * @return ProgramCallResult
     *
     * @throws GitCommitException
     */
    public function commit(string $message, string $author): ProgramCallResult
    {
        try {
            return $this->run('commit', '-m', $message, '--author', $author);
        } catch (GitCommandException $e) {
            throw new GitCommitException('Failed to commit', $this->path, $e);
        }
    }

    /**
     * @param string $revision commits since this revision
     * @return string patch
     *
     * @throws GitCreatePatchException
     */
    public function createPatch(string $revision = 'HEAD~1'): string
    {
        try {
            $result = $this->run('format-patch', '--stdout', $revision);
            return $result->getOutput();
        } catch (GitCommandException $e) {
            throw new GitCreatePatchException('Failed to create patch', $this->path, $e);
        }
    }

    /**
     * @param string $file path to new/changed file
     * @return ProgramCallResult
     *
     * @throws GitCommandException
     */
    public function add(string $file): ProgramCallResult
    {
        return $this->run('add', $file);
    }

    /**
     * @param string $origin alias of remote
     * @param string $branch branch name
     * @return ProgramCallResult
     *
     * @throws GitPushException
     */
    public function push(string $origin, string $branch): ProgramCallResult
    {
        try {
            return $this->run('push', '-f', $origin, $branch);
        } catch (GitCommandException $e) {
            throw new GitPushException("Failed to push to $origin/$branch", $this->path, $e);
        }
    }

    /**
     * @return string git url of configured remote (the first url, does not check which remote)
     *
     * @throws GitNoRemoteException
     */
    public function getRemoteUrl(): string
    {
        $config = $this->path . '/.git/config';
        if (!file_exists($config)) throw new GitNoRemoteException('Repo has no config', $this->path);

        $content = file_get_contents($config);
        if (!preg_match('/url = (git@\S*.?(?:github|gitlab).com\S*)/i', $content, $matches)) {
            throw new GitNoRemoteException('Repo has no remote configured', $this->path);
        }

        return $matches[1];
    }

    /**
     * Creates a branch
     *
     * @param string $name branch name
     * @return ProgramCallResult
     *
     * @throws GitBranchException
     */
    public function branch(string $name): ProgramCallResult
    {
        try {
            return $this->run('branch', $name);
        } catch (GitCommandException $e) {
            throw new GitBranchException("Failed to create branch $name", $this->path, $e);
        }
    }

    /**
     * @param string $name branch name
     * @return ProgramCallResult
     *
     * @throws GitCheckoutException
     */
    public function checkout(string $name): ProgramCallResult
    {
        try {
            return $this->run('checkout', $name);
        } catch (GitCommandException $e) {
            throw new GitCheckoutException("Failed to checkout $name", $this->path, $e);
        }
    }

    /**
     * @param string $arguments,... the arguments that will be arguments of the command
     * @return ProgramCallResult
     *
     * @throws GitCommandException
     */
    private function run(...$arguments): ProgramCallResult
    {
        $command = [$this->gitService->getGitBinary()];

        foreach ($arguments as $argument) {
            $command[] = $argument;
        }

        $result = $this->runCommand($command);

        if ($result->getExitCode()) {
            throw new GitCommandException($result);
        }

        return $result;
    }

    /**
     * @param array $command
     * @return ProgramCallResult
     */
    private function runCommand(array $command): ProgramCallResult
    {
        if (file_exists($this->path)) {
            $process = new Process($command, $this->path);
        } elseif (file_exists(dirname($this->path))) {
            $process = new Process($command, dirname($this->path));
        } else {
            return new ProgramCallResult(1, '', 'Folder with git repository does not exist', $command);
        }
        $process->setTimeout($this->commandTimeout);
        $process->start();
        while ($process->isRunning()) {
            $process->checkTimeout();
            usleep(1_000_000);
        }

        return new ProgramCallResult(
            $process->getExitCode(),
            $process->getOutput(),
            $process->getErrorOutput(),
            $command
        );
    }
}
