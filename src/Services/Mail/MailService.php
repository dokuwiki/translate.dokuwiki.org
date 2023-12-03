<?php
namespace App\Services\Mail;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MailService {

    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private String $lastMessage;

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
     * @param TemplatedEmail $message
     *
     * @throws TransportExceptionInterface
     */
    private function send(TemplatedEmail $message) {
        $this->mailer->send($message);
        $this->logMail($message);
    }

    /**
     * Create a message
     *
     * @param string $to E-mail address
     * @param string $subject Subject of the mail
     * @param string $template The template name
     * @param array $data data for the template placeholders
     * @return TemplatedEmail
     */
    private function createEmail($to, $subject, $template, $data = []) {
        $message = (new TemplatedEmail())
            ->to($to)
            ->subject($subject)
            ->textTemplate($template)
            ->context($data);

        $this->lastMessage = "To: $to; Subject: $subject; template: $template, data: " . var_export(array_keys($data), true);
        return $message;
    }

    /**
     * Create log line for the sent mail  //TODO move to Listener/subscriber? on MessageEvent
     *
     * @param TemplatedEmail $message
     */
    private function logMail(TemplatedEmail $message) {
        $context = [];
        $context['to'] = implode(', ', array_map(fn(Address $address) => $address->getAddress(), $message->getTo()));
        $context['subject'] = $message->getSubject();
        $context['template'] = $message->getTextTemplate();

        $this->logger->debug('Sending mail "{subject}"', $context);
    }

    /**
     * @return String
     */
    public function getLastMessage() {
        return $this->lastMessage;
    }

}
