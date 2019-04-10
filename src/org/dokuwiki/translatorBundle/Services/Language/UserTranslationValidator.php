<?php

namespace org\dokuwiki\translatorBundle\Services\Language;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserTranslationValidator {

    /** @var LocalText[]  */
    private $defaultTranslation;
    /** @var LocalText[]  */
    private $previousTranslation;
    /** @var LocalText[]  */
    private $userTranslation;
    /** @var string  */
    private $author;
    /** @var string  */
    private $authorEmail;
    private $validator;
    private $errors = array();

    /**
     * UserTranslationValidator constructor.
     *
     * @param LocalText[] $defaultTranslation
     * @param LocalText[] $previousTranslation
     * @param LocalText[] $userTranslation
     * @param string $author
     * @param string $authorEmail
     * @param ValidatorInterface $validator
     */
    function __construct($defaultTranslation, $previousTranslation, array $userTranslation, $author, $authorEmail, ValidatorInterface $validator) {
        $this->defaultTranslation = $defaultTranslation;
        $this->userTranslation = $userTranslation;
        $this->previousTranslation = $previousTranslation;
        $this->author = trim($author);
        $this->authorEmail = trim($authorEmail);
        $this->validator = $validator;
        $this->validateAuthorEmail();
        $this->validateAuthorName();
    }

    private function validateAuthorEmail() {
        if ($this->authorEmail === '') {
            $this->errors['email'] = 'No email address given.';
            return;
        }
        $email = new Email();
        $errorList = $this->validator->validate($this->authorEmail, $email);
        if (count($errorList) !== 0) {
            $this->errors['email'] = 'No valid email address given.';
        }
    }

    private function validateAuthorName() {
        if ($this->author === '') {
            $this->errors['author'] = 'No author name given.';
        }
    }

    /**
     * @return LocalText[]
     */
    public function validate() {
        $newTranslation = array();

        /** @var LocalText $translation */
        foreach ($this->defaultTranslation as $path => $translation) {
            if (!isset($this->userTranslation[$path])) {
                continue;
            }

            if ($translation->getType() !== LocalText::$TYPE_ARRAY) {
                $newTranslation[$path] = $this->validateMarkup($path);
                continue;
            }
            $newTranslation[$path] = $this->validateArray($path, $translation);
        }

        return $newTranslation;
    }

    /**
     * @param $path
     * @return LocalText
     */
    private function validateMarkup($path) {
        $text = $this->fixLineEndings($this->userTranslation[$path]);
        return new LocalText($text, LocalText::$TYPE_MARKUP);
    }

    /**
     * @param string $path
     * @param LocalText $translation
     * @return LocalText
     */
    private function validateArray($path, LocalText $translation) {
        $newContent = array();
        $translationChanged = false;

        $translationArray = $translation->getContent();
        foreach ($translationArray as $key => $text) {
            if (!isset($this->userTranslation[$path][$key])) {
                continue;
            }

            if ($key !== 'js') {
                $newContent[$key] = $this->fixLineEndings($this->userTranslation[$path][$key]);
                if ($this->hasTranslationChanged($path, $key, $translationChanged)) {
                    $translationChanged = true;
                }
                continue;
            }

            $newContent[$key] = array();
            foreach ($text as $jsKey => $jsVal) {
                if (!isset($this->userTranslation[$path][$key][$jsKey])) {
                    continue;
                }
                $newContent[$key][$jsKey] = $this->fixLineEndings($this->userTranslation[$path][$key][$jsKey]);
                if ($this->hasJsTranslationChanged($path, $key, $jsKey, $translationChanged)) {
                    $translationChanged = true;
                }
                continue;
            }
        }

        $authors = new AuthorList();
        $header = '';
        if ($translationChanged && !empty($this->author)) {
            $authors->add(new Author($this->author, $this->authorEmail));
        }
        if (isset($this->previousTranslation[$path])) {
            /** @var LocalText $prevTranslation */
            $prevTranslation = $this->previousTranslation[$path];
            $prevAuthors = $prevTranslation->getAuthors()->getAll();
            foreach ($prevAuthors as $author) {
                $authors->add($author);
            }

            $header = $prevTranslation->getHeader();
        }

        return new LocalText($newContent, LocalText::$TYPE_ARRAY, $authors, $header);
    }

    /**
     * @param string $path
     * @param string $key
     * @param bool $alreadyChanged
     * @return bool
     */
    private function hasTranslationChanged($path, $key, $alreadyChanged) {
        if ($alreadyChanged) return false;

        if (!isset($this->previousTranslation[$path])) {
            return $this->userTranslation[$path][$key] !== '';
        }

        /** @var LocalText $previous */
        $previous = $this->previousTranslation[$path];
        $previousText = $previous->getContent();

        if (!isset($previousText[$key])) {
            return $this->userTranslation[$path][$key] !== '';
        }
        return $this->userTranslation[$path][$key] !== $previousText[$key];
    }

    /**
     * @param string $path
     * @param string $key
     * @param string $jsKey
     * @param bool $alreadyChanged
     * @return bool has changed
     */
    private function hasJsTranslationChanged($path, $key, $jsKey, $alreadyChanged) {
        if ($alreadyChanged) return false;

        if (!isset($this->previousTranslation[$path])) {
            return $this->userTranslation[$path][$key][$jsKey] !== '';
        }

        /** @var LocalText $previous */
        $previous = $this->previousTranslation[$path];
        $previousText = $previous->getContent();

        if (!isset($previousText[$key])) {
            return $this->userTranslation[$path][$key][$jsKey] !== '';
        }

        if (!isset($previousText[$key][$jsKey])) {
            return $this->userTranslation[$path][$key][$jsKey] !== '';
        }

        return $this->userTranslation[$path][$key][$jsKey] !== $previousText[$key][$jsKey];
    }

    /**
     * @param $string
     * @return string
     */
    private function fixLineEndings($string) {
        $string = str_replace("\r\n", "\n", $string);
        return $string;
    }

    /**
     * @return array of error messages
     */
    public function getErrors() {
        return $this->errors;
    }



}
