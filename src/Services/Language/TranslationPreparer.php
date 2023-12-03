<?php
namespace App\Services\Language;

class TranslationPreparer {

    /**
     * @var LocalText[] languages files for default language (=en)
     */
    private array $defaultTranslation;

    /**
     * @var array|LocalText[] languages files just provided by a translator or last version of language files
     */
    private array $targetTranslation;

    /**
     * @var array[] array with entries for each language string, which is not yet translated
     */
    private array $missingTranslations;

    /**
     * @var array[] array with entries for each language string, which is already translated
     */
    private array $availableTranslations;

    /**
     * Returns for all strings in the default translation, data entries for the translation form
     * Sorted in translatable and, next, already translated strings
     *
     * @param LocalText[] $defaultTranslation
     * @param array|LocalText[] $targetTranslation if user submitted as array, otherwise LocalText from disk
     * @return array
     */
    public function prepare(array $defaultTranslation, array $targetTranslation) {
        $this->defaultTranslation = $defaultTranslation;
        $this->targetTranslation = $targetTranslation;
        $this->missingTranslations = [];
        $this->availableTranslations = [];

        foreach ($this->defaultTranslation as $path => $translation) {
            $type = $translation->getType();

            if ($type === LocalText::TYPE_MARKUP) {
                $this->createEntry($path);
                continue;
            }

            $translation = $translation->getContent();

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
        $entry = [];
        $entry['key'] = $this->createEntryKey($path, $key, $jsKey);
        $entry['searchkey'] = $this->createSearchKey($path, $key, $jsKey);
        $entry['default'] = $this->createEntryGetTranslation($this->defaultTranslation, $path, $key, $jsKey);
        $entry['target'] = $this->createEntryGetTranslation($this->targetTranslation, $path, $key, $jsKey);
        $entry['type'] = ($key === null) ? LocalText::TYPE_MARKUP : LocalText::TYPE_ARRAY;

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
     * @param string|null $key for LocalText::TYPE_ARRAY, the key of the language string
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
     * Composes the smallest unique key of the localized string, that can be used for a search at codesearch.dokuwiki.org
     *
     * @param string $path path to its language file (without language code)
     * @param string|null $key for LocalText::TYPE_ARRAY, the key of the language string
     * @param string|null $jsKey js key of language string
     * @return string
     */
    function createSearchKey($path, $key = null, $jsKey = null) {
        if ($key === null) {
            $path = explode('/', $path); // e.g. lang/<filename>.txt or inc/lang/<filename>.txt, etc
            $filename = end($path);
            return substr($filename, 0, -4); //remove .txt
        }
        if ($jsKey === null) return $key;
        return $jsKey;
    }

    /**
     * Return the requested translation string, otherwise an empty string
     *
     * @param array|LocalText[] $translation user translation or default translation
     * @param string $path path of language file
     * @param string|null $key for LocalText::TYPE_ARRAY, the key of the language string
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