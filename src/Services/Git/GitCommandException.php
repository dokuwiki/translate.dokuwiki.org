<?php

namespace App\Services\Git;

use Exception;

class GitCommandException extends Exception
{

    public ProgramCallResult $result;

    function __construct(ProgramCallResult $result)
    {
        $this->result = $result;

        $msg = "Command: " . $this->result->getCommand() . "\n";
        $msg .= "Return : " . $this->result->getExitCode() . "\n";
        $msg .= "STDOUT : " . $this->result->getOutput() . "\n";
        $msg .= "STDERR : " . $this->result->getError();

        parent::__construct($msg);
    }
}

