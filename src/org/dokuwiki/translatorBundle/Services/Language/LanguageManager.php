<?php
namespace org\dokuwiki\translatorBundle\Services\Language;

use Symfony\Component\HttpFoundation\Request;

class LanguageManager {

    /**
     * Read languages from a lang folder.
     *
     * @param string $langFolder Lang folder
     * @param string $prefix Prefix string for item keys in language array.
     * @return LocalText[]
     *
     * @throws LanguageFileDoesNotExistException
     * @throws LanguageParseException
     * @throws NoDefaultLanguageException
     * @throws NoLanguageFolderException
     */
    public static function readLanguages($langFolder, $prefix = '') {
        if (!is_dir($langFolder)) {
            throw new NoLanguageFolderException("$langFolder is not a directory");
        }

        if (!is_dir("$langFolder/en/")) {
            throw new NoDefaultLanguageException("$langFolder/en/ is not a directory");
        }

        $folders = scandir($langFolder);
        $languages = array();
        foreach ($folders as $language) {
            if ($language === '.' || $language === '..') {
                continue;
            }
            if (!is_dir("$langFolder/$language")) {
                continue;
            }
            $languages[$language] = LanguageManager::readLanguage("$langFolder/$language", "$prefix");
        }
        return $languages;
    }

    /**
     * Read all available language files for a language
     *
     * @param string $languageFolder
     * @param string $prefix Prefix string for item keys in language array
     * @return LocalText[]
     *
     * @throws LanguageFileDoesNotExistException
     * @throws LanguageParseException
     */
    private static function readLanguage($languageFolder, $prefix) {
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
                        new LocalText($translation->getLang(), LocalText::$TYPE_ARRAY, $translation->getAuthor(), $translation->getHeader());
                continue;
            }

            if ($extension === '.txt') {
                $language[$prefix . $file] = new LocalText(file_get_contents("$languageFolder/$file"), LocalText::$TYPE_MARKUP);
            }

        }
        return $language;
    }

    /**
     * Determine the current language from url, session or browser
     *
     * @param Request $request
     * @return string
     */
    public function getLanguage(Request $request) {
        $language = $request->query->get('lang', null);
        if ($language !== null) {
            $request->getSession()->set('language', $language);
            return $language;
        }
        $sessionLanguage = $request->getSession()->get('language');
        if ($sessionLanguage !== null) {
            return $sessionLanguage;
        }
        $languages = $request->getLanguages();
        if (empty($languages)) {
            $request->getSession()->set('language', 'en');
            return 'en';
        }
        $pos = strpos($languages[0], '_');
        if ($pos !== false) {
            $languages[0] = substr($languages[0], 0, $pos);
        }
        $language = strtolower($languages[0]);
        $request->getSession()->set('language', $language);
        return $language;
    }
}
