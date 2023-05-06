<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
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
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=300)
     * @var string
     */
    protected $url;

    /**
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    protected $branch;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $lastUpdate;

    /**
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $popularity;

    /**
     * @ORM\Column(type="string", length=200)
     * @var string
     */
    protected $displayName;

    /**
     * @ORM\Column(type="string", length=355)
     * @var string
     */
    protected $email;

    /**
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    protected $author;

    /**
     * @ORM\Column(type="string", length=500)
     * @var string
     */
    protected $description;

    /**
     * @ORM\Column(type="string", length=200)
     * @var string
     */
    protected $tags;

    /**
     * @ORM\Column(type="string", length=50)
     * @var string
     */
    protected $type;

    /**
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    protected $state;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    protected $errorMsg = '';

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $errorCount = 0;

    /**
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    protected $activationKey = '';

    /**
     * @ORM\OneToMany(targetEntity="LanguageStatsEntity", mappedBy="repository")
     */
    protected $translations;

    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    protected $englishReadonly = false;

    function __construct() {
        $this->translations = new ArrayCollection();
    }

    public function setTranslations($translations) {
        $this->translations = $translations;
    }

    public function getTranslations() {
        return $this->translations;
    }

    /**
     * @param int $errorCount
     */
    public function setErrorCount($errorCount) {
        $this->errorCount = $errorCount;
    }

    /**
     * @return int
     */
    public function getErrorCount() {
        return $this->errorCount;
    }

    /**
     * @param string $errorMsg
     */
    public function setErrorMsg($errorMsg) {
        $this->errorMsg = $errorMsg;
    }

    /**
     * @return string
     */
    public function getErrorMsg() {
        return $this->errorMsg;
    }

    /**
     * @param string $state
     */
    public function setState($state) {
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function getState() {
        return $this->state;
    }
    /**
     * @param string $displayName
     */
    public function setDisplayName($displayName) {
        $this->displayName = $displayName;
    }

    /**
     * @return string
     */
    public function getDisplayName() {
        return $this->displayName;
    }

    /**
     * @param int $popularity
     */
    public function setPopularity($popularity) {
        $this->popularity = $popularity;
    }

    /**
     * @return int
     */
    public function getPopularity() {
        return $this->popularity;
    }

    /**
     * @param string $email
     */
    public function setEmail($email) {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return RepositoryEntity
     */
    public function setUrl($url)
    {
        $this->url = $url;
    
        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set branch
     *
     * @param string $branch
     * @return RepositoryEntity
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;
    
        return $this;
    }

    /**
     * Get branch
     *
     * @return string 
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * Set lastUpdate
     *
     * @param integer $lastUpdate
     * @return RepositoryEntity
     */
    public function setLastUpdate($lastUpdate)
    {
        $this->lastUpdate = $lastUpdate;
    
        return $this;
    }

    /**
     * Get lastUpdate
     *
     * @return integer 
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return RepositoryEntity
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set author
     *
     * @param string $author
     * @return RepositoryEntity
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    
        return $this;
    }

    /**
     * Get author
     *
     * @return string 
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return RepositoryEntity
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set tags
     *
     * @param string $tags
     * @return RepositoryEntity
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    
        return $this;
    }

    /**
     * Get tags
     *
     * @return string 
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return RepositoryEntity
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $activationKey
     */
    public function setActivationKey($activationKey) {
        $this->activationKey = $activationKey;
    }

    /**
     * @return string
     */
    public function getActivationKey() {
        return $this->activationKey;
    }

    /**
     * @param bool $englishReadonly
     */
    public function setEnglishReadonly($englishReadonly) {
        $this->englishReadonly = $englishReadonly;
    }

    /**
     * @return bool
     */
    public function getEnglishReadonly() {
        return $this->englishReadonly;
    }

}