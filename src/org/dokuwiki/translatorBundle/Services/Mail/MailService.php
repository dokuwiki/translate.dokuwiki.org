<?php
namespace org\dokuwiki\translatorBundle\Services\Mail;

use Monolog\Logger;
use Swift_Attachment;
use Swift_Mailer;
use Swift_Message;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class MailService {

    /**
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * @var Environment
     */
    private $template;

    /**
     * @var String
     */
    private $from;

    /**
     * @var Logger
     */
    private $logger;

    /*
     * @var \Swift_Message
     */
    private $lastMessage;

    /**
     * MailService constructor.
     * @param Swift_Mailer $mailer
     * @param Environment $twig
     * @param $from
     * @param Logger $logger
     */
    function __construct(Swift_Mailer $mailer, Environment $twig, $from, Logger $logger) {
        $this->mailer = $mailer;
        $this->template = $twig;
        $this->from = $from;
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
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendEmail($to, $subject, $template, $data = array()) {
        if ($to === '') return;
        $message = $this->createMessage($to, $subject, $template, $data);

        $this->send($message);
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
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendPatchEmail($to, $subject, $patch, $template, $data = array()) {
        $message = $this->createMessage($to, $subject, $template, $data);

        $attachment = new Swift_Attachment($patch, 'language.patch', 'text/plain');
        $message->attach($attachment);

        $this->send($message);
    }

    /**
     * Send the message
     *
     * @param Swift_Message $message
     */
    private function send(Swift_Message $message) {
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
     * @return Swift_Message
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function createMessage($to, $subject, $template, $data = array()) {
        $message = (new Swift_Message())
            ->setTo($to)
            ->setSubject($subject)
            ->setFrom($this->from)
            ->setBody($this->template->render($template, $data));
        $this->lastMessage = $message;
        return $message;
    }

    /**
     * Create log line for the sent mail
     *
     * @param Swift_Message $message
     */
    private function logMail(Swift_Message $message) {

        $context = array();
        $context['to'] = $message->getTo();
        $context['subject'] = $message->getSubject();
        $context['text'] = $message->getBody();

        $this->logger->debug('Sending mail "{subject}"', $context);

    }

    /**
     * @return string
     */
    public function getLastMessage() {
        return $this->lastMessage;
    }

}
