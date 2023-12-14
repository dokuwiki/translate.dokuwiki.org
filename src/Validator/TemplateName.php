<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class TemplateName extends Constraint
{

    public $message = 'No template with name "%string%" found on dokuwiki.org template list.';

    public function validatedBy(): string
    {
        return get_class($this) . 'Validator';
    }

}
