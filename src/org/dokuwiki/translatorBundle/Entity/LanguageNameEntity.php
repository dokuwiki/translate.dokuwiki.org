<?php

namespace org\dokuwiki\translatorBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="languageName")
 */
class LanguageNameEntity {

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=50)
     * @var string
     */
    protected $code;

    /**
     * @ORM\Column(type="string", length=150)
     * @var string
     */
    protected $name;

    public function setCode($code) {
        $this->code = $code;
    }

    public function getCode() {
        return $this->code;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }


}
