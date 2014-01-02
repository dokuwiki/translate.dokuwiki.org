<?php

namespace org\dokuwiki\translatorBundle\Services\GitHub;

use Github\Client;
use Github\Exception\RuntimeException;
use Github\HttpClient\CachedHttpClient;

class GitHubServiceImpl implements GitHubService {

    private $token;
    private $client;
    private $gitHubUrl;

    function __construct($gitHubApiToken, $dataFolder, $gitHubUrl, $autoStartup = true) {
        $this->gitHubUrl = $gitHubUrl;
        if (!$autoStartup) {
            return;
        }
        $this->token = $gitHubApiToken;
        $this->client = new Client(
            new CachedHttpClient(array('cache_dir' => "$dataFolder/githubcache"))
        );
        $this->client->authenticate($gitHubApiToken, null, Client::AUTH_URL_TOKEN);
    }

    /**
     * @param string $url GitHub URL to create the fork from
     * @throws GitHubForkException
     * @return string Git URL of the fork
     */
    public function createFork($url) {
        list($user, $repository) = $this->getUsernameAndRepositoryFromURL($url);
        try {
            $result = $this->client->api('repo')->forks()->create($user, $repository);
        } catch (RuntimeException $e) {
            throw new GitHubForkException('', 0, $e);
        }
        return $this->gitHubUrlHack($result['ssh_url']);
    }

    public function getUsernameAndRepositoryFromURL($url) {
        $result = preg_replace('#^(https://github.com/|git@.*?github.com:|git://github.com/)(.*)\.git$#', '$2', $url, 1, $counter);
        if ($counter === 0) {
            throw new GitHubServiceException('Invalid GitHub URL: ' . $url);
        }
        $result = explode('/', $result);

        return $result;
    }

    public function gitHubUrlHack($url) {
        if ($this->gitHubUrl === 'github.com') return $url;
        return str_replace('github.com', $this->gitHubUrl, $url);
    }

    public function createPullRequest($patchBranch, $branch, $languageCode, $url, $patchUrl) {
        list($user, $repository) = $this->getUsernameAndRepositoryFromURL($url);
        list($repoName, $ignored) = $this->getUsernameAndRepositoryFromURL($patchUrl);

        try {
            $this->client->api('pull_request')->create($user, $repository, array(
                'base'  => $branch,
                'head'  => $repoName.':'.$patchBranch,
                'title' => 'Translation update ('.$languageCode.')',
                'body'  => 'This pull request contains some translation updates.'
            ));
        } catch (RuntimeException $e) {
            throw new GitHubCreatePullRequestException('', 0, $e);
        }
    }

}