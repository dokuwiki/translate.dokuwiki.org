<?php

namespace App\Services\Language;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserTranslationValidator {

    /** @var LocalText[]  */
    private array $defaultTranslation;
    /** @var LocalText[]  */
    private array $previousTranslation;
    /** @var array */
    private array $userTranslation;
    private string $author;
    private string $authorEmail;

    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $validator;
    /**
     * @var string[]
     */
    private array $errors = [];

    /**
     * UserTranslationValidator constructor.
     *
     * @param LocalText[] $defaultTranslation
     * @param LocalText[] $previousTranslation
     * @param array $userTranslation
     * @param string $author
     * @param string $authorEmail
     * @param ValidatorInterface $validator
     */
    function __construct(array $defaultTranslation, array $previousTranslation, array $userTranslation, $author, $authorEmail, ValidatorInterface $validator) {
        $this->defaultTranslation = $defaultTranslation;
        $this->userTranslation = $userTranslation;
        $this->previousTranslation = $previousTranslation;
        $this->author = trim($author);
        $this->authorEmail = trim($authorEmail);
        $this->validator = $validator;
        $this->validateAuthorEmail();
        $this->validateAuthorName();
    }

    /**
     * Validate the email of author of this submission
     */
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

    /**
     * Validate the name of author of this submission
     */
    private function validateAuthorName() {
        if ($this->author === '') {
            $this->errors['author'] = 'No author name given.';
        }
    }

    /**
     * Validates the user submitted strings
     *
     * @return LocalText[]
     */
    public function validate() {
        $newTranslation = array();

        foreach ($this->defaultTranslation as $path => $translation) {
            if (!isset($this->userTranslation[$path])) {
                continue;
            }

            if ($translation->getType() !== LocalText::TYPE_ARRAY) {
                $newTranslation[$path] = $this->validateMarkup($path);
                continue;
            }
            $newTranslation[$path] = $this->validateArray($path, $translation);
        }

        return $newTranslation;
    }

    /**
     * Validate strings with wiki syntax for txt-files
     *
     * @param $path
     * @return LocalText
     */
    private function validateMarkup($path) {
        $text = $this->fixLineEndings($this->userTranslation[$path]);
        return new LocalText($text, LocalText::TYPE_MARKUP);
    }

    /**
     * Validate the submitted language strings
     *
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
            }
        }

        if(!$translationChanged) {
            $this->errors['translation'] = 'No changes were made in the translation.';
        }

        $authors = new AuthorList();
        $header = '';
        if ($translationChanged && !empty($this->author)) {
            $authors->add(new Author($this->author, $this->authorEmail));
        }
        if (isset($this->previousTranslation[$path])) {
            $prevTranslation = $this->previousTranslation[$path];
            $prevAuthors = $prevTranslation->getAuthors()->getAll();
            foreach ($prevAuthors as $author) {
                $authors->add($author);
            }

            $header = $prevTranslation->getHeader();
        }

        return new LocalText($newContent, LocalText::TYPE_ARRAY, $authors, $header);
    }

    /**
     * Compare submitted translation to existing translation
     *
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

        $previous = $this->previousTranslation[$path];
        $previousText = $previous->getContent();

        if (!isset($previousText[$key])) {
            return $this->userTranslation[$path][$key] !== '';
        }
        return $this->userTranslation[$path][$key] !== $previousText[$key];
    }

    /**
     * Compare sub-arrays of submitted translation to existing translation
     *
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
     * Fixes line endings by replacing
     *
     * @param $string
     * @return string
     */
    private function fixLineEndings($string) {
        return str_replace("\r\n", "\n", $string);
    }

    /**
     * Returns the collected error messages
     *
     * @return string[] of error messages
     */
    public function getErrors() {
        return $this->errors;
    }

}
