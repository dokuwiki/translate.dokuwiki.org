<?php
namespace org\dokuwiki\translatorBundle\Services\Repository;

class RepositoryManager {

    private $dataFolder;

    function __construct($dataFolder) {
        $this->dataFolder = $dataFolder;
    }

    /**
     * @return CoreRepository The DokuWiki core repository.
     */
    public function getCoreRepository() {
        return new CoreRepository($this->dataFolder);
    }

}
