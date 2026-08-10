<?php

namespace App\Services\GitLab;

use App\Services\GitHostingProviderService;
use App\Services\GitHostingProviderException;
use Exception;
use Gitlab\Api\MergeRequests;
use Gitlab\Client;
use Gitlab\Exception\RuntimeException;
use Gitlab\HttpClient\Builder;
use Http\Client\Common\Plugin\LoggerPlugin;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;


class GitLabService extends GitHostingProviderService
{
    const REGEX_REPO_USER = '#^(https://gitlab.com/|git@.*?gitlab.com:|git://gitlab.com/)(.*)\.git$#';

    protected string $provider = 'GitLab';

    /**
     * @var Client
     */
    protected $client;

    private string $projectIdFolder;

    public function __construct(string $apiToken, string $dataFolder, string $url, LoggerInterface $httpLogger, bool $autoStartup = true)
    {
        parent::__construct($apiToken, $dataFolder, $url, $autoStartup);
        if (!$autoStartup) {
            return;
        }

        $loggerPlugin = new LoggerPlugin($httpLogger); //==new Logger('http')
        $builder = new Builder();
        $builder->addCache($this->getCachePool($dataFolder));
        $builder->addPlugin($loggerPlugin);
        $this->client = new Client($builder);

        $this->client->authenticate($apiToken, Client::AUTH_HTTP_TOKEN);
    }

    public function setProjectIdFolder(string $projectIdFolder): void
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
    private function storeProjectIdOfUpstream(int $projectId): void
    {
        if (!is_dir($this->projectIdFolder)) {
            mkdir($this->projectIdFolder, 0777, true);
        }
        file_put_contents($this->projectIdFolder . 'gitlab_project_id_upstream', $projectId);
    }

    private function getProjectIdOfUpstream(): int
    {
        return (int)file_get_contents($this->projectIdFolder . 'gitlab_project_id_upstream');
    }

    /**
     * @inheritDoc
     *
     * @throws GitLabForkException
     * @throws GitHostingProviderException
     */
    public function createFork(string $url): string
    {
        [$user, $repository] = $this->getUsernameAndRepositoryFromURL($url);
        try {
            $result = $this->client->projects()->fork("$user/$repository");
        } catch (RuntimeException $e) {
            throw new GitLabForkException($e->getMessage() . " $user/$repository", 0, $e);
        }

        $this->storeProjectIdOfUpstream($result['forked_from_project']['id']);
        return $this->gitLabUrlHack($result['ssh_url_to_repo']);
    }

    /**
     * @inheritDoc
     *
     * @throws GitHostingProviderException
     */
    public function deleteFork(string $remoteUrl): void
    {
        [$user, $repository] = $this->getUsernameAndRepositoryFromURL($remoteUrl);
        try {
            $this->client->projects()->remove("$user/$repository");

            $fs = new Filesystem();
            $fs->remove($this->projectIdFolder);
        } catch (RuntimeException $e) {
            throw new GitHostingProviderException($e->getMessage() . " $user/$repository", $this->provider, 0, $e);
        }
    }


    /**
     * @inheritDoc
     *
     * @throws GitLabCreateMergeRequestException
     * @throws GitHostingProviderException
     */
    public function createPullRequest(string $patchBranch, string $destinationBranch, string $url, string $patchUrl, string $title, string $body): void
    {
        [$userUpstream, $repositoryUpstream] = $this->getUsernameAndRepositoryFromURL($url);
        $idUpstream = $this->getProjectIdOfUpstream();
        [$userFork, $repositoryFork] = $this->getUsernameAndRepositoryFromURL($patchUrl);

        try {
            $this->client->mergeRequests()->create(
                "$userFork/$repositoryFork",
                $patchBranch,
                $destinationBranch,
                $title,
                [
                    'description' => $body,
                    'target_project_id' => $idUpstream,
                    'remove_source_branch' => true
                ]
            );
        } catch (RuntimeException $e) {
            throw new GitLabCreateMergeRequestException($e->getMessage() . " $userUpstream/$repositoryUpstream (id: $idUpstream)", 0, $e);
        }
    }

    /**
     * @inheritDoc
     *
     * @throws Exception only if in 'test' environment
     * @throws GitHostingProviderException
     */
    public function getOpenPRListInfo(string $urlUpstream, string $languageCode): array
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
                'count' => is_countable($results) ? count($results) : 0
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
     * @return string modified git clone url
     */
    private function gitLabUrlHack(string $url): string
    {
        if ($this->url === 'gitlab.com') {
            return $url;
        }
        return str_replace('gitlab.com', $this->url, $url);
    }
}