<?php
namespace org\dokuwiki\translatorBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use org\dokuwiki\translatorBundle\Services\DokuWikiRepositoryAPI\DokuWikiRepositoryAPI;

class PluginNameValidator extends ConstraintValidator {

    /**
     * @var DokuWikiRepositoryAPI
     */
    private $api;

    function setApi(DokuWikiRepositoryAPI $api) {
        $this->api = $api;
    }

    public function validate($value, Constraint $constraint) {

        if ($this->api->getPluginInfo($value) === false) {
            $this->context->addViolation($constraint->message, array('%string%' => $value));
        }
    }

}
