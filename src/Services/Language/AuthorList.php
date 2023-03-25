<?php

namespace org\dokuwiki\translatorBundle\Services\Language;

class AuthorList {

    /** @var Author[] */
    private $authors = array();

    /**
     * Adds an author, keeps first addition if equal
     *
     * @param Author $author
     */
    public function add(Author $author) {
        if (!$this->has($author)) {
            $this->authors[] = $author;
        }
    }

    /**
     * Checks if list has the author
     *
     * @param Author $author
     * @return bool true if Author already exists
     */
    public function has(Author $author) {
        foreach ($this->authors as $otherAuthor) {
            if ($author->equals($otherAuthor)) return true;
        }
        return false;
    }

    /**
     * Returns all author objects
     *
     * @return Author[]
     */
    public function getAll() {
        return $this->authors;
    }


}