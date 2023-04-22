<?php
namespace App\Services\Mail;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailService {

    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Email
     */
    private $lastMessage;

    /**
     * MailService constructor.
     *
     * @param MailerInterface $mailer
     * @param LoggerInterface $logger
     */
    function __construct(MailerInterface $mailer, LoggerInterface $logger) {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }


    /**
     *
     *
     * @param string $to E-mail address
     * @param string $subject Subject of the mail
     * @param string $template The template name
     * @param array $data data for the template placeholders
     *
     * @throws TransportExceptionInterface
     */
    public function sendEmail($to, $subject, $template, $data = []) {
        if ($to === '') return;
        $email = $this->createEmail($to, $subject, $template, $data);

        $this->send($email);
    }

    /**
     * Send the patch by email
     *
     * @param string $to E-mail address
     * @param string $subject Subject of the mail
     * @param string $patch the created patch
     * @param string $template The template name
     * @param array $data data for the template placeholders
     *
     * @throws TransportExceptionInterface
     */
    public function sendPatchEmail($to, $subject, $patch, $template, $data = []) {
        $email = $this->createEmail($to, $subject, $template, $data);
        $email->attach($patch, 'language.patch', 'text/plain');
        $this->send($email);
    }

    /**
     * Send the message
     *
     * @param Email $message
     *
     * @throws TransportExceptionInterface
     */
    private function send(Email $message) {
        $this->logMail($message);
        $this->mailer->send($message);
    }

    /**
     * Create a message
     *
     * @param string $to E-mail address
     * @param string $subject Subject of the mail
     * @param string $template The template name
     * @param array $data data for the template placeholders
     * @return Email
     */
    private function createEmail($to, $subject, $template, $data = []) {
        $message = (new TemplatedEmail())
            ->to($to)
            ->subject($subject)
            ->textTemplate($template)
            ->context($data);
        $this->lastMessage = $message;
        return $message;
    }

    /**
     * Create log line for the sent mail
     *
     * @param Email $message
     */
    private function logMail(Email $message) {

        $context = [];
        $context['to'] = $message->getTo();
        $context['subject'] = $message->getSubject();
        $context['text'] = $message->getBody();

        $this->logger->debug('Sending mail "{subject}"', $context);

    }

    /**
     * @return Email
     */
    public function getLastMessage() {
        return $this->lastMessage;
    }

}
