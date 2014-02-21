<?php
namespace org\dokuwiki\translatorBundle\Services\Git;
use org\dokuwiki\translatorBundle\Services\Git\ProgrammCallResult;

class GitCommandException extends \Exception {
    public $result;

    function __construct(ProgrammCallResult $result) {
        $this->result = $result;

        $msg = "Command: " . $this->result->getCommand() . "\n";
        $msg.= "Return : " . $this->result->getExitCode() . "\n";
        $msg.= "STDOUT : " . $this->result->getOutput() . "\n";
        $msg.= "STDERR : " . $this->result->getError();

        parent::__construct($msg);
    }
}

