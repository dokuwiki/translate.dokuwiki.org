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
use App\Services\GitHostingProviderService;
use App\Services\GitHostingProviderStatusService;
use App\Services\GitHostingProviderException;
use App\Services\GitHub\GitHubCreatePullRequestException;
use App\Services\GitHub\GitHubForkException;
use App\Services\GitLab\GitLabCreateMergeRequestException;
use App\Services\GitLab\GitLabForkException;
use Github\Exception\MissingArgumentException;

class GitHostingProviderBehavior implements RepositoryBehavior
{
    /**
     * Git Service api, provider specific
     */
    protected GitHostingProviderService $api;

    /**
     * Service for status of the api
     */
    protected GitHostingProviderStatusService $status;

    /**
     * Text to be inserted as description for the pull request.
     * @see sendChange()
     */
    protected string $prBody = <<< EOT
        This pull request contains some translation updates.
        EOT;


    public function __construct(GitHostingProviderService $api, GitHostingProviderStatusService $statusService)
    {
        $this->api = $api;
        $this->status = $statusService;
    }

    /**
     * Create branch and push it to the remote fork, then submit a pull request
     *
     * @param GitRepository $tempGit temporary local git repository with the patch of the language update
     * @param TranslationUpdateEntity $update
     * @param GitRepository $forkedGit git repository cloned of the forked repository
     *
     * @throws GitAddException
     * @throws GitBranchException
     * @throws GitCheckoutException
     * @throws GitNoRemoteException
     * @throws GitPushException
     * @throws GitHubCreatePullRequestException|GitLabCreateMergeRequestException
     * @throws MissingArgumentException
     * @throws GitHostingProviderException
     */
    public function sendChange(GitRepository $tempGit, TranslationUpdateEntity $update, GitRepository $forkedGit): void
    {
        $remoteUrl = $forkedGit->getRemoteUrl();
        $tempGit->remoteAdd('remote_fork', $remoteUrl);
        $branchName = 'lang_update_' . $update->getId() . '_' . $update->getUpdated();
        $tempGit->branch($branchName);
        $tempGit->checkout($branchName);

        $tempGit->push('remote_fork', $branchName);

        $this->api->createPullRequest(
            $branchName,
            $update->getRepository()->getBranch(),
            $update->getRepository()->getUrl(),
            $remoteUrl,
            $update->getSubject(),
            $this->prBody
        );
    }

    /**
     * Fork original repo and return the fork's url.
     *
     * @param RepositoryEntity $repository
     * @return string Git clone URL of the fork
     *
     * @throws GitHubForkException|GitLabForkException
     * @throws GitHostingProviderException
     */
    public function createOriginURL(RepositoryEntity $repository): string
    {
        return $this->api->createFork($repository->getUrl());
    }

    /**
     * Remove the fork.
     *
     * @param GitRepository $forkedGit git repository cloned of the forked repository
     *
     * @throws GitNoRemoteException
     * @throws GitHostingProviderException
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
     * Check if Git Hosting provider is functional.
     *
     * @return bool
     */
    public function isFunctional(): bool
    {
        return $this->status->isFunctional();
    }

    /**
     * Get information about the open pull requests i.e. url and count
     *
     * @param RepositoryEntity $repository
     * @param LanguageNameEntity $language
     * @return array{count: int, listURL: string, title: string}
     *
     * @throws GitHostingProviderException
     */
    public function getOpenPRListInfo(RepositoryEntity $repository, LanguageNameEntity $language): array
    {
        return $this->api->getOpenPRListInfo($repository->getUrl(), $language->getCode());
    }

}
