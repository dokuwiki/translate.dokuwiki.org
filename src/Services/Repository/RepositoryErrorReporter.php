<?php

namespace App\Services\Repository;

use App\Services\GitLab\GitLabCreateMergeRequestException;
use App\Services\GitLab\GitLabForkException;
use App\Services\GitLab\GitLabServiceException;
use DateTime;
use Exception;
use App\Services\Git\GitCloneException;
use App\Services\Git\GitPullException;
use App\Services\Git\GitPushException;
use App\Services\GitHub\GitHubCreatePullRequestException;
use App\Services\GitHub\GitHubForkException;
use App\Services\GitHub\GitHubServiceException;
use App\Services\Language\LanguageParseException;
use App\Services\Language\NoDefaultLanguageException;
use App\Services\Language\NoLanguageFolderException;
use App\Services\Mail\MailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RepositoryErrorReporter
{

    private MailService $emailService;
    private LoggerInterface $logger;
    private array $data;

    function __construct(MailService $emailService, LoggerInterface $logger)
    {
        $this->emailService = $emailService;
        $this->logger = $logger;
    }

    /**
     * General error handler function
     *
     * @param Exception $e
     * @param Repository $repo
     * @param bool $update true if repository fork update, false if sending submitted translation
     * @return string
     *
     * @throws TransportExceptionInterface
     */
    private function handleError(Exception $e, Repository $repo, bool $update): string
    {
        $this->data = [];
        $this->data['repository'] = $repo->getEntity();
        $this->data['exception'] = $e;
        if ($update) {
            $template = $this->determineEmailTemplateUpdate($e);
        } else {
            $template = $this->determineEmailTemplateTranslation($e);
        }

        $file = '';
        if (isset($this->data['fileName'])) {
            $file = 'in file: ' . $this->data['fileName'] . '(' . $this->data['lineNumber'] . ')';
        }
        $shortMessage = sprintf(
            'Error during ' . ($update ? 'repository' : 'translation') . ' update (%s: %s) %s',
            get_class($e),
            $e->getMessage(),
            $file
        );
        $this->logger->error($shortMessage);
        $this->logger->debug($e->getTraceAsString());
        if ($template !== '') {
            $isNormal = true;
            if ($repo->isFunctional() && $isNormal = $this->isNormalErrorRate($repo)) {
                $repo->getEntity()->setErrorCount($repo->getEntity()->getErrorCount() + 1);

                $this->emailService->sendEmail(
                    $repo->getEntity()->getEmail(),
                    'Error during import of ' . $repo->getEntity()->getDisplayName(),
                    $template,
                    $this->data
                );
                $errorMsg = $shortMessage . "\n" . $this->emailService->getLastMessage();
            } else {
                //no mailing or storing of error needed
                $message = $isNormal ?
                    'No error mail sent, GitHub or GitLab is not functional'
                    : 'No error mail sent, too high error rate (more than 5 per 8 min)';
                $this->logger->debug($message . ' (' . get_class($e) . ')');
                return $shortMessage;
            }

        } else {
            $errorMsg = 'Unknown error:' . "\n" . $shortMessage;
        }
        $time = new DateTime();
        $date = $time->format('D d M Y H:i:s P');
        $repo->getEntity()->addErrorMsg('--- ' . $date . "\n" . $errorMsg);
        return $shortMessage;
    }

    /**
     * Handle errors during sending of a submitted translation
     *
     * @param Exception $e
     * @param Repository $repo
     * @return string
     *
     * @throws TransportExceptionInterface
     */
    public function handleTranslationError(Exception $e, Repository $repo): string
    {
        return $this->handleError($e, $repo, false);
    }

    /**
     * Handle errors during creation/update of local repository fork
     *
     * @param Exception $e
     * @param Repository $repo
     * @return string
     *
     * @throws TransportExceptionInterface
     */
    public function handleUpdateError(Exception $e, Repository $repo): string
    {
        return $this->handleError($e, $repo, true);
    }

    /**
     * Returns an email template for exceptions that needs attention of extension author
     *
     * @param Exception $e
     * @return string template referrer
     */
    private function determineEmailTemplateTranslation(Exception $e): string
    {
        if ($e instanceof GitHubCreatePullRequestException) {
            return 'mail/translationErrorPullRequest.txt.twig';
        }
        if ($e instanceof GitLabCreateMergeRequestException) {
            return 'mail/translationErrorMergeRequest.txt.twig';
        }
        return '';
    }

    /**
     * Returns an email template for exceptions that needs attention of extension author
     *
     * @param Exception $e
     * @return string template referrer
     */
    private function determineEmailTemplateUpdate(Exception $e): string
    {
        if ($e instanceof GitPullException) {
            return 'mail/importErrorUpdate.txt.twig';
        }

        if ($e instanceof GitPushException) {
            return 'mail/importErrorUpdate.txt.twig';
        }

        if ($e instanceof GitHubServiceException) {
            return 'mail/importErrorGitHubUrl.txt.twig';
        }

        if ($e instanceof GitLabServiceException) {
            return 'mail/importErrorGitLabUrl.txt.twig';
        }

        if ($e instanceof GitCloneException) {
            return 'mail/importErrorClone.txt.twig';
        }

        if ($e instanceof GitHubForkException) {
            return 'mail/importErrorClone.txt.twig';
        }

        if ($e instanceof GitLabForkException) {
            return 'mail/importErrorClone.txt.twig';
        }

        if ($e instanceof NoLanguageFolderException) {
            return 'mail/importErrorNoLangFolder.txt.twig';
        }

        if ($e instanceof NoDefaultLanguageException) {
            return 'mail/importErrorNoDefaultTranslation.txt.twig';
        }

        if ($e instanceof LanguageParseException) {
            $this->data['fileName'] = $e->getFileName();
            $this->data['lineNumber'] = $e->getLineNumber();
            $this->data['message'] = $e->getMessage();

            return 'mail/importErrorLanguageParse.txt.twig';
        }
        return '';
    }

    /**
     * If error rate is too high (>counts within certain delta time) it returns false.
     * It extends it upto the point that no other error occur in the delta time.
     *
     * @param Repository $repo
     * @return bool
     */
    private function isNormalErrorRate(Repository $repo): bool
    {
        $maxErrorCount = 5; //tool tries max 10 per run
        $maxDeltaTime = 8 * 60; //tool tries every 5 min new run

        //reset if too long ago
        [$count, $startTime] = $this->getCounterData($repo);
        if (time() - $startTime > $maxDeltaTime) {
            $count = 0;
        }

        //count is zero resets time as well
        if ($count === 0) {
            $startTime = time();
        }

        $count++;

        $isNormal = true;
        // if too many in given delta time
        if ($count > $maxErrorCount) {
            // if rate is too high, reset time to extend period
            // (occurs up to the point that the delta time becomes too large.)
            $startTime = time();

            $isNormal = false;
        }
        $this->storeCounterData($repo, $count, $startTime);
        return $isNormal;
    }

    private function getCounterData(Repository $repo): array
    {
        $file = $repo->buildDataDirectoryPath() . 'errorcountertime';
        if (file_exists($file)) {
            $data = array_pad(explode('#', file_get_contents($file)), 2, 0);
            return [(int)$data[0], (int)$data[1]];
        } else {
            return [0, 0];
        }
    }

    private function storeCounterData(Repository $repo, $count, $startTime): void
    {
        file_put_contents($repo->buildDataDirectoryPath() . 'errorcountertime', $count . '#' . $startTime);
    }
}
