<?php

namespace org\dokuwiki\translatorBundle\Services\Repository;

class PluginRepository extends Repository {

    protected function getLanguageFolder() {
        return array(
            'lang/'
        );
    }
}
