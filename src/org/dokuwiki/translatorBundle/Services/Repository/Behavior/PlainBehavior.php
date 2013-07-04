<?php

namespace org\dokuwiki\translatorBundle\Services\Repository\Behavior;

use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Entity\TranslationUpdateEntity;
use org\dokuwiki\translatorBundle\Services\Git\GitRepository;
use org\dokuwiki\translatorBundle\Services\Git\GitService;
use org\dokuwiki\translatorBundle\Services\Mail\MailService;
use org\dokuwiki\translatorBundle\Services\Repository\Repository;

class PlainBehavior implements RepositoryBehavior {

    /**
     * @var MailService
     */
    private $mailService;

    function __construct($mailService) {
        $this->mailService = $mailService;
    }


    function sendChange(GitRepository $git, TranslationUpdateEntity $update) {
        $patch = $git->createPatch();

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
}