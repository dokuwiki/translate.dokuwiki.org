<?php

namespace App\Services\Repository;

class PluginRepository extends Repository {

    protected function getLanguageFolder() {
        return array(
            'lang/'
        );
    }
}
