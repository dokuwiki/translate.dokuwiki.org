<?php
namespace org\dokuwiki\translatorBundle\Services\Git;

class ProgrammCallResult {

    private $output;
    private $exitCode;
    private $error;
    private $command;

    function __construct($exitCode, $output, $error, $command) {
        $this->exitCode = $exitCode;
        $this->output = $output;
        $this->error = $error;
        $this->command = $command;
    }

    public function getExitCode() {
        return $this->exitCode;
    }

    public function getOutput() {
        return $this->output;
    }

    public function getError() {
        return $this->error;
    }

    public function getCommand() {
        return $this->command;
    }
}
