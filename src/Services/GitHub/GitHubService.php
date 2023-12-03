<?php

namespace App\Services\GitHub;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Exception;
use Github\AuthMethod;
use Github\Client;
use Github\Exception\MissingArgumentException;
use Github\Exception\RuntimeException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpClient\HttplugClient;


class GitHubService
{

    private Client $client;
    private string $gitHubUrl;

    public function __construct(string $gitHubApiToken, string $dataFolder, string $gitHubUrl, bool $autoStartup = true)
    {
        $this->gitHubUrl = $gitHubUrl;
        if (!$autoStartup) {
            return;
        }

        $filesystemAdapter = new Local($dataFolder); // folders are relative to folder set here
        $filesystem = new Filesystem($filesystemAdapter);

        $pool = new FilesystemCachePool($filesystem);
        $pool->setFolder('cache/github');

        $this->client = Client::createWithHttpClient(
            new HttplugClient()
        );

        $this->client->addCache($pool);
        $this->client->authenticate($gitHubApiToken, null, AuthMethod::ACCESS_TOKEN);
    }

    /**
     * Create fork in our GitHub account
     *
     * @param string $url GitHub URL to create the fork from
     * @return string Git URL of the fork
     *
     * @throws GitHubForkException
     * @throws GitHubServiceException
     */
    public function createFork(string $url): string
    {
        [$user, $repository] = $this->getUsernameAndRepositoryFromURL($url);
        try {
            $result = $this->client->api('repo')->forks()->create($user, $repository);
        } catch (RuntimeException $e) {
            throw new GitHubForkException($e->getMessage() . " $user/$repository", 0, $e);
        }
        return $this->gitHubUrlHack($result['ssh_url']);
    }

    /**
     * Delete fork from our GitHub account
     *
     * @param string $remoteUrl git url of the forked repository
     *
     * @throws GitHubServiceException
     */
    public function deleteFork(string $remoteUrl): void
    {
        [$user, $repository] = $this->getUsernameAndRepositoryFromURL($remoteUrl);
        try {
            $this->client->api('repo')->remove($user, $repository);
        } catch (RuntimeException $e) {
            throw new GitHubServiceException($e->getMessage() . " $user/$repository", 0, $e);
        }
    }

    /**
     * @param string $patchBranch name of branch with language update
     * @param string $destinationBranch name of branch at remote
     * @param string $languageCode
     * @param string $url git url original upstream repository
     * @param string $patchUrl remote url
     *
     * @throws GitHubCreatePullRequestException
     * @throws GitHubServiceException
     * @throws MissingArgumentException
     */
    public function createPullRequest(string $patchBranch, string $destinationBranch, string $languageCode, string $url, $patchUrl): void
    {
        [$user, $repository] = $this->getUsernameAndRepositoryFromURL($url);
        [$repoName, ] = $this->getUsernameAndRepositoryFromURL($patchUrl);

        try {
            $this->client->api('pull_request')->create($user, $repository, [
                'base' => $destinationBranch,
                'head' => $repoName . ':' . $patchBranch,
                'title' => 'Translation update (' . $languageCode . ')',
                'body' => 'This pull request contains some translation updates.'
            ]);
        } catch (RuntimeException $e) {
            throw new GitHubCreatePullRequestException($e->getMessage() . " $user/$repository", 0, $e);
        }
    }

    /**
     * Get information about the open pull requests i.e. url and count
     *
     * @param string $url original git clone url
     * @param string $languageCode
     * @return array
     *
     * @throws GitHubServiceException
     * @throws Exception only if in 'test' environment
     */
    public function getOpenPRListInfo(string $url, string $languageCode): array
    {
        [$user, $repository] = $this->getUsernameAndRepositoryFromURL($url);

        $info = [
            'listURL' => '',
            'title' => '',
            'count' => 0
        ];

        try {
            $q = 'Translation update (' . $languageCode . ') in:title repo:' . $user . '/' . $repository . ' type:pr state:open';
            $results = $this->client->api('search')->issues($q);

            $info = [
                'listURL' => 'https://github.com/' . $user . '/' . $repository . '/pulls?q=is%3Apr+is%3Aopen+Translation+update+%28' . $languageCode . '%29',
                'title' => 'GitHub',
                'count' => (int)$results['total_count']
            ];
        } catch (Exception $e) {
            // skip intentionally, shown only for testing
            if ($_ENV['APP_ENV'] === 'test') {
                throw $e;
            }
        }

        return $info;
    }

    /**
     * @param string $url git clone url
     * @return array with user's account name, repository name
     *
     * @throws GitHubServiceException
     */
    private function getUsernameAndRepositoryFromURL(string $url): array
    {
        $result = preg_replace(
            '#^(https://github.com/|git@.*?github.com:|git://github.com/)(.*)\.git$#',
            '$2', $url, 1, $counter
        );
        if ($counter === 0) {
            throw new GitHubServiceException('Invalid GitHub clone URL: ' . $url);
        }
        return explode('/', $result);
    }

    /**
     * @param string $url git clone url
     * @return string modified git clone url
     */
    private function gitHubUrlHack(string $url): string
    {
        if ($this->gitHubUrl === 'github.com') {
            return $url;
        }
        return str_replace('github.com', $this->gitHubUrl, $url);
    }
}
