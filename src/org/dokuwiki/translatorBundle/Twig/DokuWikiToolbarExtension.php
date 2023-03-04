<?php

namespace org\dokuwiki\translatorBundle\Twig;

use Twig\TwigFunction;
use Twig_Extension;

class DokuWikiToolbarExtension extends Twig_Extension {

    public function getFunctions() {
        return array(
            new TwigFunction('dokuWikiToolbar', array(&$this, 'dokuWikiToolbar')),
        );
    }

    public function dokuWikiToolbar() {
        $template = '/var/www/wiki/htdocs/lib/tpl/dokuwiki/dwtb.html';
        if (file_exists($template)) {
            include $template;
        }
    }
}