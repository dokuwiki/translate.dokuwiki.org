<?php

namespace App\Services\Git;

class ProgramCallResult
{

    private string $output;
    /**
     * @var int|null exit status code, null if not terminated
     */
    private ?int $exitCode;
    private string $error;
    private string $command;

    function __construct(?int $exitCode, string $output, string $error, array $command)
    {
        $this->exitCode = $exitCode;
        $this->output = $output;
        $this->error = $error;
        $this->command = implode(' ', $command);
    }

    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getCommand(): string
    {
        return $this->command;
    }
}
