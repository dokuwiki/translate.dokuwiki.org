<?php

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LanguageNameEntityRepository")
 * @ORM\Table(name="languageName")
 */
class LanguageNameEntity {

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=50)
     * @var string|null
     */
    protected ?string $code = null;

    /**
     * @ORM\Column(type="string", length=150)
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    protected bool $rtl = false;

    public function setCode(string $code): void {
        $this->code = $code;
    }

    public function getCode(): ?string {
        return $this->code;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setRtl(bool $rtl): void {
        $this->rtl = $rtl;
    }

    public function getRtl(): bool {
        return $this->rtl;
    }

}
