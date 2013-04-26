<?php
namespace org\dokuwiki\translatorBundle\Services\Repository;

use org\dokuwiki\translatorBundle\Services\Git\ProgrammCallResult;

class GitException extends \Exception {

    private $result;

    function __construct(ProgrammCallResult $result) {
        $this->result = $result;
    }

    public function __toString() {
        $string = 'Return Code: ' . $this->result->getExitCode();
        $string.= "\n";
        $string.= 'Output: ' . $this->result->getOutput();
        $string.= "\n";
        $string.= 'STDERR: ' . $this->result->getError();
        $string.= "\n";
        return $string;
    }

}

