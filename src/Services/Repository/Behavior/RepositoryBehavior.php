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
use App\Services\Git\GitPushException;
use App\Services\Git\GitRepository;
use App\Services\GitHub\GitHubCreatePullRequestException;
use App\Services\GitHub\GitHubForkException;
use App\Services\GitHub\GitHubServiceException;
use Github\Exception\MissingArgumentException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

interface RepositoryBehavior {


    /**
     * Sent request to author of remote repository to include the change, applies the best method available
     *
     * @param GitRepository $tempGit
     * @param TranslationUpdateEntity $update
     * @param GitRepository $originalGit
     *
     * Plain:
     * @throws GitCreatePatchException
     * @throws TransportExceptionInterface
     *
     * Github:
     * @throws GitHubCreatePullRequestException
     * @throws GitHubServiceException
     * @throws GitAddException
     * @throws GitBranchException
     * @throws GitCheckoutException
     * @throws GitNoRemoteException
     * @throws GitPushException
     * @throws MissingArgumentException
     */
    public function sendChange(GitRepository $tempGit, TranslationUpdateEntity $update, GitRepository $originalGit) : void;

    /**
     * Return url of 'origin' repository
     * (which can be the original repository or e.g. a fork, that is forked firstly
     * in a translator tool account from the original)
     *
     * @param RepositoryEntity $repository
     * @return string
     *
     * @throws GitHubForkException
     * @throws GitHubServiceException
     */
    public function createOriginURL(RepositoryEntity $repository) : string;

    /**
     * @param GitRepository $git
     */
    public function removeRemoteFork(GitRepository $git) : void;

    /**
     * Update repository from remote
     *
     * @param GitRepository $git
     * @param RepositoryEntity $repository
     * @return bool true if the repository is changed
     */
    public function pull(GitRepository $git, RepositoryEntity $repository) : bool;


    /**
     * Check if remote repository is functional
     *
     * @return bool
     */
    public function isFunctional() : bool;

    /**
     * Get information about the open pull requests i.e. url and count
     *
     * @param RepositoryEntity $repository
     * @param LanguageNameEntity $language
     * @return array
     */
    public function getOpenPRListInfo(RepositoryEntity $repository, LanguageNameEntity $language) : array;
}
