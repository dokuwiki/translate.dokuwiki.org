<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PluginController extends Controller {

    public function addAction() {
        return $this->render('dokuwikiTranslatorBundle:Plugin:add.html.twig');
    }

    public function descriptionAction() {
        return $this->render('dokuwikiTranslatorBundle:Plugin:addDescription.html.twig');
    }

    public function showAction($name) {
        return $this->render('dokuwikiTranslatorBundle:Plugin:show.html.twig');
    }
}
