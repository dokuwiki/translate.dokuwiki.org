<?php

namespace org\dokuwiki\translatorBundle\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

class DokuWikiToolbarExtension extends Twig_Extension {

    public function getFunctions() {
        return array(
            new Twig_SimpleFunction('dokuWikiToolbar', array(&$this, 'dokuWikiToolbar')),
        );
    }

    public function dokuWikiToolbar() {
        $template = '/var/www/wiki/htdocs/lib/tpl/dokuwiki/dwtb.html';
        if (file_exists($template)) {
            include $template;
        }
    }
}