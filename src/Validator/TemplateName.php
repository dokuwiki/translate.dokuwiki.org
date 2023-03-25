<?php
namespace org\dokuwiki\translatorBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class TemplateName extends Constraint {

    public $message = 'No template with name "%string%" found on dokuwiki.org template list.';

    public function validatedBy() {
        return \get_class($this).'Validator';
    }

}
