<?php
namespace org\dokuwiki\translatorBundle\Services\Git;

use org\dokuwiki\translatorBundle\Services\Git\ProgrammCallResult;

class GitException extends \Exception {

    private $result;

    function __construct($result) {
        $this->result = $result;
    }

    public function __toString() {
        if (is_string($this->result)) {
            return $this->result;
        }
        $string = 'Return Code: ' . $this->result->getExitCode();
        $string.= "\n";
        $string.= 'Output: ' . $this->result->getOutput();
        $string.= "\n";
        $string.= 'STDERR: ' . $this->result->getError();
        $string.= "\n";
        return $string;
    }

}

