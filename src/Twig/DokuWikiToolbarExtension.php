<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DokuWikiToolbarExtension extends AbstractExtension {

    public function getFunctions() : array {
        return [new TwigFunction('dokuWikiToolbar', [&$this, 'dokuWikiToolbar'])];
    }

    public function dokuWikiToolbar() {
        $template = '/var/www/wiki/htdocs/lib/tpl/dokuwiki/dwtb.html';
        if (file_exists($template)) {
            include $template;
        }
    }
}