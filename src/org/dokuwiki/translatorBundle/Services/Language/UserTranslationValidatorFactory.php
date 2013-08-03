<?php

namespace org\dokuwiki\translatorBundle\Services\Language;

use Symfony\Component\Validator\Validator;

class UserTranslationValidatorFactory {

    private $validator;

    public function __construct(Validator $validator) {
        $this->validator = $validator;
    }

    public function getInstance($defaultTranslation, $previousTranslation, array $userTranslation, $author, $authorEmail) {
        return new UserTranslationValidator($defaultTranslation, $previousTranslation, $userTranslation, $author, $authorEmail, $this->validator);
    }


}