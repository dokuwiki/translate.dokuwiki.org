<?php
namespace App\Validator;

use Doctrine\Persistence\ManagerRegistry;
use App\Entity\RepositoryEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\Validator\Constraint;

class CustomUniqueEntityValidator extends UniqueEntityValidator {

    /**
     * Type-hinted for auto wiring of service
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry);
    }

    /**
     * @param RepositoryEntity $entity
     * @param Constraint       $constraint
     */
    public function validate($entity, Constraint $constraint) {
        $constraint->message = strtr(
            $constraint->message,
            ['{{ type }}' => $entity->getType(), '{{ name }}' => $entity->getName()]
        );

        parent::validate($entity, $constraint);
    }

}
