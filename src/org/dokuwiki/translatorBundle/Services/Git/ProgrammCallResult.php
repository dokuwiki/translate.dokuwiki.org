<?php
namespace org\dokuwiki\translatorBundle\Services\Git;

class ProgrammCallResult {

    private $output;
    private $exitCode;

    function __construct($exitCode, $output) {
        $this->exitCode = $exitCode;
        $this->output = $output;
    }

    public function getExitCode() {
        return $this->exitCode;
    }

    public function getOutput() {
        return $this->output;
    }
}
