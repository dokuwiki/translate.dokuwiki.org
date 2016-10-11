<?php
namespace org\dokuwiki\translatorBundle\Validator;

use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use org\dokuwiki\translatorBundle\Services\DokuWikiRepositoryAPI\DokuWikiRepositoryAPI;

class TemplateNameValidator extends ConstraintValidator {

    /**
     * @var DokuWikiRepositoryAPI
     */
    private $api;

    function setApi(DokuWikiRepositoryAPI $api) {
        $this->api = $api;
    }

    public function validate($value, Constraint $constraint) {

        if ($this->api->getExtensionInfo(RepositoryEntity::$TYPE_TEMPLATE, $value) === false) {
            $this->context->addViolation($constraint->message, array('%string%' => $value));
        }
    }

}
