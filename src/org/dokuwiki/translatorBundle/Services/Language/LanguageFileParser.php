<?php
namespace org\dokuwiki\translatorBundle\Services\Language;

class LanguageFileParser {

    protected $content;
    protected $author;
    protected $lang;

    public static $MODE_PHP = 'php';
    public static $MODE_COMMENT_SINGLE_LINE = 'comment single line';
    public static $MODE_COMMENT_MULTI_LINE = 'comment multi line';
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
            } elseif ($mode === LanguageFileParser::$MODE_COMMENT_MULTI_LINE) {
                $mode = $this->processMultiLineComment();
            } elseif ($mode === LanguageFileParser::$MODE_COMMENT_SINGLE_LINE) {
                $mode = $this->processSingleLineComment();
            } elseif ($mode === LanguageFileParser::$MODE_LANG) {
                $mode = $this->processLang();
            } else {
                throw new LanguageParseException("Invalid syntax - no code execution allowed");
            }
        }

        return array();
    }

    public function processLang() {
        $key = $this->getString();
        $this->content = rtrim($this->content);

        $javaScriptLang = ($key === 'js');
        if ($javaScriptLang) {
            $this->content = preg_replace('/^\s*\]\s*\[\s*/', '', $this->content, 1, $found);
            if ($found === 0) {
                throw new LanguageParseException('Wrong key/value syntax in: ' . substr($this->content, 0, 50));
            }
            $key = $this->getString();
            $this->content = rtrim($this->content);
        }

        $this->content = preg_replace('/^\s*\]\s*=\s*/', '', $this->content, 1, $found);
        if ($found === 0) {
            throw new LanguageParseException('Wrong key/value syntax in: ' . substr($this->content, 0, 50));
        }
        $value = $this->getString();
        $this->content = rtrim($this->content);
        if (!isset($this->content[0]) || $this->content[0] !== ';') {
            throw new LanguageParseException('Wrong key/value syntax, expected command end or eof');
        }
        $this->shortContentBy(1);

        if ($javaScriptLang) {
            $this->lang['js'][$key] = $value;
        } else {
            $this->lang[$key] = $value;
        }
        return LanguageFileParser::$MODE_PHP;
    }

    public function getString() {
        $string = '';
        while (true) {
            $string .= $this->getFirstString();
            $this->content = ltrim($this->content);
            if (!isset($this->content[0]) || $this->content[0] !== '.') {
                break;
            }
            $this->shortContentBy(1);
            $this->content = ltrim($this->content);
        }
        return $string;
    }

    public function getFirstString() {
        $stringDelimiter = $this->content[0];
        if (!in_array($stringDelimiter, array('\'', '"'))) {
            throw new LanguageParseException("Content won't start with a string. Found " . $stringDelimiter);
        }
        $this->shortContentBy(1);

        $pos = null;
        $offset = 0;
        do {
            $pos = strpos($this->content, $stringDelimiter, $offset);
            if ($pos === false) {
                throw new LanguageParseException('String has no ending.');
            }

            if ($this->content[$pos-1] === '\\') {
                $offset = $pos+1;
                continue;
            }
            break;
        } while ($pos !== false);

        $string = substr($this->content, 0, $pos);
        $string = $this->escapeString($string, $stringDelimiter);
        $this->shortContentBy($pos+1);
        return $string;
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
            $line .= "\n";
            if(!preg_match('/\* @author (.+?)(?: <(.*?)>)?\n/i', $line, $matches)) {
                continue;
            }
            $this->author[$matches[1]] = isset($matches[2])?$matches[2]:'';
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

    /**
     * Escapes a string according to http://php.net/manual/en/language.types.string.php
     * @param string $string the string to escape
     * @param string $delimiter ' or "
     * @return string escaped string
     */
    public function escapeString($string, $delimiter) {
        if ($delimiter === "'") {
            return $this->escapeSingleQuoted($string);
        }
        return $this->escapeDoubleQuoted($string);
    }

    private function escapeSingleQuoted($string) {
        $string = str_replace('\\\\', '\\', $string);
        $string = str_replace('\\\'', '\'', $string);
        return $string;
    }

    private function escapeDoubleQuoted($string) {
        $string = str_replace('\\\\', '\\', $string);
        $string = str_replace('\\n', "\n", $string);
        $string = str_replace('\\r', "\r", $string);
        $string = str_replace('\\t', "\t", $string);
        $string = str_replace('\\v', "\v", $string);
        $string = str_replace('\\e', "\e", $string);
        $string = str_replace('\\f', "\f", $string);
        $string = str_replace('\\$', "\$", $string);
        $string = str_replace('\\"', "\"", $string);

        $matchCount = preg_match_all('/\\\\x([0-9A-Fa-f]{1,2})/', $string, $matches);
        if ($matchCount > 0) {
            for ($i = 0; $i < $matchCount; $i++) {
                $string = str_replace($matches[0][$i], chr(hexdec($matches[1][$i])), $string);
            }
        }

        $matchCount = preg_match_all('/\\\\([0-7]{1,3})/', $string, $matches);
        if ($matchCount > 0) {
            for ($i = 0; $i < $matchCount; $i++) {
                $string = str_replace($matches[0][$i], chr(octdec($matches[1][$i])), $string);
            }
        }

        return $string;
    }

}
