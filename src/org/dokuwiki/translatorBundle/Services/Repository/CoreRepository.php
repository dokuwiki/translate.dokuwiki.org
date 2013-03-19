<?php
namespace org\dokuwiki\translatorBundle\Services\Repository;

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

    /**
     * @return string Relative path to the language folder. i.e. lang/ for plugins
     */
    protected function getLanguageFolder() {
        return array(
            'inc/lang',
            'lib/plugins/acl/lang',
            'lib/plugins/authad/lang',
            'lib/plugins/authldap/lang',
            'lib/plugins/authmysql/lang',
            'lib/plugins/authpgsql/lang',
            'lib/plugins/plugin/lang',
            'lib/plugins/popularity/lang',
            'lib/plugins/revert/lang',
            'lib/plugins/popularity/lang',
            'lib/plugins/usermanager/lang',
            'lib/plugins/popularity/lang'
        );
    }
}
