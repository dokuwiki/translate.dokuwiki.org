<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LanguageStatsEntityRepository")
 * @ORM\Table(name="languageStats")
 *      indexes={@ORM\Index(name="langName_idx", columns={"language"})}
 * )
 */
class LanguageStatsEntity {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="LanguageNameEntity")
     * @ORM\JoinColumn(name="language", referencedColumnName="code")
     */
    private ?LanguageNameEntity $language = null;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $completionPercent = null;

    /**
     * @ORM\ManyToOne(targetEntity="RepositoryEntity" , inversedBy="translations")
     */
    private ?RepositoryEntity $repository = null;

    public function setCompletionPercent(int $completionPercent): void {
        $this->completionPercent = $completionPercent;
    }

    public function getCompletionPercent(): ?int {
        return $this->completionPercent;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function setLanguage(LanguageNameEntity $language): void {
        $this->language = $language;
    }

    public function getLanguage(): ?LanguageNameEntity
    {
        return $this->language;
    }

    public function setRepository(RepositoryEntity $repository): void {
        $this->repository = $repository;
    }

    public function getRepository(): ?RepositoryEntity
    {
        return $this->repository;
    }


}
