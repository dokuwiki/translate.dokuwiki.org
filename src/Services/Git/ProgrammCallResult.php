<?php
namespace App\Services\Git;

class ProgrammCallResult {

    /** @var string */
    private $output;

    /** @var int|null */
    private $exitCode;

    /** @var string */
    private $error;

    /** @var string */
    private $command;

    function __construct($exitCode, $output, $error, $command) {
        $this->exitCode = $exitCode;
        $this->output = $output;
        $this->error = $error;
        $this->command = implode(' ', $command);
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
