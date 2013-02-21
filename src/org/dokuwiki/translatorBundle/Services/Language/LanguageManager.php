<?php
namespace org\dokuwiki\translatorBundle\Services\Language;

class LanguageManager {

    /**
     * read languages from lang folder.
     *
     * @param string $langFolder Lang folder
     * @throws NoLanguageFolderException
     * @throws NoDefaultLanguageException
     * @return array()
     */
    public function readLanguages($langFolder) {
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
            $languages[$folder] = $this->readLanguage("$langFolder/$folder");
        }
    }

    private function readLanguage($languageFolder) {
        $language = array();

        if (is_file("$languageFolder/lang.php")) {
            $language['lang'] = $this->parseLangFile("$languageFolder/lang.php");
        }

        $folders = scandir($languageFolder);
        foreach ($folders as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (!is_file("$languageFolder/$file")) {
                continue;
            }
            if (substr($file, -4) !== '.txt') {
                continue;
            }
            $language[$file] = file_get_contents("$languageFolder/$file");
        }
        return $language;
    }

    function parseLangFile() {
        // FIXME
        return array();
    }
}
