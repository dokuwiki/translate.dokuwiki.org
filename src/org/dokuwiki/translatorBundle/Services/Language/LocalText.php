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
}
