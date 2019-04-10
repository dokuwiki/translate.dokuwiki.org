<?php

namespace org\dokuwiki\translatorBundle\Services\Repository\Behavior;

use org\dokuwiki\translatorBundle\Entity\LanguageNameEntity;
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
     */
    public function pull(GitRepository $git, RepositoryEntity $repository) {
        $changed = $git->pull($repository->getUrl(), $repository->getBranch()) === GitRepository::$PULL_CHANGED;
        $git->push('origin', $repository->getBranch());
        return $changed;
    }

    /**
     * @return bool|null
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
     */
    public function getOpenPRListInfo(RepositoryEntity $repository, LanguageNameEntity $language) {
        return $this->api->getOpenPRListInfo($repository->getUrl(), $language->getCode());
    }
}
