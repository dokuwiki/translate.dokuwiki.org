<?php

namespace App\Services\Language;

class AuthorList {

    /** @var Author[] */
    private array $authors = [];

    /**
     * Adds an author, keeps first addition if equal
     *
     * @param Author $author
     */
    public function add(Author $author): void {
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
    public function has(Author $author): bool {
        foreach ($this->authors as $otherAuthor) {
            if ($author->equals($otherAuthor)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns all author objects
     *
     * @return Author[]
     */
    public function getAll(): array {
        return $this->authors;
    }


}