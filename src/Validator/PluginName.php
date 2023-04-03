<?php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PluginName extends Constraint {

    public $message = 'No plugin with name "%string%" found on dokuwiki.org plugin list.';

    public function validatedBy() {
        return get_class($this).'Validator';
    }

}
