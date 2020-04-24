<?php
namespace org\dokuwiki\translatorBundle\Services\Language;

class LanguageFileParser {


    /**
     * @var string content of language file
     */
    protected $content;

    /**
     * @var string
     */
    protected $header;

    /**
     * @var AuthorList
     */
    protected $author;

    /**
     * @var array associated array with per key the language string
     */
    protected $lang;

    /**
     * @var int Total number of lines of language file
     */
    protected $totalLineNumbers;

    /**
     * @var string Path to language file
     */
    protected $file = '';

    /**
     * @var string Stores trimmed ending of content
     */
    protected $trimmedEnding;

    public static $MODE_PHP = 'php';
    public static $MODE_COMMENT_SINGLE_LINE = 'comment single line';
    public static $MODE_COMMENT_MULTI_LINE = 'comment multi line';
    public static $MODE_LANG = 'lang';
    public static $MODE_PHP_END = 'php end';
    public static $MODE_PHP_UNKNOWN = 'php unknown';

    /**
     * Load content from file
     *
     * @param $file
     *
     * @throws LanguageFileDoesNotExistException
     */
    public function loadFile($file) {
        if (!is_file($file)) {
            throw new LanguageFileDoesNotExistException();
        }
        $this->file = $file;
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        $this->totalLineNumbers = count($lines) - 1;

        $content = rtrim($content);
        $position = strlen($content);
        $this->trimmedEnding = substr($this->content, $position);
        $this->content = ltrim($content);
    }

    /**
     * Parse a .php language file
     *
     * @param string $file
     * @return LanguageFileParser
     *
     * @throws LanguageFileDoesNotExistException
     * @throws LanguageParseException
     */
    public static function parseLangPHP($file) {
        $parser = new LanguageFileParser();
        $parser->loadFile($file);
        return $parser->parse();
    }

