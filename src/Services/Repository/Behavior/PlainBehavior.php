<?php

namespace App\Services\Repository\Behavior;

use App\Entity\LanguageNameEntity;
use App\Entity\RepositoryEntity;
use App\Entity\TranslationUpdateEntity;
use App\Services\Git\GitCreatePatchException;
use App\Services\Git\GitPullException;
use App\Services\Git\GitRepository;
use App\Services\Mail\MailService;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PlainBehavior implements RepositoryBehavior {

    private MailService $mailService;

    public function __construct($mailService) {
        $this->mailService = $mailService;
    }


    /**
     * No possibility available to open pull request to remote repository, therefore a patch is sent by email
     *
     * @param GitRepository $tempGit
     * @param TranslationUpdateEntity $update
     * @param GitRepository $originalGit
     *
     * @throws GitCreatePatchException
     * @throws TransportExceptionInterface
     */
    public function sendChange(GitRepository $tempGit, TranslationUpdateEntity $update, GitRepository $originalGit): void
    {
        $patch = $tempGit->createPatch();

        $this->mailService->sendPatchEmail(
            $update->getRepository()->getEmail(),
            'Language Update',
            $patch,
            'mail/languageUpdate.txt.twig',
            ['update' => $update]
        );
    }

    /**
     * Return url of 'origin' repository, which is the original repository given
     *
     * @param RepositoryEntity $repository
     * @return string
     */
    public function createOriginURL(RepositoryEntity $repository): string
    {
        return $repository->getUrl();
    }

    public function removeRemoteFork(GitRepository $git): void
    {
        //no fork to delete
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
    public function pull(GitRepository $git, RepositoryEntity $repository): bool
    {
        return $git->pull('origin', $repository->getBranch()) === GitRepository::PULL_CHANGED;
    }

    public function isFunctional(): bool
    {
        return true;
    }

    /**
     * Get information about the open pull requests i.e. url and count
     *
     * @param RepositoryEntity $repository
     * @param LanguageNameEntity $language
     * @return array
     */
    public function getOpenPRListInfo(RepositoryEntity $repository, LanguageNameEntity $language): array
    {
        return [
            'count' => 0,
            'listURL' => '',
            'title' => ''
        ];
    }
}
