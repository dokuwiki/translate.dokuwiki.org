<?php
namespace App\Services\Language;


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
        if($author->getName() === '' && $this->name === '' ) {
            return mb_strtolower($author->getEmail()) === mb_strtolower($this->email);
        }

        if($author->getEmail() === '' && $this->email === '' ) {
            return mb_strtolower($author->getName()) === mb_strtolower($this->name);
        }

        if (mb_strtolower($author->getName()) !== mb_strtolower($this->name)
            && mb_strtolower($author->getEmail()) !== mb_strtolower($this->email)) return false;
        return true;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getName() {
        return $this->name;
    }
}