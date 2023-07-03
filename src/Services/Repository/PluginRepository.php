<?php

namespace App\Services\Repository;

class PluginRepository extends Repository
{

    protected function getLanguageFolders(): array
    {
        return [
            'lang/'
        ];
    }
}
