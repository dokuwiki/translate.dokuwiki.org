<?php

namespace org\dokuwiki\translatorBundle\Services\Language;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserTranslationValidatorFactory {

    private $validator;

    public function __construct(ValidatorInterface $validator) {
        $this->validator = $validator;
    }

    public function getInstance($defaultTranslation, $previousTranslation, array $userTranslation, $author, $authorEmail) {
        return new UserTranslationValidator($defaultTranslation, $previousTranslation, $userTranslation, $author, $authorEmail, $this->validator);
    }


}
