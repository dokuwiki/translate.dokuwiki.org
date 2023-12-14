<?php

namespace App\Services\Language;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserTranslationValidatorFactory {

    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator) {
        $this->validator = $validator;
    }

    public function getInstance(array $defaultTranslation, array $previousTranslation, array $userTranslation,
                                string $author, string $authorEmail): UserTranslationValidator
    {
        return new UserTranslationValidator($defaultTranslation, $previousTranslation, $userTranslation, $author,
            $authorEmail, $this->validator);
    }


}
