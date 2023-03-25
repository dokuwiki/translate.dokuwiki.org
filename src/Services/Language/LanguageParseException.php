<?php
namespace org\dokuwiki\translatorBundle\Services\Language;

use Exception;

class LanguageParseException extends Exception {

    private $lineNumber;
    private $fileName;

    /**
     * @param string $message
     * @param int $lineNumber
     * @param string $fileName
     */
    function __construct($message, $lineNumber, $fileName) {
        parent::__construct($message);
        $this->lineNumber = $lineNumber;
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getFileName() {
        return $this->fileName;
    }

    /**
     * @return int
     */
    public function getLineNumber() {
        return $this->lineNumber;
    }


}
