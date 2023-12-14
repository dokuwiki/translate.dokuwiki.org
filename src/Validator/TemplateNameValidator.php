<?php

namespace App\Validator;

use App\Entity\RepositoryEntity;
use App\Services\DokuWikiRepositoryAPI\DokuWikiRepositoryAPI;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TemplateNameValidator extends ConstraintValidator
{

    private DokuWikiRepositoryAPI $api;

    function setApi(DokuWikiRepositoryAPI $api): void
    {
        $this->api = $api;
    }

    /**
     * @param string $value template name
     * @param Constraint $constraint
     * @return void
     */
    public function validate($value, Constraint $constraint): void
    {

        if ($this->api->getExtensionInfo(RepositoryEntity::TYPE_TEMPLATE, $value) === false) {
            $this->context->addViolation($constraint->message, ['%string%' => $value]);
        }
    }

}
