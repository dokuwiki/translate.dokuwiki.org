<?php

namespace org\dokuwiki\translatorBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HighlightWhitespaceExtension extends AbstractExtension {

    public function getFilters() {
        return array(
            new TwigFilter('highlight_whitespace', array($this, 'highlightWhitespace'),
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