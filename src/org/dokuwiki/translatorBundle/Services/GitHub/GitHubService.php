<?php

namespace org\dokuwiki\translatorBundle\Services\GitHub;

interface GitHubService {

    /**
     * @param string $url GitHub URL to create the fork from
     * @throws GitHubForkException
     * @return string Git URL of the fork
     */
    public function createFork($url);

    public function createPullRequest($patchBranch, $branch, $languageCode, $url, $patchUrl);
}