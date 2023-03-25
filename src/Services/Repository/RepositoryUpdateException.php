<?php

namespace App\Services\Repository;

use Exception;
use App\Entity\RepositoryEntity;

class RepositoryUpdateException extends Exception {

    private $repo;

    function __construct($message, RepositoryEntity $repo, Exception $previous) {
        $this->repo = $repo;
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return RepositoryEntity
     */
    public function getRepo() {
        return $this->repo;
    }
}