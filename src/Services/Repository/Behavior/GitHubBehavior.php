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

class GitHubBehavior implements RepositoryBehavior
{

    private GitHubService $api;
    private GitHubStatusService $gitHubStatus;

    public function __construct(GitHubService $api, GitHubStatusService $gitHubStatus)
    {
        $this->api = $api;
        $this->gitHubStatus = $gitHubStatus;
    }

    /**
     * Create branch and push it to remote, create subsequently pull request at Github
     *
     * @param GitRepository $tempGit temporary local git repository with the patch of the language update
     * @param TranslationUpdateEntity $update
     * @param GitRepository $forkedGit git repository cloned of the forked repository
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
    public function sendChange(GitRepository $tempGit, TranslationUpdateEntity $update, GitRepository $forkedGit): void
    {

        $remoteUrl = $forkedGit->getRemoteUrl();
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
     * @return string Git clone URL of the fork
     *
     * @throws GitHubForkException
     * @throws GitHubServiceException
     */
    public function createOriginURL(RepositoryEntity $repository): string
    {
        return $this->api->createFork($repository->getUrl());
    }

    /**
     * Remove the fork on GitHub
     *
     * @param GitRepository $forkedGit git repository cloned of the forked repository
     *
     * @throws GitHubServiceException
     * @throws GitNoRemoteException
     */
    public function removeRemoteFork(GitRepository $forkedGit): void
    {
        $remoteUrl = $forkedGit->getRemoteUrl();
        $this->api->deleteFork($remoteUrl);
    }

    /**
     * Update from original and push to fork of translate tool
     *
     * @param GitRepository $forkedGit git repository cloned of the forked repository
     * @param RepositoryEntity $repository
     * @return bool true if the repository is changed
     *
     * @throws GitPullException
     * @throws GitPushException
     */
    public function pull(GitRepository $forkedGit, RepositoryEntity $repository): bool
    {
        $changed = $forkedGit->pull($repository->getUrl(), $repository->getBranch()) === GitRepository::PULL_CHANGED;
        $forkedGit->push('origin', $repository->getBranch());
        return $changed;
    }

    /**
     * Update from original and push to fork of translate tool (assumes there are no local changes)
     *
     * @param GitRepository $forkedGit git repository cloned of the forked repository
     * @param RepositoryEntity $repository
     * @return bool true if the repository is changed
     *
     * @throws GitPullException
     * @throws GitPushException
     */
    public function reset(GitRepository $forkedGit, RepositoryEntity $repository): bool
    {
        $changed = $forkedGit->reset($repository->getUrl(), $repository->getBranch()) === GitRepository::PULL_CHANGED;
        $forkedGit->push('origin', $repository->getBranch());
        return $changed;
    }

    /**
     * Check if GitHub is functional
     *
     * @return bool
     */
    public function isFunctional(): bool
    {
        return $this->gitHubStatus->isFunctional();
    }

    /**
     * Get information about the open pull requests i.e. url and count
     *
     * @param RepositoryEntity $repository
     * @param LanguageNameEntity $language
     * @return array{count: int, listURL: string, title: string}
     *
     * @throws GitHubServiceException
     */
    public function getOpenPRListInfo(RepositoryEntity $repository, LanguageNameEntity $language): array
    {
        return $this->api->getOpenPRListInfo($repository->getUrl(), $language->getCode());
    }

}
