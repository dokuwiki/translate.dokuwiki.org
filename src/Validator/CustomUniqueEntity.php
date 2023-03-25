<?php
namespace App\Validator;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @Annotation
 */
class CustomUniqueEntity extends UniqueEntity {

    public function validatedBy() {
        return \get_class($this).'Validator';
    }

}