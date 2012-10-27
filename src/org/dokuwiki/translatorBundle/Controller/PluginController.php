<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PluginController extends Controller {
    public function indexAction() {
        return $this->render('dokuwikiTranslatorBundle:Plugin:index.html.twig');
    }

    public function addAction() {
        return $this->render('dokuwikiTranslatorBundle:Plugin:add.html.twig');
    }
}
