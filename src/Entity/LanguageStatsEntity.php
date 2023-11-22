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
     * @var int|null
     */
    protected ?int $id;

    /**
     * @ORM\ManyToOne(targetEntity="LanguageNameEntity")
     * @ORM\JoinColumn(name="language", referencedColumnName="code")
     * @var LanguageNameEntity
     */
    private LanguageNameEntity $language;

    /**
     * @ORM\Column(type="integer")
     * @var int|null
     */
    private ?int $completionPercent;

    /**
     * @ORM\ManyToOne(targetEntity="RepositoryEntity" , inversedBy="translations")
     * @var RepositoryEntity
     */
    private RepositoryEntity $repository;

    /**
     * @param int $completionPercent
     */
    public function setCompletionPercent($completionPercent) {
        $this->completionPercent = $completionPercent;
    }

    /**
     * @return int
     */
    public function getCompletionPercent() {
        return $this->completionPercent;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param LanguageNameEntity $language
     */
    public function setLanguage($language) {
        $this->language = $language;
    }

    /**
     * @return LanguageNameEntity
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @param RepositoryEntity $repository
     */
    public function setRepository($repository) {
        $this->repository = $repository;
    }

    /**
     * @return RepositoryEntity
     */
    public function getRepository() {
        return $this->repository;
    }


}
