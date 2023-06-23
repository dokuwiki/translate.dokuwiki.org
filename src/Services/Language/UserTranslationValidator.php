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
     * @var true
     */
    private bool $translationChanged;

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
     * - errors are stored
     * - line endings are fixed
     * - list with LocalText objects is returned
     *
     * @return LocalText[]
     */
    public function validate() {
        $this->translationChanged = false;
        $newTranslation = [];

        foreach ($this->defaultTranslation as $path => $translation) {
            //normally userTranslation contains all paths (and keys) from default
            if (!isset($this->userTranslation[$path])) {
                if(isset($this->previousTranslation[$path])) {
                    $this->translationChanged = true;
                }
                continue;
            }

            if ($translation->getType() === LocalText::TYPE_MARKUP) {
                $newTranslation[$path] = $this->validateMarkup($path);
                continue;
            }
            $newTranslation[$path] = $this->validateArray($path, $translation);
        }

        if(!$this->translationChanged) {
            $this->errors['translation'] = 'No changes were made in the translation.';
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

        $translationChanged = $this->hasMarkupTranslationChanged($path, $text);
        $this->storeTranslationChanged($translationChanged);

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
        $newContent = [];
        $translationChanged = false;

        $translationArray = $translation->getContent();
        foreach ($translationArray as $key => $text) {
            //normally userTranslation contains all existing paths and keys
            if (!isset($this->userTranslation[$path][$key])) {
                continue;
            }

            if ($key !== 'js') {
                $newContent[$key] = $this->fixLineEndings($this->userTranslation[$path][$key]);
                if ($this->hasTranslationChanged($path, $key, $translationChanged, $newContent[$key])) {
                    $translationChanged = true;
                }
                continue;
            }

            $newContent[$key] = [];
            foreach ($text as $jsKey => $jsVal) {
                //normally userTranslation contains all existing paths and keys
                if (!isset($this->userTranslation[$path][$key][$jsKey])) {
                    continue;
                }
                $newContent[$key][$jsKey] = $this->fixLineEndings($this->userTranslation[$path][$key][$jsKey]);
                if ($this->hasJsTranslationChanged($path, $key, $jsKey, $translationChanged, $newContent[$key][$jsKey])) {
                    $translationChanged = true;
                }
            }
        }

        $this->storeTranslationChanged($translationChanged);

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
     * @return bool has changed
     */
    private function hasTranslationChanged($path, $key, $alreadyChanged, $userTranslation) {
        if ($alreadyChanged) {
            return true;
        }

        if (!isset($this->previousTranslation[$path])) {
            return $userTranslation !== '';
        }

        $previous = $this->previousTranslation[$path];
        $previousText = $previous->getContent();

        if (!isset($previousText[$key])) {
            return $userTranslation !== '';
        }
        return $userTranslation !== $previousText[$key];
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
    private function hasJsTranslationChanged($path, $key, $jsKey, $alreadyChanged, $userTranslation) {
        if ($alreadyChanged) {
            return true;
        }

        if (!isset($this->previousTranslation[$path])) {
            return $userTranslation !== '';
        }

        $previous = $this->previousTranslation[$path];
        $previousText = $previous->getContent();

        if (!isset($previousText[$key])) {
            return $userTranslation !== '';
        }

        if (!isset($previousText[$key][$jsKey])) {
            return $userTranslation !== '';
        }
        return $userTranslation !== $previousText[$key][$jsKey];
    }

    private function hasMarkupTranslationChanged($path, $userTranslation) {
        if (!isset($this->previousTranslation[$path])) {
            return $userTranslation !== '';
        }

        $previous = $this->previousTranslation[$path];
        $previousText = $previous->getContent();
        //Previous markup translation should not be empty
        if ($previousText === '') {
            return $userTranslation !== '';
        }
        return $userTranslation !== $previousText;
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
     * Used for each file to store globally the translation change status
     *
     * @param bool $hasChanged
     */
    private function storeTranslationChanged(bool $hasChanged) {
        if($hasChanged) {
            $this->translationChanged = true;
        }
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