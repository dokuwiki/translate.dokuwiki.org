<?php


namespace org\dokuwiki\translatorBundle\Services\Mail;

interface MailService {
    public function sendEmail($to, $subject, $template, $data = array());

    public function sendPatchEmail($to, $subject, $patch, $template, $data = array());

    /**
     * @return string
     */
    public function getLastMessage();
}