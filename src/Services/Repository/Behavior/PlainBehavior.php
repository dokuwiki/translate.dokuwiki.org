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

class PlainBehavior implements RepositoryBehavior
{

    private MailService $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }


    /**
     * No possibility available to open pull request to remote repository, therefore a patch is sent by email
     *
     * @param GitRepository $tempGit temporary local git repository with the patch of the language update
     * @param TranslationUpdateEntity $update
     * @param GitRepository $forkedGit git repository cloned the original repository
     *
     * @throws GitCreatePatchException
     * @throws TransportExceptionInterface
     */
    public function sendChange(GitRepository $tempGit, TranslationUpdateEntity $update, GitRepository $forkedGit): void
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
     * Return url of 'origin' repository, which is here the original repository given
     *
     * @param RepositoryEntity $repository
     * @return string git clone url
     */
    public function createOriginURL(RepositoryEntity $repository): string
    {
        return $repository->getUrl();
    }

    public function removeRemoteFork(GitRepository $forkedGit): void
    {
        //no fork to delete
    }

    /**
     * Update repository from remote
     *
     * @param GitRepository $forkedGit
     * @param RepositoryEntity $repository
     * @return bool true if the repository is changed
     *
     * @throws GitPullException
     */
    public function pull(GitRepository $forkedGit, RepositoryEntity $repository): bool
    {
        return $forkedGit->pull('origin', $repository->getBranch()) === GitRepository::PULL_CHANGED;
    }

    /**
     * Update repository from remote (assumes there ar no local changes)
     *
     * @param GitRepository $forkedGit
     * @param RepositoryEntity $repository
     * @return bool true if the repository is changed
     *
     * @throws GitPullException
     */
    public function reset(GitRepository $forkedGit, RepositoryEntity $repository): bool
    {
        return $forkedGit->reset('origin', $repository->getBranch()) === GitRepository::PULL_CHANGED;
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
     * @return array{count: int, listURL: string, title: string}
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
