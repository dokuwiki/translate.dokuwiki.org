<?php
namespace App\Services\Git;

class ProgramCallResult {

    private string $output;
    private ?int $exitCode;
    private string $error;
    private string $command;

    function __construct($exitCode, $output, $error, array $command) {
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
