<?php

namespace App\Services\Repository;

class CoreRepository extends Repository
{

    /**
     * @return string[] Relative path to the language folder. i.e. lang/ for plugins
     */
    protected function getLanguageFolder(): array
    {
        return [
            'inc/lang',
            'lib/plugins/acl/lang',
            'lib/plugins/authad/lang',
            'lib/plugins/authldap/lang',
            'lib/plugins/authpdo/lang',
            'lib/plugins/authplain/lang',
            'lib/plugins/config/lang',
            'lib/plugins/extension/lang',
            'lib/plugins/logviewer/lang',
            'lib/plugins/popularity/lang',
            'lib/plugins/revert/lang',
            'lib/plugins/styling/lang',
            'lib/plugins/usermanager/lang',
            'lib/tpl/dokuwiki/lang'
        ];
    }
}
