<?php
namespace org\dokuwiki\translatorBundle\Services;

/**
 * @author Dominik Eckelmann
 */
class CoreRepository extends Repository {

    private static $URL = 'git://github.com/splitbrain/dokuwiki.git';
    private static $BRANCH = 'master';

    protected function getRepositoryUrl() {
        return CoreRepository::$URL;
    }

    protected function getBranch() {
        return CoreRepository::$BRANCH;
    }

    protected function getName() {
        return 'dokuwiki';
    }

    protected function getType() {
        return Repository::$TYPE_CORE;
    }
}
