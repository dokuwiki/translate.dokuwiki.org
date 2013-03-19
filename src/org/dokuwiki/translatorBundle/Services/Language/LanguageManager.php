<?php
namespace org\dokuwiki\translatorBundle\Services\Language;

use Doctrine\Tests\ORM\Functional\Locking\LockTest;

class LanguageManager {

    /**
     * read languages from lang folder.
     *
     * @param string $langFolder Lang folder
     * @param string $prefix Prefix string for item keys in language array.
     * @throws NoLanguageFolderException
     * @throws NoDefaultLanguageException
     * @return array()
     */
    public function readLanguages($langFolder, $prefix = '') {
        if (!is_dir($langFolder)) {
            throw new NoLanguageFolderException();
        }

        if (!is_dir("$langFolder/en/")) {
            throw new NoDefaultLanguageException();
        }

        $folders = scandir($langFolder);
        $languages = array();
        foreach ($folders as $folder) {
            if ($folder === '.' || $folder === '..') {
                continue;
            }
            if (!is_dir("$langFolder/$folder")) {
                continue;
            }
            $languages[$folder] = $this->readLanguage("$langFolder/$folder", "$prefix$folder/");
        }
        return $languages;
    }

    private function readLanguage($languageFolder, $prefix) {
        $language = array();

        $folders = scandir($languageFolder);
        foreach ($folders as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (!is_file("$languageFolder/$file")) {
                continue;
            }

            $extension = substr($file, -4);

            if ($extension === '.php') {
                $translation = LanguageFileParser::parseLangPHP("$languageFolder/$file");
                $language[$prefix . $file] =
                        new LocalText($translation->getLang(), LocalText::$TYPE_ARRAY, $translation->getAuthor());
                continue;
            }

            if ($extension === '.txt') {
                $language[$prefix . $file] = new LocalText(file_get_contents("$languageFolder/$file"), LocalText::$TYPE_MARKUP);
            }

        }
        return $language;
    }
}
