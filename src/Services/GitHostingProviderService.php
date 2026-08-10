<?php

namespace App\Services;

use App\Services\GitHub\GitHubCreatePullRequestException;
use App\Services\GitHub\GitHubForkException;
use App\Services\GitHub\GitHubService;
use App\Services\GitLab\GitLabCreateMergeRequestException;
use App\Services\GitLab\GitLabForkException;
use App\Services\GitLab\GitLabService;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use Exception;
use Github\Client as GithubClient;
use Github\Exception\MissingArgumentException;
use Gitlab\Client as GitlabClient;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

/**
 * Parent Class for Git Hosting Provider Services.
 *
 * @see GitHubService, GitLabService
 */
abstract class GitHostingProviderService
{

    /**
     * PCRE pattern to parse repository and username from URL.
     * Must be defined by child class.
     * @see getUsernameAndRepositoryFromURL()
     */
    protected const REGEX_REPO_USER = '';

    /**
     * Hosting Provider identifier.
     * Must be defined by child class.
     */
    protected string $provider;

    /**
     * Git Repository client API.
     * Actual type depends on child class.
     * @var GithubClient|GitlabClient
     */
    protected $client;

    /**
     * Repository URL
     */
    protected string $url;

    public function __construct(string $apiToken, string $dataFolder, string $url, bool $autoStartup = true)
    {
        $this->url = $url;
    }

    protected function getCachePool(string $dataFolder): FilesystemCachePool
    {
        // folders are relative to folder set here
        $filesystemAdapter = new Local($dataFolder);
        $filesystem = new Filesystem($filesystemAdapter);

        $pool = new FilesystemCachePool($filesystem);
        $pool->setFolder('cache/' . strtolower($this->provider));

        return $pool;
    }

    /**
     * Create fork in our Hosting Provider account
     *
     * @param string $url URL to create the fork from
     * @return string Git URL of the fork
     *
     * @throws GitHubForkException|GitLabForkException
     * @throws GitHostingProviderException
 */
    abstract public function createFork(string $url): string;

    /**
     * Delete fork from our Hosting Provider account
     *
     * @param string $remoteUrl Git url of the forked repository
     *
     * @throws GitHostingProviderException
     */
    abstract public function deleteFork(string $remoteUrl): void;

    /**
     * @param string $patchBranch name of branch with language update
     * @param string $destinationBranch name of branch at remote
     * @param string $url git url original upstream repository
     * @param string $patchUrl remote url
     * @param string $title Title for the pull request
     * @param string $body Text to be inserted as description for the pull request
     *
     * @throws GitHubCreatePullRequestException|GitLabCreateMergeRequestException
     * @throws MissingArgumentException
     * @throws GitHostingProviderException
 */
    abstract public function createPullRequest(string $patchBranch, string $destinationBranch, string $url, string $patchUrl, string $title, string $body): void;

    /**
     * Get information about the open pull requests i.e. url and count
     *
     * @param string $urlUpstream original git clone url
     * @param string $languageCode
     * @return array{count: int, listURL: string, title: string}
     *
     * @throws GitHostingProviderException
     * @throws Exception only if in 'test' environment
     */
    abstract public function getOpenPRListInfo(string $urlUpstream, string $languageCode): array;

    /**
     * @param string $url git clone url
     * @return array with user's account name, repository name
     *
     * @throws GitHostingProviderException
     */
    protected function getUsernameAndRepositoryFromURL(string $url): array
    {
        $result = preg_replace($this::REGEX_REPO_USER, '$2', $url, 1, $counter);
        if ($counter === 0) {
            throw new GitHostingProviderException('Invalid ' . $this->provider . ' clone URL: ' . $url, $this->provider);
        }
        return explode('/', $result);
    }

}
