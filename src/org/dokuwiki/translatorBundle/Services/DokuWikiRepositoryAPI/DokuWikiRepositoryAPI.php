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
        $content = simplexml_load_file('https://www.dokuwiki.org/lib/plugins/pluginrepo/repository.php?includetemplates=yes');
        if ($content === false) {
            return false;
        }

        $cache = array();
        foreach ($content->plugin as $extension) {
            $repository = new RepositoryEntity();
            if(substr($extension->id, 0, 9) == 'template:') {
                $type = RepositoryEntity::$TYPE_TEMPLATE;
                $name = substr($extension->id, 9);
            } else {
                $type = RepositoryEntity::$TYPE_PLUGIN;
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
        $this->entityManager->flush();
        file_put_contents($this->cachePath, serialize($cache));
        $this->cache = $cache;

        return true;
    }

    private function mergeExtensionTags(\SimpleXMLElement $tags) {
        $result = array();
        foreach ($tags->tag as $tag) {
            $result[] = strval($tag);
        }

        return implode(', ', $result);
    }

    private function updateRepositoryInformation(RepositoryEntity $repository) {
        try {
            $current = $this->repositoryRepository->getRepository($repository->getType(), $repository->getName());
        } catch (NoResultException $ignored) {
            return;
        }

        $this->mergeRepository($current, $repository);
        $this->entityManager->merge($current);
    }

    /**
     * @param string $type
     * @param string $name
     * @return bool|RepositoryEntity
     */
    public function getExtensionInfo($type, $name) {
        $this->loadCache();
        $name = strtolower($name);
        if (!isset($this->cache[$type . ':'. $name])) {
            return false;
        }

        return $this->cache[$type . ':'. $name];
    }

    public function mergeExtensionInfo(RepositoryEntity &$entity) {
        $info = $this->getExtensionInfo($entity->getType(), $entity->getName());
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
