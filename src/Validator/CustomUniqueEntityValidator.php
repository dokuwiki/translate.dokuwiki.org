<?php
namespace App\Validator;

use Doctrine\Bundle\DoctrineBundle\Registry;
use App\Entity\RepositoryEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\Validator\Constraint;

class CustomUniqueEntityValidator extends UniqueEntityValidator {

    /**
     * Type-hinted for auto wiring of service
     *
     * @param Registry $registry
     */
    public function __construct(Registry $registry) {
        parent::__construct($registry);
    }

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
