<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RepositoryEntityRepository")
 * @ORM\Table(name="repository",
 *      indexes={@ORM\Index(name="name_idx", columns={"name"})}
 * )
 */
class RepositoryEntity {

    public const STATE_WAITING_FOR_APPROVAL = 'waiting';
    public const STATE_INITIALIZING = 'initialProcessing';
    public const STATE_ACTIVE = 'active';
    public const STATE_ERROR = 'error';

    public const TYPE_CORE   = 'core';
    public const TYPE_PLUGIN = 'plugin';
    public const TYPE_TEMPLATE = 'template';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(type="string", length=300)
     */
    protected ?string $url = null;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected ?string $branch = null;

    /**
     * @ORM\Column(type="integer")
     */
    protected ?int $lastUpdate = null;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected ?string $name = null;

    /**
     * @ORM\Column(type="integer")
     */
    protected ?int $popularity = null;

    /**
     * @ORM\Column(type="string", length=200)
     */
    protected ?string $displayName = null;

    /**
     * @ORM\Column(type="string", length=355)
     */
    protected ?string $email = null;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected ?string $author = null;

    /**
     * @ORM\Column(type="string", length=500)
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(type="string", length=200)
     */
    protected ?string $tags = null;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected ?string $type = null;

    /**
     * @ORM\Column(type="string", length=100)
     * @var string|null
     */
    protected ?string $state = null;

    /**
     * @ORM\Column(type="text")
     * @var string|null
     */
    protected ?string $errorMsg = '';

    /**
     * @ORM\Column(type="integer")
     * @var int|null
     */
    protected ?int $errorCount = 0;

    /**
     * @ORM\Column(type="string", length=100)
     * @var string|null
     */
    protected ?string $activationKey = '';

    /**
     * @ORM\OneToMany(targetEntity="LanguageStatsEntity", mappedBy="repository")
     */
    protected Collection $translations;

    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    protected bool $englishReadonly = false;

    public function __construct() {
        $this->translations = new ArrayCollection();
    }

    public function setTranslations(Collection $translations): void {
        $this->translations = $translations;
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function setErrorCount(int $errorCount): void {
        $this->errorCount = $errorCount;
    }

    public function getErrorCount(): ?int {
        return $this->errorCount;
    }

    public function setErrorMsg(string $errorMsg): void {
        $this->errorMsg = $errorMsg;
    }

    public function getErrorMsg(): ?string {
        return $this->errorMsg;
    }

    public function addErrorMsg(string $errorMsg): void {
        $this->errorMsg .= "\n" . $errorMsg;
    }

    public function setState(string $state): void {
        $this->state = $state;
    }

    public function getState(): ?string {
        return $this->state;
    }

    public function setDisplayName(string $displayName): void {
        $this->displayName = $displayName;
    }

    public function getDisplayName(): ?string {
        return $this->displayName;
    }

    public function setPopularity(int $popularity): void {
        $this->popularity = $popularity;
    }

    public function getPopularity(): ?int {
        return $this->popularity;
    }

     public function setEmail(string $email): void {
        $this->email = $email;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUrl(string $url): RepositoryEntity
    {
        $this->url = $url;
    
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setBranch(string $branch): RepositoryEntity
    {
        $this->branch = $branch;
    
        return $this;
    }

    public function getBranch(): ?string
    {
        return $this->branch;
    }

    public function setLastUpdate(int $lastUpdate): RepositoryEntity
    {
        $this->lastUpdate = $lastUpdate;
    
        return $this;
    }

    public function getLastUpdate(): ?int
    {
        return $this->lastUpdate;
    }

    public function setName(string $name): RepositoryEntity
    {
        $this->name = $name;
    
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setAuthor(string $author): RepositoryEntity
    {
        $this->author = $author;
    
        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setDescription(string $description): RepositoryEntity
    {
        $this->description = $description;
    
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setTags(string $tags): RepositoryEntity
    {
        $this->tags = $tags;
    
        return $this;
    }

    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function setType(string $type): RepositoryEntity
    {
        $this->type = $type;
    
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setActivationKey(string $activationKey): void {
        $this->activationKey = $activationKey;
    }

    public function getActivationKey(): ?string {
        return $this->activationKey;
    }

    public function setEnglishReadonly(bool $englishReadonly): void {
        $this->englishReadonly = $englishReadonly;
    }

    public function getEnglishReadonly(): bool {
        return $this->englishReadonly;
    }

}