    /**
     * Parse the loaded content
     *
     * @return $this
     *
     * @throws LanguageParseException
     */
    public function parse() {
        $this->author = new AuthorList();
        $this->lang = array();
        $this->header = '';

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
            } elseif ($mode === LanguageFileParser::$MODE_PHP_END) {
                $this->content = trim($this->content);
                if (!empty($this->content)) {
                    throw $this->createException("Nothing allowed behind ?>");
                }
            } else {
                throw $this->createException("No code execution allowed. ");
            }
        }

        return $this;
    }

    /**
     * Process a content line starting with $lang[
     *
     * @return string LanguageFileParser::$MODE_PHP
     *
     * @throws LanguageParseException
     */
    public function processLang() {
        $key = $this->getString();
        $this->content = rtrim($this->content);

        $javaScriptLang = ($key === 'js');
        if ($javaScriptLang) {
            $this->content = preg_replace('/^\s*\]\s*\[\s*/', '', $this->content, 1, $found);
            if ($found === 0) {
                throw $this->createException('Wrong key/value syntax');
            }
            $key = $this->getString();
            $this->content = rtrim($this->content);
        }

        $this->content = preg_replace('/^\s*\]\s*=\s*/', '', $this->content, 1, $found);
        if ($found === 0) {
            throw $this->createException('Wrong key/value syntax');
        }
        $value = $this->getString();
        $this->content = rtrim($this->content);
        if (!isset($this->content[0]) || $this->content[0] !== ';') {
            throw $this->createException('Wrong key/value syntax, expected command end or eof');
        }
        $this->shortContentBy(1);

        if ($javaScriptLang) {
            $this->lang['js'][$key] = $value;
        } else {
            $this->lang[$key] = $value;
        }
        return LanguageFileParser::$MODE_PHP;
    }

    /**
     * Get a string from content (concatenated strings are joined), content is shortened
     *
     * @return string
     *
     * @throws LanguageParseException
     */
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

    /**
     * Get first string from content, content is shortened
     *
     * @return bool|string
     *
     * @throws LanguageParseException
     */
    public function getFirstString() {
        $stringDelimiter = $this->content[0];
        if (!in_array($stringDelimiter, array('\'', '"'))) {
            throw $this->createException("Content won't start with a string.");
        }
        $this->shortContentBy(1);

        $pos = null;
        $offset = 0;
        do {
            $pos = strpos($this->content, $stringDelimiter, $offset);
            if ($pos === false) {
                throw $this->createException('String has no ending delimiter.');
            }
            if ($pos === 0) {
                break;
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

    /**
     * Process content of single line comment: just skips the content
     *
     * @return string
     */
    public function processSingleLineComment() {
        $endOfLine = strpos($this->content, "\n");
        if ($endOfLine === false) {
            $this->content = '';
            return LanguageFileParser::$MODE_PHP;
        }
        $this->content = substr($this->content, $endOfLine+1);

        return LanguageFileParser::$MODE_PHP;
    }

    /**
     * Process content of multi line comment: filter authors and header text
     *
     * @return string
     *
     * @throws LanguageParseException
     */
    public function processMultiLineComment() {
        $end = strpos($this->content, '*/');
        if ($end === false) {
            throw $this->createException('multi line comment not closed');
        }
        $comment = substr($this->content, 0, $end);
        $commentLines = explode("\n", $comment);
        $lastLineWasEmpty = false;
        foreach($commentLines as $line) {
            $line = ltrim($line);
            if(empty($line)) {
                continue;
            }

            $line .= "\n";
            if(preg_match('/\* @author:?[ ]+<(.*?)>\n/i', $line, $matches)) {
                $this->author->add(new Author('', trim($matches[1])));
                continue;
            }elseif(preg_match('/\* @author:? (.+?)(?: <(.*?)>)?\n/i', $line, $matches)) {
                $name = trim($matches[1]);
                $email = '';
                if(isset($matches[2])) {
                    $email = trim($matches[2]);
                } else {
                    $nameParts = explode(" ", $name);
                    foreach(array_reverse($nameParts) as $namePart) {
                        $trimmed = trim($namePart);
                        $isEmail = filter_var($trimmed, FILTER_VALIDATE_EMAIL);
                        if($isEmail) {
                            $name = trim(str_replace($trimmed, '', $name));
                            $email = $trimmed;
                            break;
                        }
                    }
                }
                $this->author->add(new Author($name, $email));
                continue;
            }

                $multilineMarker = '*';
            if ($this->stringStartsWith($line, $multilineMarker)) {
                $line = substr($line, strlen($multilineMarker));
                $line = ltrim($line, ' ');
            }
            if($line == "\n") {
                //keep only one empty line
                if(!$lastLineWasEmpty) {
                    $this->header .= ' *' . $line;
                }
                $lastLineWasEmpty = true;
            } else {
                $this->header .= ' * ' . $line;
                $lastLineWasEmpty = false;
            }
        }

        $this->content = substr($this->content, $end + 2);
        return LanguageFileParser::$MODE_PHP;
    }

    /**
     * Determine next mode, content is shortened
     *
     * @return string one of the LanguageFileParser::$MODE_* modes
     */
    public function determineNextMode() {
        $this->content = ltrim($this->content);

        $modes = array(
            '/*' => LanguageFileParser::$MODE_COMMENT_MULTI_LINE,
            '//' => LanguageFileParser::$MODE_COMMENT_SINGLE_LINE,
            '#' => LanguageFileParser::$MODE_COMMENT_SINGLE_LINE,
            '$lang[' => LanguageFileParser::$MODE_LANG,
            '?>' => LanguageFileParser::$MODE_PHP_END
        );

        foreach ($modes as $startsWith => $result) {
            if ($this->contentStartsWith($startsWith)) {
                $this->shortContentBy(strlen($startsWith));
                return $result;
            }
        }

        return LanguageFileParser::$MODE_PHP_UNKNOWN;
    }

    /**
     * Jump to first php-start-tag in content, if existing
     *
     * @throws LanguageParseException
     */
    function goToStart() {
        $phpStart = strpos($this->content, '<?php');
        if ($phpStart === -1) {
            throw $this->createException('No PHP start found');
        }
        $this->content = substr($this->content, $phpStart + 5);
    }

    /**
     * Does content starts with $needle
     *
     * @param string $needle
     * @return bool
     */
    private function contentStartsWith($needle) {
        return $this->stringStartsWith($this->content, $needle);
    }

    /**
     * Does $haystack starts with the $needle
     *
     * @param string $haystack given string
     * @param string $needle search for this text in string
     * @return bool
     */
    private function stringStartsWith($haystack, $needle) {
        return !strncmp($haystack, $needle, strlen($needle));
    }

    /**
     * Shorten the content from the begin with the given length
     *
     * @param $length
     */
    private function shortContentBy($length) {
        $this->content = substr($this->content, $length);
    }

    /**
     * Escapes a string according to http://php.net/manual/en/language.types.string.php
     *
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

    /**
     * @param $string
     * @return mixed
     */
    private function escapeSingleQuoted($string) {
        $string = str_replace('\\\\', '\\', $string);
        $string = str_replace('\\\'', '\'', $string);
        return $string;
    }

    /**
     * @param $string
     * @return mixed
     */
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

    /**
     * @return mixed
     */
    public function getHeader() {
        return $this->header;
    }

    /**
     * @return AuthorList
     */
    public function getAuthor() {
        return $this->author;
    }

    /**
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @return mixed
     */
    public function getLang() {
        return $this->lang;
    }

    /**
     * Creates a LanguageParseException
     *
     * @param string $message
     * @return LanguageParseException
     */
    private function createException($message) {
        $remaining = $this->content . $this->trimmedEnding;
        $remaining = explode("\n", $remaining);
        $remainingLines = count($remaining) - 1;
        $line = $this->totalLineNumbers - $remainingLines +1;

        return new LanguageParseException($message, $line, $this->file);
    }
}
