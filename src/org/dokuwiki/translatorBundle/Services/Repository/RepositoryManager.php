<?php
namespace org\dokuwiki\translatorBundle\Services\Repository;

class RepositoryManager {

    private $dataFolder;

    function __construct($dataFolder) {
        $this->dataFolder = $dataFolder;
    }

    public function getCoreRepository() {
        return new CoreRepository($this->dataFolder);
    }

}
