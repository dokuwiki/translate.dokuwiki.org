<?php
namespace App\Validator;

use App\Entity\RepositoryEntity;
use App\Services\DokuWikiRepositoryAPI\DokuWikiRepositoryAPI;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PluginNameValidator extends ConstraintValidator {

    private DokuWikiRepositoryAPI $api;

    function setApi(DokuWikiRepositoryAPI $api): void {
        $this->api = $api;
    }

    /**
     * @param string $value plugin name
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void {

        if ($this->api->getExtensionInfo(RepositoryEntity::TYPE_PLUGIN, $value) === false) {
            $this->context->addViolation($constraint->message, ['%string%' => $value]);
        }
    }

}
