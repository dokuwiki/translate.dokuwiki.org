<?php
namespace org\dokuwiki\translatorBundle\Services\Language;


class Author {

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $email;

    public function __construct($name, $email) {
        $this->email = $email;
        $this->name = $name;
    }

    public function equals(Author $author) {
        if (mb_strtolower($author->getName()) !== mb_strtolower($this->getName())
            && mb_strtolower($author->getEmail()) !== mb_strtolower($this->getEmail())) return false;
        return true;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getName() {
        return $this->name;
    }
}