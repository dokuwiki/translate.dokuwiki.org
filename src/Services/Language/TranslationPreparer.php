<?php
namespace App\Services\Language;

class TranslationPreparer {

    /**
     * @var LocalText[] languages files for default language (=en)
     */
    private $defaultTranslation;

    /**
     * @var array|LocalText[] languages files just provided by a translator or last version of language files
     */
    private $targetTranslation;

    /**
     * @var array[] array with entries for each language string, which is not yet translated
     */
    private $missingTranslations;

    /**
     * @var array[] array with entries for each language string, which is already translated
     */
    private $availableTranslations;

    /**
     * Returns for all strings in the default translation, data entries for the translation form
     * Sorted in translatable and, next, already translated strings
     *
     * @param array $defaultTranslation
     * @param array $targetTranslation
     * @return array
     */
    public function prepare(array $defaultTranslation, array $targetTranslation) {
        $this->defaultTranslation = $defaultTranslation;
        $this->targetTranslation = $targetTranslation;
        $this->missingTranslations = array();
        $this->availableTranslations = array();

        /** @var LocalText $translation */
        foreach ($this->defaultTranslation as $path => $translation) {

            if ($translation instanceof LocalText) { //TODO defaultTranslation could be only LocalText[], only user translation should provide raw arrays??
                $type = $translation->getType();
                $arrayMode = false;
            } else {
                $type = is_array($translation) ? LocalText::$TYPE_ARRAY : LocalText::$TYPE_MARKUP;
                $arrayMode = true;
            }

            if ($type !== LocalText::$TYPE_ARRAY) {
                $this->createEntry($path);
                continue;
            }

            if (!$arrayMode) {
                $translation = $translation->getContent();
            }
            foreach ($translation as $key => $text) {
                if ($key !== 'js') {
                    $this->createEntry($path, $key);
                    continue;
                }
                foreach ($text as $jsKey => $jsVal) {
                    $this->createEntry($path, $key, $jsKey);
                }
            }
        }

        return array_merge($this->missingTranslations, $this->availableTranslations);
    }


    /**
     * Creates an entry for a language string, and save it sorted in two groups: missing or already translated
     *
     * @param string $path path to its language file (without language code)
     * @param string|null $key key of the language string
     * @param string|null $jsKey js-key
     */
    private function createEntry($path, $key = null, $jsKey = null) {
        $entry = array();
        $entry['key'] = $this->createEntryKey($path, $key, $jsKey);
        $entry['default'] = $this->createEntryGetTranslation($this->defaultTranslation, $path, $key, $jsKey);
        $entry['target'] = $this->createEntryGetTranslation($this->targetTranslation, $path, $key, $jsKey);
        $entry['type'] = ($key === null) ? LocalText::$TYPE_MARKUP : LocalText::$TYPE_ARRAY;

        if ($entry['target'] === '') {
            $this->missingTranslations[] = $entry;
        } else {
            $this->availableTranslations[] = $entry;
        }
    }

    /**
     * Composes the key of the language string
     *
     * @param string $path path to its language file (without language code)
     * @param string|null $key for LocalText::$TYPE_ARRAY, the key of the language string
     * @param string|null $jsKey js key of language string
     * @return string
     */
    function createEntryKey($path, $key = null, $jsKey = null) {
        $entryKey = sprintf('translation[%s]', $path);
        if ($key === null) return $entryKey;

        $entryKey .= sprintf('[%s]', $key);
        if ($jsKey === null) return $entryKey;

        $entryKey .= sprintf('[%s]', $jsKey);
        return $entryKey;
    }

    /**
     * Return the requested translation string, otherwise an empty string
     *
     * @param array|LocalText[] $translation user translation or default translation
     * @param string $path path of language file
     * @param string|null $key for LocalText::$TYPE_ARRAY, the key of the language string
     * @param string|null $jsKey js key of language string
     * @return string
     */
    function createEntryGetTranslation($translation, $path, $key = null, $jsKey = null) {
        if (!isset($translation[$path])) {
            return '';
        }

        $translation = $translation[$path];
        if ($translation instanceof LocalText) {
            $translation = $translation->getContent();
        }

        if ($key === null) {
            return $translation;
        }

        if (!isset($translation[$key])) return '';
        if ($jsKey === null) return $translation[$key];

        if (!isset($translation[$key][$jsKey])) return '';
        return $translation[$key][$jsKey];
    }



}