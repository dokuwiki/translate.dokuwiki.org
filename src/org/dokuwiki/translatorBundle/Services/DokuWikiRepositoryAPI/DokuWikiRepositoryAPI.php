<?php

namespace org\dokuwiki\translatorBundle\Services\DokuWikiRepositoryAPI;

use org\dokuwiki\translatorBundle\Services\Repository\Repository;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;

class DokuWikiRepositoryAPI {

    private $cachePath;
    private $cache = null;

    function __construct($dataFolder) {
        $this->cachePath = "$dataFolder/dokuwikiRepositoryAPI.ser";
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
            $repository->setType(strval(Repository::$TYPE_PLUGIN));
            $repository->setTags($this->mergePluginTags($plugin->tags));
            $repository->setDisplayName(strval($plugin->name));
            $repository->setPopularity(intval($plugin->popularity));
            $cache[$repository->getName()] = $repository;
        }

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

    public function getPluginInfo($id) {
        $this->loadCache();
        $id = strtolower($id);
        if (!isset($this->cache[$id])) {
            return false;
        }

        return $this->cache[$id];
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
