<?php

namespace App\Services\GitLab;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Exception;
use Gitlab\Api\MergeRequests;
use Gitlab\Client;
use Gitlab\Exception\RuntimeException;
use Gitlab\HttpClient\Builder;
use Http\Client\Common\Plugin\LoggerPlugin;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;


class GitLabService
{
    private string $gitLabUrl;
    private Client $client;
    private string $projectIdFolder;

    function __construct($gitLabApiToken, $dataFolder, $gitLabUrl, LoggerInterface $httpLogger, $autoStartup = true) {
        $this->gitLabUrl = $gitLabUrl;
        if (!$autoStartup) {
            return;
        }

        $filesystemAdapter = new Local($dataFolder); // folders are relative to folder set here
        $filesystem        = new Filesystem($filesystemAdapter);

        $pool = new FilesystemCachePool($filesystem);
        $pool->setFolder('gitlab_cache');


        $loggerPlugin = new LoggerPlugin($httpLogger); //==new Logger('http')
        $builder = new Builder();
        $builder->addCache($pool);
        $builder->addPlugin($loggerPlugin);
        $this->client = new Client($builder);

        $this->client->authenticate($gitLabApiToken, Client::AUTH_HTTP_TOKEN);
    }


    /**
     * @param string $url GitLab URL to create the fork from
     * @return string Git URL of the fork
     *
     * @throws GitLabForkException
     * @throws GitLabServiceException
     */
    public function createFork(string $url) : string
    {
        [$user, $repository] = $this->getUsernameAndRepositoryFromURL($url);
        try {
            $result = $this->client->projects()->fork("$user/$repository");
        } catch (RuntimeException $e) {
            throw new GitLabForkException($e->getMessage()." $user/$repository", 0, $e);
        }

        $this->storeProjectIdOfUpstream($result['forked_from_project']['id']);
        return $this->gitLabUrlHack($result['ssh_url_to_repo']);
    }

    public function setProjectIdFolder(string $projectIdFolder)
    {
        $this->projectIdFolder = $projectIdFolder;
    }

    /**
     * Stores the project id of the upstream project
     * Cannot be stored in repository folder, because it did not yet exists
     *
     * @param int $projectId
     * @return void
     */
    private function storeProjectIdOfUpstream(int $projectId) : void
    {
        if (!is_dir($this->projectIdFolder)) {
            mkdir($this->projectIdFolder, 0777, true);
        }
        file_put_contents($this->projectIdFolder . 'gitlab_project_id_upstream', $projectId);
    }

    private function getProjectIdOfUpstream() : int
    {
        return (int) file_get_contents($this->projectIdFolder . 'gitlab_project_id_upstream');
    }

    /**
     * @param string $url
     * @return array
     *
     * @throws GitLabServiceException
     */
    public function getUsernameAndRepositoryFromURL(string $url) : array
    {
        $result = preg_replace('#^(https://gitlab.com/|git@.*?gitlab.com:|git://gitlab.com/)(.*)\.git$#', '$2', $url, 1, $counter);
        if ($counter === 0) {
            throw new GitLabServiceException('Invalid GitLab clone URL: ' . $url);
        }
        return explode('/', $result);
    }

    public function gitLabUrlHack(string $url) : string
    {
        if ($this->gitLabUrl === 'gitlab.com') {
            return $url;
        }
        return str_replace('gitlab.com', $this->gitLabUrl, $url);
    }

    /**
     * @param string $remoteUrl
     *
     * @throws GitLabServiceException
     */
    public function deleteFork(string $remoteUrl) : void
    {
         [$user, $repository] = $this->getUsernameAndRepositoryFromURL($remoteUrl);
        try {
            $this->client->projects()->remove("$user/$repository");

            $fs = new \Symfony\Component\Filesystem\Filesystem();
            $fs->remove($this->projectIdFolder);
        } catch (RuntimeException $e) {
            throw new GitLabServiceException($e->getMessage()." $user/$repository", 0, $e);
        }
    }


    /**
     * @param string $patchBranch name of branch with language update
     * @param string $branch name of branch at remote
     * @param string $languageCode
     * @param string $url remote url
     * @param string $patchUrl remote url
     *
     * @throws GitLabCreatePullRequestException
     * @throws GitLabServiceException
     */
    public function createPullRequest(string $patchBranch, string $branch, string $languageCode, string $url, string $patchUrl) : void
    {
        [$userUpstream, $repositoryUpstream] = $this->getUsernameAndRepositoryFromURL($url);
        [$userFork, $repositoryFork] = $this->getUsernameAndRepositoryFromURL($patchUrl);

        try {
            $this->client->mergeRequests()->create(
                "$userFork/$repositoryFork",
                $patchBranch,
                $branch,
                "Translation update ($languageCode)",
                [
                    'description' => 'This pull request contains some translation updates.',
                    'target_project_id' => $this->getProjectIdOfUpstream(),
                    'remove_source_branch' => true
                ]
            );
        } catch (RuntimeException $e) {
            throw new GitLabCreatePullRequestException($e->getMessage() . " $userUpstream/$repositoryUpstream", 0, $e);
        }
    }

    /**
     * Get information about the open pull requests i.e. url and count
     *
     * @param string $urlUpstream remote url
     * @param string $languageCode
     * @return array
     *
     * @throws GitLabServiceException
     */
    public function getOpenPRListInfo(string $urlUpstream, string $languageCode) : array
    {
        [$user, $repository] = $this->getUsernameAndRepositoryFromURL($urlUpstream);

        $info = [
            'listURL' => '',
            'title' => '',
            'count' => 0
        ];

        try {
            $results = $this->client->mergeRequests()->all(
                "$user/$repository",
                [
                    'scope' => 'all',
                    'state' => MergeRequests::STATE_OPENED,
                    'search' => "Translation update ($languageCode)"
                ]
            );
            $info = [
                'listURL' => "https://gitlab.com/$user/$repository/-/merge_requests?scope=all&state=opened&search=Translation+update+%28$languageCode%29",
                'title' => 'GitLab',
                'count' => count($results)
            ];
        } catch (Exception $e) {
            // skip intentionally
            // throw new GitLabServiceException($e->getMessage());
        }
        return $info;
    }

}