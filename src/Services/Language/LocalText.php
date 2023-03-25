<?php

namespace org\dokuwiki\translatorBundle\Services\Language;

/**
 * Object with content of a .php or a .txt language file
 */
class LocalText {

    public static $TYPE_ARRAY = 'array';
    public static $TYPE_MARKUP = 'markup';

    /**
     * @var string see {@see LocalText::$TYPE_ARRAY} and {@see LocalText::$TYPE_MARKUP}
     */
    private $type;

    /**
     * @var array|string
     */
    private $content;

    /**
     * @var AuthorList
     */
    private $authors;

    /**
     * @var string
     */
    private $header;

    /**
     * @param array|string $content translated text, on markup its string everything else array
     * @param string $type see {@see LocalText::$TYPE_ARRAY} and {@see LocalText::$TYPE_MARKUP}
     * @param AuthorList|null $authors List of authors. Key set are the author names, values may the email addresses.
     *                            Always empty on markup mode.
     * @param string $header the other lines than the list of authors
     */
    function __construct($content, $type, AuthorList $authors = null, $header = '') {
        $this->content = $content;
        $this->type = $type;
        if ($authors === null) $authors = new AuthorList();
        $this->authors = $authors;
        $this->header = $header;
    }

    /**
     * Returns content of language file
     *
     * @return array|string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * Returns type
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Returns AuthorList
     *
     * @return AuthorList
     */
    public function getAuthors() {
        return $this->authors;
    }

    /**
     * Returns header for .php file
     *
     * @return string
     */
    public function getHeader() {
        return $this->header;
    }

    /**
     * Returns the rendered content of a language file
     *
     * @return string
     *
     * @throws LanguageFileIsEmptyException
     */
    public function render() {
        if ($this->type === LocalText::$TYPE_MARKUP) {
            return $this->getContent();
        }

        $php = "<?php\n\n";
        $php .= $this->renderHeader();
        $php .= $this->renderArray($this->content);

        return $php;
    }

    /**
     * Returns rendered header for .php file
     *
     * @return string
     */
    private function renderHeader() {
        $php = "/**\n";
        $end = strpos($this->header, '@license');
        if ($end === false) {
            $php .= " * @license    GPL 2 (https://www.gnu.org/licenses/gpl.html)\n";
            $php .= " *\n";
        }
        if(!empty($this->header)) {
            $emptyLine = " *\n";
            if($this->startsWith($this->header, $emptyLine)) {
                $this->header = substr($this->header, strlen($emptyLine));
            }
            $php .= $this->header;
            if(!$this->endsWith($this->header, "*\n")) {
                $php .= " *\n";
            }
        }
        $php .= $this->renderAuthors();

        $php .= " */\n";
        return $php;
    }

    /**
     * Returns header lines for the authors of the file
     *
     * @return string
     */
    private function renderAuthors() {
        $php = '';

        $authors = $this->authors->getAll();

        foreach ($authors as $author) {
            if ($author->getName() === '' && $author->getEmail() === '') continue;
            $name = $author->getName();
            if($name === '') {
                $user = explode('@', $author->getEmail(), 2);
                $name = $user[0];
            }
            $authorName = $this->escapeComment($name);
            $php.= " * @author $authorName";
            if ($author->getEmail() !== '') {
                $email = $this->escapeComment($author->getEmail());
                $php.=" <$email>";
            }
            $php.="\n";
        }

        return $php;
    }

    /**
     * Escape comments in header
     *
     * @param string $str
     * @return string
     */
    private function escapeComment($str) {
        return str_replace('*/', '', $str);
    }

    /**
     * Returns rendered array of language strings
     *
     * @param array $array associative array with keys and localized strings
     * @param string $prefix string included just for the key
     * @param bool $elementsWritten by reference give back whether the inner loop has written elements
     * @return string rendered content
     *
     * @throws LanguageFileIsEmptyException
     */
    private function renderArray($array, $prefix = '', &$elementsWritten = false) {
        $php = '';

        foreach ($array as $key => $text) {
            $key = $this->escapeText($key);

            if (is_array($text)) {
                $php .= $this->renderArray($text, "{$prefix}['$key']", $elementsWritten);
                continue;
            }

            $text = $this->escapeText($text);
            if ($text === '') continue;
            $left = '$lang' . $prefix . "['$key']";
            $php .= sprintf('%-30s', $left). " = '$text';\n";
            $elementsWritten = true;
        }

        if ($prefix === '') { // outer loop
            if (!$elementsWritten) {
                throw new LanguageFileIsEmptyException();
            }
        }
        return $php;
    }

    /**
     * Returns escaped text
     *
     * @param string $text
     * @return string
     */
    private function escapeText($text) {
        return str_replace("'", '\\\'', $text);
    }


    /**
     * Check if string starts with the needle
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    private function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * Check if string ends with needle
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    private function endsWith($haystack, $needle) {
        $length = strlen($needle);

        return $length === 0 ||
        (substr($haystack, -$length) === $needle);
    }
}
