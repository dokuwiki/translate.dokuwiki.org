<?php
namespace org\dokuwiki\translatorBundle\Services\Mail;

class MailService {

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var \Twig_Environment
     */
    private $template;

    /**
     * @var String
     */
    private $from;

    function __construct(\Swift_Mailer $mailer, \Twig_Environment $twig, $from) {
        $this->mailer = $mailer;
        $this->template = $twig;
        $this->from = $from;
    }

    public function sendEmail($to, $subject, $template, $data = array()) {
        $message = $this->createMessage($to, $subject, $template, $data);

        $this->mailer->send($message);
    }

    public function sendPatchEmail($to, $subject, $patch, $template, $data = array()) {
        $message = $this->createMessage($to, $subject, $template, $data);

        $attachment = \Swift_Attachment::newInstance($patch, 'language.patch', 'text/plain');
        $message->attach($attachment);

        $this->mailer->send($message);
    }

    private function createMessage($to, $subject, $template, $data = array()) {
        $message = \Swift_Message::newInstance();
        $message->setTo($to);
        $message->setSubject($subject);
        $message->setFrom($this->from);
        $message->setBody($this->template->render($template, $data));
        return $message;
    }

}
