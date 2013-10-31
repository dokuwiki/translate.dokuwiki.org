<?php

namespace org\dokuwiki\translatorBundle\Services\Repository\Behavior;

use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Entity\TranslationUpdateEntity;
use org\dokuwiki\translatorBundle\Services\Git\GitRepository;
use org\dokuwiki\translatorBundle\Services\Mail\MailService;

class PlainBehavior implements RepositoryBehavior {

    /**
     * @var MailService
     */
    private $mailService;

    function __construct($mailService) {
        $this->mailService = $mailService;
    }


    function sendChange(GitRepository $tempGit, TranslationUpdateEntity $update, GitRepository $originalGit) {
        $patch = $tempGit->createPatch();

        $this->mailService->sendPatchEmail(
            $update->getRepository()->getEmail(),
            'Language Update',
            $patch,
            'dokuwikiTranslatorBundle:Mail:languageUpdate.txt.twig',
            array('update' => $update)
        );
    }

    function createOriginURL(RepositoryEntity $repository) {
        return $repository->getUrl();
    }

    /**
     * Called before a pull
     * @param GitRepository $git
     * @param RepositoryEntity $repository
     * @return bool true if the repository is changed
     */
    function pull(GitRepository $git, RepositoryEntity $repository) {
        return $git->pull('origin', $repository->getBranch()) === GitRepository::$PULL_CHANGED;
    }

    function isFunctional() {
        return true;
    }
}