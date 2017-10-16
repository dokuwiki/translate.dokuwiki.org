<?php

namespace org\dokuwiki\translatorBundle\Services\Repository\Behavior;

use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Entity\TranslationUpdateEntity;
use org\dokuwiki\translatorBundle\Services\Git\GitRepository;
use org\dokuwiki\translatorBundle\Services\GitHub\GitHubService;
use org\dokuwiki\translatorBundle\Services\GitHub\GitHubStatusService;

class GitHubBehavior implements RepositoryBehavior {

    /**
     * @var GitHubService
     */
    private $api;

    /**
     * @var GitHubStatusService
     */
    private $gitHubService;

    function __construct(GitHubService $api, GitHubStatusService $gitHubStatus) {
        $this->api = $api;
        $this->gitHubService = $gitHubStatus;
    }

    /**
     * Create branch and push it to remote, create subsequently pull request at Github
     *
     * @param GitRepository $tempGit temporary local git repository
     * @param TranslationUpdateEntity $update
     * @param GitRepository $originalGit
     */
    function sendChange(GitRepository $tempGit, TranslationUpdateEntity $update, GitRepository $originalGit) {

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
     * Fork at Github and return url of the fork
     *
     * @param RepositoryEntity $repository
     * @return string Git URL of the fork
     */
    function createOriginURL(RepositoryEntity $repository) {
        return $this->api->createFork($repository->getUrl());
    }

    /**
     * Update from original and push to fork
     *
     * @param GitRepository $git
     * @param RepositoryEntity $repository
     * @return bool true if the repository is changed
     */
    function pull(GitRepository $git, RepositoryEntity $repository) {
        $changed = $git->pull($repository->getUrl(), $repository->getBranch()) === GitRepository::$PULL_CHANGED;
        $git->push('origin', $repository->getBranch());
        return $changed;
    }

    /**
     * @return bool|null
     */
    function isFunctional() {
        return $this->gitHubService->isFunctional();
    }
}
