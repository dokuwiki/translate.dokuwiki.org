<?php

namespace org\dokuwiki\translatorBundle\Services\Language;

class LocalText {

    public static $TYPE_ARRAY = 'array';
    public static $TYPE_MARKUP = 'markup';

    private $type;
    private $content;
    private $authors;
    private $header;

    /**
     * @param array|string $content translated text, on markup its string everything else array
     * @param string $type see {@see LocalText::TYPE_ARRAY} and {@see LocalText::$TYPE_MARKUP}
     * @param AuthorList $authors List of authors. Keyset are the author names, values may the email addresses.
     *                       Always empty on markup mode.
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
     * @return array|string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return AuthorList
     */
    public function getAuthors() {
        return $this->authors;
    }

    /**
     * @return string
     */
    public function getHeader() {
        return $this->header;
    }

    /**
     * Returns the rendered content of a language file
     *
     * @return string
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

    private function renderHeader() {
        $php = "/**\n";
        $end = strpos($this->header, '@license');
        if ($end === false) {
            $php .= " * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)\n";
            $php .= " *\n";
        }
        if(!empty($this->header)) {
            $emptyline = " *\n";
            if($this->startsWith($this->header, $emptyline)) {
                $this->header = substr($this->header, strlen($emptyline));
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

    private function renderAuthors() {
        $php = '';

        $authors = $this->authors->getAll();

        /** @var Author $author */
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

    private function escapeComment($str) {
        $str = str_replace('*/', '', $str);
        return $str;
    }

    /**
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

    private function escapeText($text) {
        return str_replace("'", '\\\'', $text);
    }


    private function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    private function endsWith($haystack, $needle) {
        $length = strlen($needle);

        return $length === 0 ||
        (substr($haystack, -$length) === $needle);
    }
}
