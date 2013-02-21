<?php
namespace org\dokuwiki\translatorBundle\Services\Language;

class LanguageFileParser {

    protected $content;
    protected $author;
    protected $lang;

    public static $MODE_PHP = 'php';
    public static $MODE_COMMENT_SINGLE_LINE = 'comment single line';
    public static $MODE_COMMENT_MULTI_LINE = 'comment multi line';
    public static $MODE_STRING = 'string';
    public static $MODE_LANG = 'lang';
    public static $MODE_PHP_UNKNOWN = 'php unknown';

    public function loadFile($file) {
        if (!is_file($file)) {
            throw new LanguageFileDoseNotExistException();
        }
        $this->content = trim(file_get_contents($file));
    }

    public static function parseLangPHP($file) {
        $parser = new LanguageFileParser();
        $parser->loadFile($file);
        return $parser->parse();
    }

    public function parse() {
        $this->author = array();
        $this->lang = array();

        $this->goToStart();

        $mode = LanguageFileParser::$MODE_PHP;
        while (strlen($this->content) !== 0) {
            if ($mode === LanguageFileParser::$MODE_PHP) {
                $mode = $this->determineNextMode();
                continue;
            } elseif (LanguageFileParser::$MODE_STRING) {

            } elseif ($mode === LanguageFileParser::$MODE_COMMENT_MULTI_LINE) {
                $mode = $this->processMultiLineComment();
            } elseif ($mode === LanguageFileParser::$MODE_COMMENT_SINGLE_LINE) {
                $mode = $this->processSingleLineComment();
            } elseif ($mode === LanguageFileParser::$MODE_PHP_UNKNOWN) {

            } else {
                // trim first character -> unmatched
                $this->content = substr($this->content, 0, 1);
            }
        }

        return array();
    }

    public function processSingleLineComment() {
        $endOfLine = strpos($this->content, "\n");
        if ($endOfLine === false) {
            $this->content = '';
            return LanguageFileParser::$MODE_PHP;
        }
        $this->content = substr($this->content, $endOfLine+1);

        return LanguageFileParser::$MODE_PHP;
    }

    public function processMultiLineComment() {
        $end = strpos($this->content, '*/');
        if ($end === false) {
            throw new LanguageParseException('multi line comment not closed');
        }
        $comment = substr($this->content, 0, $end);
        $commentLines = explode("\n", $comment);
        foreach($commentLines as $line) {
            $line = ltrim($line);
            if(!preg_match('/\* @author (.*?) <(.*?)>/i', $line, $matches)) {
                continue;
            }
            $this->author[$matches[1]] = $matches[2];
        }

        $this->content = substr($this->content, $end + 2);
        return LanguageFileParser::$MODE_PHP;
    }

    public function determineNextMode() {
        $this->content = ltrim($this->content);
        if ($this->contentStartsWith('/*')) {
            $this->shortContentBy(2);
            return LanguageFileParser::$MODE_COMMENT_MULTI_LINE;
        }

        if ($this->contentStartsWith('//')) {
            $this->shortContentBy(2);
            return LanguageFileParser::$MODE_COMMENT_SINGLE_LINE;
        }

        if ($this->contentStartsWith('$lang[')) {
            $this->shortContentBy(6);
            return LanguageFileParser::$MODE_LANG;
        }
        return LanguageFileParser::$MODE_PHP_UNKNOWN;
    }

    function goToStart() {
        $phpStart = strpos($this->content, '<?php');
        if ($phpStart === -1) {
            throw new LanguageParseException('No PHP start found');
        }
        $this->content = substr($this->content, $phpStart + 5);
    }

    private function contentStartsWith($needle) {
        return $this->stringStartsWith($this->content, $needle);
    }

    private function stringStartsWith($haystack, $needle) {
        return !strncmp($haystack, $needle, strlen($needle));
    }

    private function shortContentBy($length) {
        $this->content = substr($this->content, $length);
    }


}
