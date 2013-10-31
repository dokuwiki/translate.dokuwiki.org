<?php

namespace org\dokuwiki\translatorBundle\Services\Repository\Behavior;

use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Entity\TranslationUpdateEntity;
use org\dokuwiki\translatorBundle\Services\Git\GitRepository;

interface RepositoryBehavior {

    function sendChange(GitRepository $tempGit, TranslationUpdateEntity $update, GitRepository $originalGit);

    function createOriginURL(RepositoryEntity $repository);

    /**
     * Called before a pull
     * @param GitRepository $git
     * @param RepositoryEntity $repository
     * @return bool true if the repository is changed
     */
    function pull(GitRepository $git, RepositoryEntity $repository);

    function isFunctional();

}
