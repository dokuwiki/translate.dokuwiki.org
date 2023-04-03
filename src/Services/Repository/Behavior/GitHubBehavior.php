<?php

namespace App\Services\Repository\Behavior;

use Github\Exception\MissingArgumentException;
use App\Entity\LanguageNameEntity;
use App\Entity\RepositoryEntity;
use App\Entity\TranslationUpdateEntity;
use App\Services\Git\GitAddException;
use App\Services\Git\GitBranchException;
use App\Services\Git\GitCheckoutException;
use App\Services\Git\GitNoRemoteException;
use App\Services\Git\GitPullException;
use App\Services\Git\GitPushException;
use App\Services\Git\GitRepository;
use App\Services\GitHub\GitHubCreatePullRequestException;
use App\Services\GitHub\GitHubForkException;
use App\Services\GitHub\GitHubService;
use App\Services\GitHub\GitHubServiceException;
use App\Services\GitHub\GitHubStatusService;

class GitHubBehavior implements RepositoryBehavior {

    /**
     * @var GitHubService
     */
    private $api;

    /**
     * @var GitHubStatusService
     */
    private $gitHubService;

    public function __construct(GitHubService $api, GitHubStatusService $gitHubStatus) {
        $this->api = $api;
        $this->gitHubService = $gitHubStatus;
    }

    /**
     * Create branch and push it to remote, create subsequently pull request at Github
     *
     * @param GitRepository $tempGit temporary local git repository
     * @param TranslationUpdateEntity $update
     * @param GitRepository $originalGit
     *
     * @throws GitHubCreatePullRequestException
     * @throws GitHubServiceException
     * @throws GitAddException
     * @throws GitBranchException
     * @throws GitCheckoutException
     * @throws GitNoRemoteException
     * @throws GitPushException
     * @throws MissingArgumentException
     */
    public function sendChange(GitRepository $tempGit, TranslationUpdateEntity $update, GitRepository $originalGit) {

        $remoteUrl = $originalGit->getRemoteUrl();
        $tempGit->remoteAdd('github', $remoteUrl);
        $branchName = 'lang_update_' . $update->getId() . '_' . $update->getUpdated();
        $tempGit->branch($branchName);
        $tempGit->checkout($branchName);

        $tempGit->push('github', $branchName);

        $this->api->createPullRequest($branchName, $update->getRepository()->getBranch(),
                $update->getLanguage(), $update->getRepository()->getUrl(), $remoteUrl);
    }

    /**
     * Fork original repo at Github and return url of the fork
     *
     * @param RepositoryEntity $repository
     * @return string Git URL of the fork
     *
     * @throws GitHubForkException
     * @throws GitHubServiceException
     */
    public function createOriginURL(RepositoryEntity $repository) {
        return $this->api->createFork($repository->getUrl());
    }

    /**
     * Update from original and push to fork of translate tool
     *
     * @param GitRepository $git
     * @param RepositoryEntity $repository
     * @return bool true if the repository is changed
     *
     * @throws GitPullException
     * @throws GitPushException
     */
    public function pull(GitRepository $git, RepositoryEntity $repository) {
        $changed = $git->pull($repository->getUrl(), $repository->getBranch()) === GitRepository::$PULL_CHANGED;
        $git->push('origin', $repository->getBranch());
        return $changed;
    }


    /**
     * Check if GitHub is functional
     *
     * @return bool
     */
    public function isFunctional() {
        return $this->gitHubService->isFunctional();
    }

    /**
     * Get information about the open pull requests i.e. url and count
     *
     * @param RepositoryEntity $repository
     * @param LanguageNameEntity $language
     * @return array
     *
     * @throws GitHubServiceException
     */
    public function getOpenPRListInfo(RepositoryEntity $repository, LanguageNameEntity $language) {
        return $this->api->getOpenPRListInfo($repository->getUrl(), $language->getCode());
    }
}
