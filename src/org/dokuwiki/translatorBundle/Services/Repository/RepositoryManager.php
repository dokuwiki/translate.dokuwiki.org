<?php
namespace org\dokuwiki\translatorBundle\Services\Repository;

use Doctrine\ORM\EntityManager;

class RepositoryManager {

    /**
     * @var string Path to the data folder. configured in Resources/config/services.yml
     */
    private $dataFolder;

    /**
     * @var EntityManager The Symfony entity manager
     */
    private $entityManager;

    function __construct($dataFolder, EntityManager $entityManager) {
        $this->dataFolder = $dataFolder;
        $this->entityManager = $entityManager;
    }

    /**
     * @return CoreRepository The DokuWiki core repository.
     */
    public function getCoreRepository() {
        return new CoreRepository($this->dataFolder, $this->entityManager);
    }
}
