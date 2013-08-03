<?php

namespace org\dokuwiki\translatorBundle\Services\Language;

class AuthorList {

    private $authors = array();

    public function add(Author $author) {
        if (!$this->has($author)) {
            $this->authors[] = $author;
        }
    }

    public function has(Author $author) {
        foreach ($this->authors as $otherAuthor) {
            if ($author->equals($otherAuthor)) return true;
        }
        return false;
    }

    public function getAll() {
        return $this->authors;
    }


}