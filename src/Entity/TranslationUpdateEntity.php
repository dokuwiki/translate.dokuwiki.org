<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TranslationUpdateEntityRepository")
 * @ORM\Table(name="translationUpdate")
 */
class TranslationUpdateEntity {

    public const STATE_UNDONE = 'undone';
    public const STATE_SENT = 'send';
    public const STATE_FAILED = 'failed';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="RepositoryEntity")
     */
    protected ?RepositoryEntity $repository = null;

    /**
     * @ORM\Column(type="string", length=300)
     */
    protected ?string $author = null;

    /**
     * @ORM\Column(type="string", length=300)
     */
    protected ?string $email = null;

    /**
     * @ORM\Column(type="integer")
     */
    protected ?int $updated = null;

    /**
     * @ORM\Column(type="string", length=300)
     */
    protected ?string $state = null;

    /**
     * @ORM\Column(type="text")
     */
    protected ?string $errorMsg = '';

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected ?string $language = null;

    /**
     * Subject message.
     * @see getSubject()
     */
    private string $subject = 'Translation update';


    public function setAuthor(string $author): void {
        $this->author = $author;
    }

    public function getAuthor(): ?string {
        return $this->author;
    }

    public function setEmail(string $email): void {
        $this->email = $email;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function setRepository(RepositoryEntity $repository): void {
        $this->repository = $repository;
    }

    public function getRepository(): ?RepositoryEntity
    {
        return $this->repository;
    }

    public function setState(string $state): void {
        $this->state = $state;
    }

    public function getState(): ?string {
        return $this->state;
    }

    public function setUpdated(int $updated): void {
        $this->updated = $updated;
    }

    public function getUpdated(): ?int {
        return $this->updated;
    }

    public function setLanguage(string $language): void {
        $this->language = $language;
    }

    public function getLanguage(): ?string {
        return $this->language;
    }

    /**
     * Returns a standard subject for translation updates.
     *
     * This can be used e.g. as commit message, pull request title or email subject.
     * If defined, the language code is added as a suffix.
     *
     * @return string
     */
    public function getSubject(): string {
        $subject = $this->subject;
        if ($this->language) {
            $subject .= ' (' . $this->language . ')';
        }
        return $subject;
    }

    public function setErrorMsg(string $errorMsg): void
    {
        $this->errorMsg = $errorMsg;
    }

    public function getErrorMsg(): string
    {
        return $this->errorMsg;
    }

}
