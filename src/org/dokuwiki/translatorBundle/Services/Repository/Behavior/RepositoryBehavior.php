<?php

namespace org\dokuwiki\translatorBundle\Services\Repository\Behavior;

use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Entity\TranslationUpdateEntity;
use org\dokuwiki\translatorBundle\Services\Git\GitRepository;

interface RepositoryBehavior {

    function sendChange(GitRepository $git, TranslationUpdateEntity $update);

    function createOriginURL(RepositoryEntity $repository);

}
