<?php

namespace App\Services\Repository;

class TemplateRepository extends Repository {

    protected function getLanguageFolder() {
        return array(
            'lang/'
        );
    }
}
