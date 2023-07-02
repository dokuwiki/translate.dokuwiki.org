<?php

namespace App\Services\Repository;

class PluginRepository extends Repository
{

    protected function getLanguageFolder(): array
    {
        return [
            'lang/'
        ];
    }
}
