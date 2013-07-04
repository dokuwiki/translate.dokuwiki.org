<?php

namespace org\dokuwiki\translatorBundle\Services\Repository\Behavior;

use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Entity\TranslationUpdateEntity;
use org\dokuwiki\translatorBundle\Services\Git\GitRepository;
use org\dokuwiki\translatorBundle\Services\GitHub\GitHubService;

class GitHubBehavior implements RepositoryBehavior {

    /**
     * @var GitHubService
     */
    private $api;

    function __construct(GitHubService $api) {
        $this->api = $api;
    }


    function sendChange(GitRepository $git, TranslationUpdateEntity $update) {
/* TODO
        $remoteUrl = $update->getRepository()->getUrl();
        $remoteUrl = str_replace('github.com', 'dokuwiki-translation.github.com', $remoteUrl);
        $git->remoteAdd('github', $remoteUrl);

        $branchName = md5(time());

        $git->push('github', $branchName);

        // create pull request
  */
    }

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
}