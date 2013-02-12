<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TranslationController extends Controller {

    public function translateCoreAction() {
        return $this->render('dokuwikiTranslatorBundle:Translate:translate.html.twig',
                array('name' => 'DokuWiki'));
    }

    public function translatePluginAction($name) {
        return $this->render('dokuwikiTranslatorBundle:Translate:translate.html.twig',
                array('name' => $name));
    }

}
