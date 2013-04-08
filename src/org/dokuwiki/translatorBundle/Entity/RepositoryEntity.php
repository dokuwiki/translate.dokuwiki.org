<?php
namespace org\dokuwiki\translatorBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="repository")
 */
class RepositoryEntity {

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
     * @var string
     */
    protected $lastUpdate;

    /**
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    protected $name;

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
}