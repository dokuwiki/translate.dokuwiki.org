<?php
namespace App\Services\Git;


use Exception;

class GitException extends Exception {

    function __construct($msg, $path='', Exception $previous = null) {
        if($path) {
            $msg .= "\nPath: $path";
        }
        if($previous) {
            $msg .= "\n" . $previous->getMessage();
        }

        parent::__construct($msg, 0, $previous);
    }

}

