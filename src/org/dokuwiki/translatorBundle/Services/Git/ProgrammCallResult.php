<?php
namespace org\dokuwiki\translatorBundle\Services\Git;

class ProgrammCallResult {

    private $error;
    private $output;
    private $exitCode;

    function __construct($error, $exitCode, $output) {
        $this->error = $error;
        $this->exitCode = $exitCode;
        $this->output = $output;
    }

    public function getError() {
        return $this->error;
    }

    public function getExitCode() {
        return $this->exitCode;
    }

    public function getOutput() {
        return $this->output;
    }
}
