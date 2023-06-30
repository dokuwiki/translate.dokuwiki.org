<?php

namespace App\Services\Repository\Behavior;

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
use App\Services\GitLab\GitLabCreateMergeRequestException;
use App\Services\GitLab\GitLabForkException;
use App\Services\GitLab\GitLabService;
use App\Services\GitLab\GitLabServiceException;
use App\Services\GitLab\GitLabStatusService;

class GitLabBehavior implements RepositoryBehavior
{

    private GitLabService $api;
    private GitLabStatusService $gitLabStatus;

    public function __construct(GitLabService $api, GitLabStatusService $gitLabStatus) {
        $this->api = $api;
        $this->gitLabStatus = $gitLabStatus;
    }

    /**
     * Create branch and push it to remote, create subsequently pull request at GitLab
     *
     * @param GitRepository $tempGit temporary local git repository with the patch of the language update
     * @param TranslationUpdateEntity $update
     * @param GitRepository $forkedGit git repository cloned of the fork
     *
     * @throws GitLabCreateMergeRequestException
     * @throws GitLabServiceException
     * @throws GitAddException
     * @throws GitBranchException
     * @throws GitCheckoutException
     * @throws GitNoRemoteException
     * @throws GitPushException
     */
    public function sendChange(GitRepository $tempGit, TranslationUpdateEntity $update, GitRepository $forkedGit): void
    {
        $remoteUrl = $forkedGit->getRemoteUrl();
        $tempGit->remoteAdd('gitlab', $remoteUrl);
        $branchName = 'lang_update_' . $update->getId() . '_' . $update->getUpdated();
        $tempGit->branch($branchName);
        $tempGit->checkout($branchName);

        $tempGit->push('gitlab', $branchName);

        $this->api->createPullRequest(
            $branchName, $update->getRepository()->getBranch(),
            $update->getLanguage(), $update->getRepository()->getUrl(), $remoteUrl
        );
    }

    /**
     * Fork original repo at GitLab and return url of the fork
     *
     * @param RepositoryEntity $repository
     * @return string git clone URL of the fork
     *
     * @throws GitLabForkException
     * @throws GitLabServiceException
     */
    public function createOriginURL(RepositoryEntity $repository): string
    {
        return $this->api->createFork($repository->getUrl());
    }


    /**
     * Remove the fork on GitLab
     *
     * @param GitRepository $forkedGit git repository cloned of the fork repository
     *
     * @throws GitLabServiceException
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
     * @param GitRepository $forkedGit git repository cloned of the fork repository
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
     * Check if GitLab is functional
     *
     * @return bool
     */
    public function isFunctional(): bool
    {
        return $this->gitLabStatus->isFunctional();
    }

    /**
     * Get information about the open pull requests i.e. url and count
     *
     * @param RepositoryEntity $repository
     * @param LanguageNameEntity $language
     * @return array
     *
     * @throws GitLabServiceException
     */
    public function getOpenPRListInfo(RepositoryEntity $repository, LanguageNameEntity $language): array
    {
        return $this->api->getOpenPRListInfo($repository->getUrl(), $language->getCode());
    }

}