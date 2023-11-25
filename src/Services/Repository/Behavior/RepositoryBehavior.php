<?php

namespace App\Services\Repository\Behavior;

use App\Entity\LanguageNameEntity;
use App\Entity\RepositoryEntity;
use App\Entity\TranslationUpdateEntity;
use App\Services\Git\GitAddException;
use App\Services\Git\GitBranchException;
use App\Services\Git\GitCheckoutException;
use App\Services\Git\GitCreatePatchException;
use App\Services\Git\GitNoRemoteException;
use App\Services\Git\GitPullException;
use App\Services\Git\GitPushException;
use App\Services\Git\GitRepository;
use App\Services\GitHub\GitHubCreatePullRequestException;
use App\Services\GitHub\GitHubForkException;
use App\Services\GitHub\GitHubServiceException;
use App\Services\GitLab\GitLabCreateMergeRequestException;
use App\Services\GitLab\GitLabForkException;
use App\Services\GitLab\GitLabServiceException;
use Github\Exception\MissingArgumentException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

interface RepositoryBehavior
{


    /**
     * Sent request to author of remote repository to include the change, applies the best method available
     *
     * @param GitRepository $tempGit temporary local git repository with the patch of the language update
     * @param TranslationUpdateEntity $update
     * @param GitRepository $forkedGit git repository cloned of the fork or the original repository
     *
     * Plain:
     * @throws GitCreatePatchException
     * @throws TransportExceptionInterface
     *
     * Github/GitLab:
     * @throws GitHubCreatePullRequestException|GitLabCreateMergeRequestException
     * @throws GitHubServiceException|GitLabServiceException
     * @throws GitAddException
     * @throws GitBranchException
     * @throws GitCheckoutException
     * @throws GitNoRemoteException
     * @throws GitPushException
     * @throws MissingArgumentException
     */
    public function sendChange(GitRepository $tempGit, TranslationUpdateEntity $update, GitRepository $forkedGit): void;

    /**
     * Return url of 'origin' repository
     * (which can be the original repository or e.g. a fork, that is forked firstly
     * in a translator tool account from the original)
     *
     * @param RepositoryEntity $repository
     * @return string git clone URL of the fork or original repository
     *
     * @throws GitHubForkException|GitLabForkException
     * @throws GitHubServiceException|GitLabServiceException
     */
    public function createOriginURL(RepositoryEntity $repository): string;

    /**
     * @param GitRepository $forkedGit git repository cloned of the fork or the original repository
     *
     * @throws GitHubServiceException|GitLabServiceException
     * @throws GitNoRemoteException
     */
    public function removeRemoteFork(GitRepository $forkedGit): void;

    /**
     * Update repository from original, and when applicable push to remote fork
     *
     * @param GitRepository $forkedGit git repository cloned of the forked or the original repository
     * @param RepositoryEntity $repository
     * @return bool true if the repository is changed
     *
     * @throws GitPullException
     * @throws GitPushException
     */
    public function pull(GitRepository $forkedGit, RepositoryEntity $repository): bool;

    /**
     * Update repository from original, and when applicable push to remote fork
     * Note: By using git reset --hard instead of pull, conflicts are prevented, it ASSUMES there are NO local changes
     * which needs merging.
     *
     * @param GitRepository $forkedGit git repository cloned of the forked or the original repository
     * @param RepositoryEntity $repository
     * @return bool true if the repository is changed
     *
     * @throws GitPullException
     * @throws GitPushException
     */
    public function reset(GitRepository $forkedGit, RepositoryEntity $repository): bool;

    /**
     * Check if remote repository is functional
     *
     * @return bool true if operational
     */
    public function isFunctional(): bool;

    /**
     * Get information about the open pull requests i.e. url and count
     *
     * @param RepositoryEntity $repository
     * @param LanguageNameEntity $language
     * @return array url, hosting name as url title, count
     *
     * @throws GitHubServiceException
     */
    public function getOpenPRListInfo(RepositoryEntity $repository, LanguageNameEntity $language): array;
}
