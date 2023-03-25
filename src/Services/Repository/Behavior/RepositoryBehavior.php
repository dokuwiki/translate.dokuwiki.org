<?php

namespace App\Services\Repository\Behavior;

use App\Entity\LanguageNameEntity;
use App\Entity\RepositoryEntity;
use App\Entity\TranslationUpdateEntity;
use App\Services\Git\GitRepository;
use App\Services\GitHub\GitHubForkException;
use App\Services\GitHub\GitHubServiceException;

interface RepositoryBehavior {


    /**
     * Sent request to author of remote repository to include the change, applies the best method available
     *
     * @param GitRepository $tempGit
     * @param TranslationUpdateEntity $update
     * @param GitRepository $originalGit
     * @return mixed
     */
    public function sendChange(GitRepository $tempGit, TranslationUpdateEntity $update, GitRepository $originalGit);

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
    public function createOriginURL(RepositoryEntity $repository);

    /**
     * Update repository from remote
     *
     * @param GitRepository $git
     * @param RepositoryEntity $repository
     * @return bool true if the repository is changed
     */
    public function pull(GitRepository $git, RepositoryEntity $repository);


    /**
     * Check if remote repository is functional
     *
     * @return mixed
     */
    public function isFunctional();

    /**
     * Get information about the open pull requests i.e. url and count
     *
     * @param \App\Entity\RepositoryEntity $repository
     * @param \App\Entity\LanguageNameEntity $language
     * @return array
     */
    public function getOpenPRListInfo(RepositoryEntity $repository, LanguageNameEntity $language);
}
