<?php

namespace org\dokuwiki\translatorBundle\Twig;

use Twig_Extension;
use Twig_SimpleFilter;

class HighlightWhitespaceExtension extends Twig_Extension {

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName() {
        return 'highlight_whitespace';
    }

    public function getFilters() {
        return array(
            new Twig_SimpleFilter('highlight_whitespace', array($this, 'highlightWhitespace'),
                array('pre_escape' => 'html', 'is_safe' => array('html')))
        );
    }

    public function highlightWhitespace($text) {

        $tagStart = '<span class="highlight-whitespace" title="Here are whitespaces - don\'t forget them in your translation">';

        $text = preg_replace('/([ \t]+)\n/', $tagStart . '$1</span>' . "\n", $text);
        //$text = preg_replace('/\n([ \t]+)/', "\n$tagStart" . '$1</span>', $text); TODO #54

        return $text;
    }
}