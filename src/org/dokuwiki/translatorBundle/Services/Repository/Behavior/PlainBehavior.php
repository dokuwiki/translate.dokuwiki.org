<?php

namespace org\dokuwiki\translatorBundle\Services\Repository\Behavior;

use App\Entity\LanguageNameEntity;
use App\Entity\RepositoryEntity;
use App\Entity\TranslationUpdateEntity;
use org\dokuwiki\translatorBundle\Services\Git\GitCreatePatchException;
use org\dokuwiki\translatorBundle\Services\Git\GitPullException;
use org\dokuwiki\translatorBundle\Services\Git\GitRepository;
use org\dokuwiki\translatorBundle\Services\Mail\MailService;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class PlainBehavior implements RepositoryBehavior {

    /**
     * @var MailService
     */
    private $mailService;

    public function __construct($mailService) {
        $this->mailService = $mailService;
    }


    /**
     * No possibility available to open pull request to remote repository, therefore a patch is sent by email
     *
     * @param GitRepository $tempGit
     * @param \App\Entity\TranslationUpdateEntity $update
     * @param GitRepository $originalGit
     *
     * @throws GitCreatePatchException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendChange(GitRepository $tempGit, TranslationUpdateEntity $update, GitRepository $originalGit) {
        $patch = $tempGit->createPatch();

        $this->mailService->sendPatchEmail(
            $update->getRepository()->getEmail(),
            'Language Update',
            $patch,
            'dokuwikiTranslatorBundle:Mail:languageUpdate.txt.twig',
            array('update' => $update)
        );
    }

    /**
     * Return url of 'origin' repository, which is the original repository given
     *
     * @param \App\Entity\RepositoryEntity $repository
     * @return string
     */
    public function createOriginURL(RepositoryEntity $repository) {
        return $repository->getUrl();
    }

    /**
     * Update repository from remote
     *
     * @param GitRepository $git
     * @param RepositoryEntity $repository
     * @return bool true if the repository is changed
     *
     * @throws GitPullException
     */
    public function pull(GitRepository $git, RepositoryEntity $repository) {
        return $git->pull('origin', $repository->getBranch()) === GitRepository::$PULL_CHANGED;
    }

    public function isFunctional() {
        return true;
    }

    /**
     * Get information about the open pull requests i.e. url and count
     *
     * @param \App\Entity\RepositoryEntity $repository
     * @param \App\Entity\LanguageNameEntity $language
     * @return array
     */
    public function getOpenPRListInfo(RepositoryEntity $repository, LanguageNameEntity $language) {
        return array(
            'count' => 0,
            'listURL' => ''
        );
    }
}
