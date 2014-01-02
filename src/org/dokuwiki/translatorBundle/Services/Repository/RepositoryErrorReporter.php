<?php

namespace org\dokuwiki\translatorBundle\Services\Repository;

use Monolog\Logger;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Services\Git\GitCloneException;
use org\dokuwiki\translatorBundle\Services\Git\GitPullException;
use org\dokuwiki\translatorBundle\Services\Git\GitPushException;
use org\dokuwiki\translatorBundle\Services\GitHub\GitHubCreatePullRequestException;
use org\dokuwiki\translatorBundle\Services\GitHub\GitHubForkException;
use org\dokuwiki\translatorBundle\Services\GitHub\GitHubStatusService;
use org\dokuwiki\translatorBundle\Services\Language\LanguageParseException;
use org\dokuwiki\translatorBundle\Services\Language\NoDefaultLanguageException;
use org\dokuwiki\translatorBundle\Services\Language\NoLanguageFolderException;
use org\dokuwiki\translatorBundle\Services\Mail\MailService;

class RepositoryErrorReporter {

    /**
     * @var MailService
     */
    private $emailService;

    /**
     * @var Logger
     */
    private $logger;

    private $data;

    function __construct(MailService $emailService, Logger $logger) {
        $this->emailService = $emailService;
        $this->logger = $logger;
    }

    private function handleError(\Exception $e, Repository $repo, $update) {
        $this->data = array();
        $this->data['repo'] =  $repo;
        $this->data['exception'] = $e;
        if ($update) {
            $template = $this->determineEmailTemplateUpdate($e);
        } else {
            $template = $this->determineEmailTemplateTranslation($e);
        }

        $this->logger->err(sprintf('error during repository update (%s: %s)',
                get_class($e), $e->getMessage()));
        $this->logger->debug($e->getTraceAsString());
        if ($template !== '' && $repo->isFunctional()) {

            $this->emailService->sendEmail(
                $repo->getEntity()->getEmail(),
                'Error during import of ' . $repo->getEntity()->getDisplayName(),
                $template,
                $this->data
            );
            return $this->emailService->getLastMessage();
        } else {
            return 'Unknown error:' .get_class($e);
        }
    }

    public function handleTranslationError(\Exception $e, Repository $repo) {
        return $this->handleError($e, $repo, false);
    }

    private function determineEmailTemplateTranslation(\Exception $e) {
        if ($e instanceof GitHubCreatePullRequestException) {
            return 'dokuwikiTranslatorBundle:Mail:translationErrorPullRequest.txt.twig';
        }
        return '';
    }

    public function handleUpdateError(\Exception $e, Repository $repo) {
        return $this->handleError($e, $repo, true);
    }

    /**
     * @param \Exception $e
     * @return string
     */
    private function determineEmailTemplateUpdate(\Exception $e) {
        if ($e instanceof GitPullException) {
            return 'dokuwikiTranslatorBundle:Mail:importErrorUpdate.txt.twig';
        }

        if ($e instanceof GitPushException) {
            return 'dokuwikiTranslatorBundle:Mail:importErrorUpdate.txt.twig';
        }

        if ($e instanceof GitCloneException) {
            return 'dokuwikiTranslatorBundle:Mail:importErrorClone.txt.twig';
        }

        if ($e instanceof GitHubForkException) {
            return 'dokuwikiTranslatorBundle:Mail:importErrorClone.txt.twig';
        }

        if ($e instanceof NoLanguageFolderException) {
            return 'dokuwikiTranslatorBundle:Mail:importErrorNoLangFolder.txt.twig';
        }

        if ($e instanceof NoDefaultLanguageException) {
            return 'dokuwikiTranslatorBundle:Mail:importErrorNoDefaultTranslation.txt.twig';
        }

        if ($e instanceof LanguageParseException) {
            $this->data['fileName'] = basename(dirname($e->getFileName())) . '/' . basename($e->getFileName());
            $this->data['lineNumber'] = $e->getLineNumber();
            $this->data['message'] = $e->getMessage();

            return 'dokuwikiTranslatorBundle:Mail:importErrorLanguageParse.txt.twig';
        }
        return '';
    }
}