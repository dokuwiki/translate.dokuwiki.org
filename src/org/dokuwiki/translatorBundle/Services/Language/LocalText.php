<?php

namespace org\dokuwiki\translatorBundle\Services\Language;

class LocalText {

    public static $TYPE_ARRAY = 'array';
    public static $TYPE_MARKUP = 'markup';

    private $type;
    private $content;
    private $authors;

    /**
     * @param array|string $content translated text, on markup its string everything else array
     * @param string $type see {@see LocalText::TYPE_ARRAY} and {@see LocalText::$TYPE_MARKUP}
     * @param array $authors List of authors. Keyset are the author names, values may the email addresses.
     *                       Always empty on markup mode.
     */
    function __construct($content, $type, $authors = array()) {
        $this->content = $content;
        $this->type = $type;
        $this->authors = $authors;
    }

    public function getContent() {
        return $this->content;
    }

    public function getType() {
        return $this->type;
    }

    public function getAuthors() {
        return $this->authors;
    }

    public function render() {
        if ($this->type === LocalText::$TYPE_MARKUP) {
            return $this->getContent();
        }

        $php = "<?php\n\n";
        $php .= $this->renderAuthors();
        $php .= $this->renderArray($this->content);

        return $php;
    }

    private function renderAuthors() {
        $php = "/**\n";
        $php.= " * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)\n";
        $php.= " * \n";

        foreach ($this->authors as $author => $email) {
            if (empty($author)) continue;
            $author = $this->escapeComment($author);
            $php.= " * @author $author";
            if (!empty($email)) {
                $email = $this->escapeComment($email);
                $php.=" <$email>";
            }
            $php.="\n";
        }
        $php.= " */\n";

        return $php;
    }

    private function escapeComment($str) {
        $str = str_replace('*/', '', $str);
        return $str;
    }

    private function renderArray($array, $prefix = '') {
        $php = '';

        foreach ($array as $key => $text) {
            $key = $this->escapeText($key);

            if (is_array($text)) {
                $php .= $this->renderArray($text, "{$prefix}['$key']");
                continue;
            }

            $text = $this->escapeText($text);
            if ($text === '') continue;
            $left = '$lang' . $prefix . "['$key']";
            $php .= sprintf('%-30s', $left). " = '$text';\n";
        }

        return $php;
    }

    private function escapeText($text) {
        return str_replace("'", '\\\'', $text);
    }
}
