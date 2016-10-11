<?php
namespace org\dokuwiki\translatorBundle\Validator;

use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\Validator\Constraint;

class CustomUniqueEntityValidator extends UniqueEntityValidator {

    /**
     * @param RepositoryEntity $entity
     * @param Constraint       $constraint
     */
    public function validate($entity, Constraint $constraint) {
        $constraint->message = strtr(
            $constraint->message,
            array(
                '{{ type }}' => $entity->getType(),
                '{{ name }}' => $entity->getName()
            )
        );

        parent::validate($entity, $constraint);
    }

}
