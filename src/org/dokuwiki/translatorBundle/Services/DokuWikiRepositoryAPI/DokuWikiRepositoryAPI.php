<?php

namespace org\dokuwiki\translatorBundle\Services\DokuWikiRepositoryAPI;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntityRepository;

class DokuWikiRepositoryAPI {

    private $cachePath;
    private $cache = null;
    private $entityManager;

    /**
     * @var RepositoryEntityRepository
     */
    private $repositoryRepository;

    function __construct($dataFolder, EntityManager $entityManager) {
        $this->cachePath = "$dataFolder/dokuwikiRepositoryAPI.ser";
        $this->entityManager = $entityManager;
        $this->repositoryRepository = $entityManager->getRepository('dokuwikiTranslatorBundle:RepositoryEntity');
    }

    public function updateCache() {
        $content = simplexml_load_file('https://www.dokuwiki.org/lib/plugins/pluginrepo/repository.php');
        if ($content === false) {
            return false;
        }

        $cache = array();
        foreach ($content->plugin as $plugin) {
            $repository = new RepositoryEntity();
            $repository->setName(strtolower(strval($plugin->id)));
            $repository->setAuthor(strval($plugin->author));
            $repository->setDescription(strval($plugin->description));
            $repository->setType(strval(RepositoryEntity::$TYPE_PLUGIN));
            $repository->setTags($this->mergePluginTags($plugin->tags));
            $repository->setDisplayName(strval($plugin->name));
            $repository->setPopularity(intval($plugin->popularity));
            $cache[$repository->getName()] = $repository;

            $this->updateRepositoryInformation($repository);
        }
        $this->entityManager->flush();
        file_put_contents($this->cachePath, serialize($cache));
        $this->cache = $cache;

        return true;
    }

    private function mergePluginTags(\SimpleXMLElement $tags) {
        $result = array();
        foreach ($tags->tag as $tag) {
            $result[] = strval($tag);
        }

        return implode(', ', $result);
    }

    private function updateRepositoryInformation(RepositoryEntity $repository) {
        try {
            $current = $this->repositoryRepository->getRepository(RepositoryEntity::$TYPE_PLUGIN, $repository->getName());
        } catch (NoResultException $ignored) {
            return;
        }

        $this->mergeRepository($current, $repository);
        $this->entityManager->merge($current);
    }

    /**
     * @param string $id
     * @return bool|RepositoryEntity
     */
    public function getPluginInfo($id) {
        $this->loadCache();
        $id = strtolower($id);
        if (!isset($this->cache[$id])) {
            return false;
        }

        return $this->cache[$id];
    }

    public function mergePluginInfo(RepositoryEntity &$entity) {
        $info = $this->getPluginInfo($entity->getName());
        $this->mergeRepository($entity, $info);
    }

    private function mergeRepository(RepositoryEntity &$left, RepositoryEntity &$other) {
        $left->setAuthor($other->getAuthor());
        $left->setDescription($other->getDescription());
        $left->setType($other->getType());
        $left->setTags($other->getTags());
        $left->setDisplayName($other->getDisplayName());
        $left->setPopularity($other->getPopularity());
    }

    private function loadCache() {
        if ($this->cache !== null) {
            return true;
        }

        $fileContent = file_get_contents($this->cachePath);
        if ($fileContent === false) {
            $this->cache = array();
            return false;
        }
        $this->cache = unserialize($fileContent);
        return true;
    }
}
