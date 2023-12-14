<?php

namespace App\Services\DokuWikiRepositoryAPI;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Exception\ORMException;
use App\Entity\RepositoryEntity;
use App\Repository\RepositoryEntityRepository;
use SimpleXMLElement;

class DokuWikiRepositoryAPI {

    private string $cachePath;

    /** @var RepositoryEntity[]|null  */
    private ?array $cache = null;
    /**
     * @var EntityManager
     */
    private EntityManagerInterface $entityManager;
    private RepositoryEntityRepository $repositoryRepository;

    function __construct($dataFolder, EntityManagerInterface $entityManager) {
        $this->cachePath = "$dataFolder/dokuwikiRepositoryAPI.ser";
        $this->entityManager = $entityManager;
        $this->repositoryRepository = $entityManager->getRepository(RepositoryEntity::class);
    }

    /**
     * Update the cache with data from the API, also stored on disk for reuse
     *
     * @return bool
     * @throws ORMException
     */
    public function updateCache(): bool {
        $content = simplexml_load_file('https://www.dokuwiki.org/lib/plugins/pluginrepo/repository.php?includetemplates=yes');
        if ($content === false) {
            return false;
        }

        $cache = [];
        foreach ($content->plugin as $extension) {
            $repository = new RepositoryEntity();
            if(substr($extension->id, 0, 9) == 'template:') {
                $type = RepositoryEntity::TYPE_TEMPLATE;
                $name = substr($extension->id, 9);
            } else {
                $type = RepositoryEntity::TYPE_PLUGIN;
                $name = $extension->id;
            }
            $repository->setName(strtolower(strval($name)));
            $repository->setType($type);
            $repository->setAuthor(strval($extension->author));
            $repository->setDescription(strval($extension->description));
            $repository->setTags($this->mergeExtensionTags($extension->tags));
            $repository->setDisplayName(strval($extension->name));
            $repository->setPopularity(intval($extension->popularity));
            $cache[$repository->getType() . ':'. $repository->getName()] = $repository;

            $this->updateRepositoryInformation($repository);
        }
        try {
            $this->entityManager->flush();
        } catch (OptimisticLockException $e) {
            return false;
        }
        file_put_contents($this->cachePath, serialize($cache));
        $this->cache = $cache;

        return true;
    }

    /**
     * @param SimpleXMLElement $tags
     * @return string
     */
    private function mergeExtensionTags(SimpleXMLElement $tags): string {
        $result = [];
        foreach ($tags->tag as $tag) {
            $result[] = strval($tag);
        }

        return implode(', ', $result);
    }

    /**
     * Updates the $current repository entity with data from the API
     *
     * @param RepositoryEntity $repository entity with data set from the API
     * @return void
     */
    private function updateRepositoryInformation(RepositoryEntity $repository): void {
        try {
            $current = $this->repositoryRepository->getRepository($repository->getType(), $repository->getName());
        } catch (NoResultException $ignored) {
            return;
        }

        $this->mergeRepository($current, $repository);
    }

    /**
     * Get extension info from the cached API data
     *
     * @param string $type
     * @param string $name
     * @return false|RepositoryEntity
     */
    public function getExtensionInfo(string $type, string $name) {
        if(!$this->loadCache()) {
            return false;
        }

        $name = strtolower($name);
        if (!isset($this->cache[$type . ':'. $name])) {
            return false;
        }

        return $this->cache[$type . ':'. $name];
    }

    /**
     * Updates $entity with cached info from the API
     *
     * @param RepositoryEntity $entity
     * @return void
     */
    public function mergeExtensionInfo(RepositoryEntity $entity): void {
        $info = $this->getExtensionInfo($entity->getType(), $entity->getName());
        $this->mergeRepository($entity, $info);
    }

    /**
     * Merges the relevant info from the API into the local entity
     *
     * @param RepositoryEntity $left local entity
     * @param RepositoryEntity $apiInfo entity with data from API
     * @return void
     */
    private function mergeRepository(RepositoryEntity $left, RepositoryEntity $apiInfo): void {
        $left->setAuthor($apiInfo->getAuthor());
        $left->setDescription($apiInfo->getDescription());
        $left->setType($apiInfo->getType()); //TODO should not be touched from the api?
        $left->setTags($apiInfo->getTags());
        $left->setDisplayName($apiInfo->getDisplayName());
        $left->setPopularity($apiInfo->getPopularity());
    }

    /**
     * Loads the cache from file, and reports if it is available
     *
     * @return bool cache is loaded
     */
    private function loadCache(): bool {
        if ($this->cache !== null) {
            return true;
        }

        $fileContent = file_get_contents($this->cachePath);
        if ($fileContent === false) {
            $this->cache = [];
            return false;
        }
        $this->cache = unserialize($fileContent);
        return true;
    }
}
