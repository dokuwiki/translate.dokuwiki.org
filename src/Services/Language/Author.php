<?php
namespace App\Services\Language;


class Author {


    private string $name;
    private string $email;

    public function __construct(string $name, string $email) {
        $this->email = $email;
        $this->name = $name;
    }

    public function equals(Author $author): bool {
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

    public function getEmail(): string {
        return $this->email;
    }

    public function getName(): string {
        return $this->name;
    }
}