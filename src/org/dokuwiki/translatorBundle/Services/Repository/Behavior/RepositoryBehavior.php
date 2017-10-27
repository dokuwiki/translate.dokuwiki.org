<?php

namespace org\dokuwiki\translatorBundle\Services\Repository\Behavior;

use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Entity\TranslationUpdateEntity;
use org\dokuwiki\translatorBundle\Services\Git\GitRepository;

interface RepositoryBehavior {

    function sendChange(GitRepository $tempGit, TranslationUpdateEntity $update, GitRepository $originalGit);

    /**
     * Return url of 'origin' repository
     * (which can be the original repository or e.g. a fork, that is forked firstly
     * in a translator tool account from the original)
     *
     * @param RepositoryEntity $repository
     * @return string
     */
    function createOriginURL(RepositoryEntity $repository);

    /**
     * Update repository from remote
     *
     * @param GitRepository $git
     * @param RepositoryEntity $repository
     * @return bool true if the repository is changed
     */
    function pull(GitRepository $git, RepositoryEntity $repository);


    function isFunctional();

}
