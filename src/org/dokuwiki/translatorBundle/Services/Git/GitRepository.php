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
        $result = $this->run('clone', $source, $destination);

        while (true) {
            try {
                $this->run('config', '--local', 'core.pager', 'S'); // Don't use less on long outputs
                break;
            } catch (GitException $e) {
                if ($retries == 0) {
                    throw $e;
                }
                $retries--;
                sleep(10);
            }
        }
        return $result;
    }

    public function pull($remote = 'origin', $branch = 'master') {
        $result = $this->run('pull', $remote, $branch);

        // empty result -> new, contains already up2date -> unchanged, else updated
        if (strstr($result->getOutput(), 'Already up-to-date') !== false) {
            return GitRepository::$PULL_UNCHANGED;
        }
        return GitRepository::$PULL_CHANGED;
    }

    public function remoteAdd($name, $path) {
        return $this->run('remote', 'add', $name, $path);
    }

    public function commit($message, $author) {
        return $this->run('commit', '-m', $message, '--author', $author);
    }

    public function createPatch($revision = 'HEAD~1') {
        $result = $this->run('format-patch', '--stdout', $revision);
        return $result->getOutput();
    }

    public function add($file) {
        return $this->run('add', $file);
    }

    public function push($origin, $branch) {
        return $this->run('push', $origin, $branch);
    }

    public function getRemoteUrl() {
        $config = $this->path . '/.git/config';
        if (!file_exists($config)) throw new GitException('No remote url found');

        $content = file_get_contents($config);
        if (!preg_match('/url = (git@\S*.?github.com\S*)/i', $content, $matches)) {
            throw new GitException('No remote url found');
        }

        return $matches[1];
    }

    public function branch($name) {
        return $this->run('branch', $name);
    }

    public function checkout($name) {
        return $this->run('checkout', $name);
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
            print_r($command);
            throw new GitException($result->getExitCode() . $result->getExitCode());
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
        $process->start();

        return new ProgrammCallResult($process->getExitCode(), $process->getOutput());
    }
}
