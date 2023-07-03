<?php

namespace App\Services\Repository;

class TemplateRepository extends Repository
{

    protected function getLanguageFolders(): array
    {
        return [
            'lang/'
        ];
    }
}
