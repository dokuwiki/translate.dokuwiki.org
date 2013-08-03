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
        if ($author->getName() !== $this->getName()) return false;
        if ($author->getEmail() !== $this->getEmail()) return false;
        return true;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getName() {
        return $this->name;
    }
}