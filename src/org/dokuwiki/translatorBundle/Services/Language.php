<?php

namespace org\dokuwiki\translatorBundle\Services;

/**
 * @author Dominik Eckelmann
 */
class Language {

    public function getUserLanguage() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return 'en';
        }

        preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $parsedLanguages);

        if (count($parsedLanguages[1]) === 0) {
            return 'en';
        }
        $languages = array_combine($parsedLanguages[1], $parsedLanguages[4]);

        // assume quality 1 on missing quality
        foreach ($languages as $language => $quality) {
            if ($quality === '') $languages[$language] = 1;
        }

        arsort($languages, SORT_NUMERIC);
        $preferredLanguage = array_keys($languages);
        $preferredLanguage = $preferredLanguage[0];

        // strip last part of language
        $dash = strpos($preferredLanguage, '-');
        if ($dash !== -1) {
            $preferredLanguage = substr($preferredLanguage, 0, $dash);
        }
        return $preferredLanguage;
    }

}
