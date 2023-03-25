<?php

namespace org\dokuwiki\translatorBundle\Services\Repository;

class TemplateRepository extends Repository {

    protected function getLanguageFolder() {
        return array(
            'lang/'
        );
    }
}
