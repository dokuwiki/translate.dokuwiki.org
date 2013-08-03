<?php
namespace org\dokuwiki\translatorBundle\Services\Language;

class TranslationPreparer {

    private $defaultTranslation;
    private $targetTranslation;
    private $missingTranslations;
    private $availableTranslations;
    private $arrayMode;

    public function prepare(array $defaultTranslation, array $targetTranslation) {
        $this->defaultTranslation = $defaultTranslation;
        $this->targetTranslation = $targetTranslation;
        $this->missingTranslations = array();
        $this->availableTranslations = array();

        /** @var LocalText $translation */
        foreach ($this->defaultTranslation as $path => $translation) {

            if ($translation instanceof LocalText) {
                $type = $translation->getType();
                $this->arrayMode = false;
            } else {
                $type = is_array($translation) ? LocalText::$TYPE_ARRAY : LocalText::$TYPE_MARKUP;
                $this->arrayMode = true;
            }

            if ($type !== LocalText::$TYPE_ARRAY) {
                $this->createEntry($path);
                continue;
            }

            if (!$this->arrayMode) {
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

    function createEntryKey($path, $key = null, $jsKey = null) {
        $entryKey = sprintf('translation[%s]', $path);
        if ($key === null) return $entryKey;

        $entryKey .= sprintf('[%s]', $key);
        if ($jsKey === null) return $entryKey;

        $entryKey .= sprintf('[%s]', $jsKey);
        return $entryKey;
    }

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